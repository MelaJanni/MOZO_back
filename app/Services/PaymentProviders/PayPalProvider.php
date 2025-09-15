<?php

namespace App\Services\PaymentProviders;

use App\Contracts\PaymentProviderInterface;
use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Coupon;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalProvider implements PaymentProviderInterface
{
    private ?string $clientId;
    private ?string $clientSecret;
    private string $baseUrl;
    private bool $isSandbox;

    public function __construct()
    {
        $this->isSandbox = config('services.paypal.environment', 'sandbox') === 'sandbox';
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');

        $this->baseUrl = $this->isSandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    public function createCheckout(
        User $user,
        Plan $plan,
        ?Coupon $coupon = null,
        array $metadata = []
    ): array {
        try {
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return ['success' => false, 'error' => 'Failed to get PayPal access token'];
            }

            $amount = $plan->price_cents;

            // Apply coupon discount if provided
            if ($coupon && $coupon->canBeRedeemed()) {
                $amount = $plan->getDiscountedPrice($coupon);
            }

            $order = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => "plan_{$plan->id}_user_{$user->id}_" . time(),
                        'description' => "Suscripción {$plan->name} - {$plan->interval}",
                        'amount' => [
                            'currency_code' => $plan->currency ?? 'USD',
                            'value' => number_format($amount / 100, 2, '.', ''),
                        ],
                        'custom_id' => json_encode([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'coupon_id' => $coupon?->id,
                            'original_amount' => $plan->price_cents,
                            'final_amount' => $amount,
                        ]),
                    ]
                ],
                'application_context' => [
                    'return_url' => route('billing.success'),
                    'cancel_url' => route('billing.failure'),
                    'brand_name' => config('app.name'),
                    'landing_page' => 'BILLING',
                    'user_action' => 'PAY_NOW',
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation',
            ])->post($this->baseUrl . '/v2/checkout/orders', $order);

            if ($response->successful()) {
                $data = $response->json();

                // Create pending subscription
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'provider' => 'paypal',
                    'provider_subscription_id' => $data['id'],
                    'status' => 'pending',
                    'coupon_id' => $coupon?->id,
                    'metadata' => array_merge($metadata, $order['purchase_units'][0]['custom_id'] ? json_decode($order['purchase_units'][0]['custom_id'], true) : []),
                ]);

                // Find approval URL
                $approvalUrl = collect($data['links'])->firstWhere('rel', 'approve')['href'] ?? null;

                return [
                    'success' => true,
                    'checkout_url' => $approvalUrl,
                    'order_id' => $data['id'],
                    'subscription_id' => $subscription->id,
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create PayPal order',
                'details' => $response->json(),
            ];

        } catch (\Exception $e) {
            Log::error('PayPal checkout creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);

            return [
                'success' => false,
                'error' => 'Payment provider error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function handleWebhook(array $payload, string $signature = null): bool
    {
        try {
            Log::info('PayPal webhook received', $payload);

            if (!$this->validateWebhookSignature($payload, $signature)) {
                Log::warning('PayPal webhook signature validation failed');
                return false;
            }

            $eventType = $payload['event_type'] ?? null;
            $resource = $payload['resource'] ?? [];

            switch ($eventType) {
                case 'PAYMENT.CAPTURE.COMPLETED':
                    return $this->handlePaymentCompleted($resource);
                case 'PAYMENT.CAPTURE.DENIED':
                case 'PAYMENT.CAPTURE.DECLINED':
                    return $this->handlePaymentFailed($resource);
                default:
                    Log::info('PayPal webhook event not handled', ['event_type' => $eventType]);
                    return true;
            }

        } catch (\Exception $e) {
            Log::error('PayPal webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return false;
        }
    }

    private function handlePaymentCompleted(array $resource): bool
    {
        $captureId = $resource['id'] ?? null;
        $customId = $resource['custom_id'] ?? null;

        if (!$captureId || !$customId) {
            return false;
        }

        $customData = json_decode($customId, true);
        if (!$customData || !isset($customData['user_id'], $customData['plan_id'])) {
            return false;
        }

        // Find subscription
        $subscription = Subscription::where('provider', 'paypal')
            ->where('user_id', $customData['user_id'])
            ->where('plan_id', $customData['plan_id'])
            ->where('status', 'pending')
            ->first();

        if (!$subscription) {
            return false;
        }

        // Create payment record
        Payment::updateOrCreate(
            [
                'provider_payment_id' => $captureId,
                'provider' => 'paypal',
            ],
            [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'amount_cents' => ($resource['amount']['value'] ?? 0) * 100,
                'currency' => $resource['amount']['currency_code'] ?? 'USD',
                'status' => 'paid',
                'paid_at' => now(),
                'raw_payload' => $resource,
            ]
        );

        // Update subscription
        $subscription->update([
            'status' => 'active',
            'current_period_end' => now()->add($subscription->plan->interval, 1),
        ]);

        return true;
    }

    private function handlePaymentFailed(array $resource): bool
    {
        $captureId = $resource['id'] ?? null;
        $customId = $resource['custom_id'] ?? null;

        if (!$captureId || !$customId) {
            return false;
        }

        $customData = json_decode($customId, true);
        if (!$customData || !isset($customData['user_id'], $customData['plan_id'])) {
            return false;
        }

        // Find subscription
        $subscription = Subscription::where('provider', 'paypal')
            ->where('user_id', $customData['user_id'])
            ->where('plan_id', $customData['plan_id'])
            ->where('status', 'pending')
            ->first();

        if (!$subscription) {
            return false;
        }

        // Create failed payment record
        Payment::updateOrCreate(
            [
                'provider_payment_id' => $captureId,
                'provider' => 'paypal',
            ],
            [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'amount_cents' => ($resource['amount']['value'] ?? 0) * 100,
                'currency' => $resource['amount']['currency_code'] ?? 'USD',
                'status' => 'failed',
                'failure_reason' => $resource['status_details']['reason'] ?? 'Payment failed',
                'raw_payload' => $resource,
            ]
        );

        // Update subscription
        $subscription->update(['status' => 'failed']);

        return true;
    }

    private function getAccessToken(): ?string
    {
        try {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post($this->baseUrl . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                return $response->json()['access_token'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('PayPal access token request failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function cancelSubscription(Subscription $subscription): bool
    {
        // PayPal order-based payments don't have recurring subscriptions by default
        // This would be implemented if using PayPal Subscriptions API
        $subscription->update(['status' => 'canceled']);
        return true;
    }

    public function fetchSubscriptionStatus(Subscription $subscription): array
    {
        return [
            'status' => $subscription->status,
            'provider_status' => $subscription->status,
        ];
    }

    public function createOneTimePayment(
        User $user,
        int $amountCents,
        string $description,
        array $metadata = []
    ): array {
        return $this->createCheckout(
            $user,
            new Plan([
                'code' => 'one_time',
                'name' => $description,
                'price_cents' => $amountCents,
                'currency' => 'USD',
                'interval' => 'one_time',
            ]),
            null,
            $metadata
        );
    }

    public function processRefund(string $paymentId, int $amountCents = null): bool
    {
        try {
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return false;
            }

            $refundData = [];
            if ($amountCents) {
                $refundData['amount'] = [
                    'value' => number_format($amountCents / 100, 2, '.', ''),
                    'currency_code' => 'USD',
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation',
            ])->post($this->baseUrl . "/v2/payments/captures/{$paymentId}/refund", $refundData);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('PayPal refund failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getProviderName(): string
    {
        return 'paypal';
    }

    public function isEnabled(): bool
    {
        return config('billing.paypal.enabled', false) && !empty($this->clientId) && !empty($this->clientSecret);
    }

    public function validateWebhookSignature(array $payload, string $signature = null): bool
    {
        // PayPal webhook signature validation would go here
        // This requires the webhook certificate verification
        return true;
    }
}
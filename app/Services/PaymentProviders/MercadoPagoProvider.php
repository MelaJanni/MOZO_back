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

class MercadoPagoProvider implements PaymentProviderInterface
{
    private ?string $accessToken;
    private string $baseUrl;
    private bool $isSandbox;

    public function __construct()
    {
        $this->isSandbox = config('services.mercado_pago.environment', 'sandbox') === 'sandbox';
        $this->accessToken = config('services.mercado_pago.access_token') 
            ?? config('services.mercadopago.access_token')
            ?? env('MERCADO_PAGO_ACCESS_TOKEN');

        $this->baseUrl = $this->isSandbox
            ? 'https://api.mercadopago.com'
            : 'https://api.mercadopago.com';

        // Log para debugging
        if (!$this->accessToken) {
            Log::warning('MercadoPago access token no configurado', [
                'config_mercado_pago' => config('services.mercado_pago'),
                'env_token' => env('MERCADO_PAGO_ACCESS_TOKEN') ? 'exists' : 'missing',
            ]);
        }
    }

    public function createCheckout(
        User $user,
        Plan $plan,
        ?Coupon $coupon = null,
        array $metadata = []
    ): array {
        try {
            // Verificar que el access token esté configurado
            if (!$this->accessToken) {
                Log::error('MercadoPago access token no configurado');
                return [
                    'success' => false,
                    'error' => 'Payment provider not configured',
                    'message' => 'La pasarela de pagos no está configurada correctamente. Por favor contacta al administrador.',
                ];
            }

            $amount = $plan->price_cents;

            // Apply coupon discount if provided
            if ($coupon && $coupon->canBeRedeemed()) {
                $amount = $plan->getDiscountedPrice($coupon);
            }

            $preference = [
                'items' => [
                    [
                        'id' => $plan->code,
                        'title' => $plan->name,
                        'description' => "Suscripción {$plan->name} - {$plan->interval}",
                        'quantity' => 1,
                        'unit_price' => $amount / 100, // MercadoPago expects decimals
                        'currency_id' => $plan->currency ?? 'USD',
                    ]
                ],
                'payer' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'back_urls' => [
                    'success' => route('billing.success'),
                    'failure' => route('billing.failure'),
                    'pending' => route('billing.pending'),
                ],
                'auto_return' => 'approved',
                'external_reference' => "plan_{$plan->id}_user_{$user->id}_" . time(),
                'metadata' => array_merge($metadata, [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'coupon_id' => $coupon?->id,
                    'original_amount' => $plan->price_cents,
                    'final_amount' => $amount,
                ]),
                'notification_url' => route('webhooks.mercadopago'),
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/checkout/preferences', $preference);

            if ($response->successful()) {
                $data = $response->json();

                // Create pending subscription
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'provider' => 'mercadopago',
                    'provider_subscription_id' => $data['id'],
                    'status' => 'pending',
                    'coupon_id' => $coupon?->id,
                    'metadata' => $preference['metadata'],
                ]);

                return [
                    'success' => true,
                    'checkout_url' => $this->isSandbox ? $data['sandbox_init_point'] : $data['init_point'],
                    'preference_id' => $data['id'],
                    'subscription_id' => $subscription->id,
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create MercadoPago preference',
                'details' => $response->json(),
            ];

        } catch (\Exception $e) {
            Log::error('MercadoPago checkout creation failed', [
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
            Log::info('MercadoPago webhook received', $payload);

            if (!$this->validateWebhookSignature($payload, $signature)) {
                Log::warning('MercadoPago webhook signature validation failed');
                return false;
            }

            $type = $payload['type'] ?? null;
            $data = $payload['data'] ?? [];

            if ($type === 'payment') {
                return $this->handlePaymentWebhook($data);
            }

            Log::info('MercadoPago webhook type not handled', ['type' => $type]);
            return true;

        } catch (\Exception $e) {
            Log::error('MercadoPago webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return false;
        }
    }

    private function handlePaymentWebhook(array $data): bool
    {
        $paymentId = $data['id'] ?? null;
        if (!$paymentId) {
            return false;
        }

        // Fetch payment details from MercadoPago
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
        ])->get($this->baseUrl . "/v1/payments/{$paymentId}");

        if (!$response->successful()) {
            return false;
        }

        $paymentData = $response->json();
        $status = $paymentData['status'] ?? null;
        $externalReference = $paymentData['external_reference'] ?? null;

        if (!$externalReference) {
            return false;
        }

        // Find subscription by external reference
        $subscription = Subscription::where('provider', 'mercadopago')
            ->whereJsonContains('metadata->external_reference', $externalReference)
            ->first();

        if (!$subscription) {
            return false;
        }

        // Create or update payment record
        $payment = Payment::updateOrCreate(
            [
                'provider_payment_id' => $paymentId,
                'provider' => 'mercadopago',
            ],
            [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'amount_cents' => $paymentData['transaction_amount'] * 100,
                'currency' => $paymentData['currency_id'],
                'status' => $this->mapPaymentStatus($status),
                'paid_at' => $status === 'approved' ? now() : null,
                'failure_reason' => $status === 'rejected' ? ($paymentData['status_detail'] ?? 'Unknown') : null,
                'raw_payload' => $paymentData,
            ]
        );

        // Update subscription status
        if ($status === 'approved') {
            $subscription->update([
                'status' => 'active',
                'current_period_end' => now()->add($subscription->plan->interval, 1),
            ]);
        } elseif ($status === 'rejected') {
            $subscription->update(['status' => 'failed']);
        }

        return true;
    }

    private function mapPaymentStatus(string $mpStatus): string
    {
        return match($mpStatus) {
            'approved' => 'paid',
            'pending' => 'pending',
            'rejected', 'cancelled' => 'failed',
            default => 'pending',
        };
    }

    public function cancelSubscription(Subscription $subscription): bool
    {
        // MercadoPago doesn't have recurring subscriptions by default
        // This would be implemented if using MercadoPago subscriptions
        $subscription->update(['status' => 'canceled']);
        return true;
    }

    public function fetchSubscriptionStatus(Subscription $subscription): array
    {
        // For MercadoPago, we'd check the latest payment status
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
            $refundData = ['amount' => $amountCents ? $amountCents / 100 : null];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . "/v1/payments/{$paymentId}/refunds", array_filter($refundData));

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('MercadoPago refund failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getProviderName(): string
    {
        return 'mercadopago';
    }

    public function isEnabled(): bool
    {
        return config('billing.mercadopago.enabled', false) && !empty($this->accessToken);
    }

    public function validateWebhookSignature(array $payload, string $signature = null): bool
    {
        // MercadoPago webhook signature validation would go here
        // For now, we'll implement basic validation
        return true;
    }
}
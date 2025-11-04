<?php

namespace App\Services\PaymentProviders;

use App\Contracts\PaymentProviderInterface;
use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OfflineBankTransferProvider implements PaymentProviderInterface
{
    public function createCheckout(
        User $user,
        Plan $plan,
        ?Coupon $coupon = null,
        array $metadata = []
    ): array {
        try {
            $amount = $plan->price_cents;

            // Apply coupon discount if provided
            if ($coupon && $coupon->canBeRedeemed()) {
                $amount = $plan->getDiscountedPrice($coupon);
            }

            // Create pending subscription
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'provider' => 'bank_transfer',
                'provider_subscription_id' => 'bt_' . time() . '_' . $user->id,
                'status' => 'pending',
                'coupon_id' => $coupon?->id,
                'metadata' => array_merge($metadata, [
                    'original_amount' => $plan->price_cents,
                    'final_amount' => $amount,
                    'currency' => $plan->currency ?? 'USD',
                ]),
            ]);

            // Create pending payment record
            $payment = Payment::create([
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'provider' => 'bank_transfer',
                'provider_payment_id' => 'bt_payment_' . $subscription->id,
                'amount_cents' => $amount,
                'currency' => $plan->currency ?? 'USD',
                'status' => 'pending',
                'raw_payload' => [
                    'plan_name' => $plan->name,
                    'user_email' => $user->email,
                    'created_via' => 'offline_transfer',
                ],
            ]);

            // Create support ticket automatically
            $ticket = Ticket::create([
                'user_id' => $user->id,
                'subject' => "Transferencia Bancaria - Suscripción {$plan->name}",
                'message' => "El usuario {$user->name} ({$user->email}) ha solicitado activar la suscripción {$plan->name} mediante transferencia bancaria.\n\nMonto: $" . number_format($amount / 100, 2) . "\nPago ID: {$payment->provider_payment_id}\nSuscripción ID: {$subscription->id}",
                'status' => 'open',
                'priority' => 'normal',
                'tags' => ['transferencia', 'pago', 'suscripcion'],
            ]);

            // Generate bank transfer instructions
            $bankInfo = $this->getBankTransferInstructions();
            $referenceNumber = $payment->provider_payment_id;

            // Send email with transfer instructions
            try {
                // This would send an email with bank transfer instructions
                // Mail::to($user->email)->send(new BankTransferInstructionsMail($user, $plan, $amount, $referenceNumber, $bankInfo));
            } catch (\Exception $e) {
                Log::warning('Failed to send bank transfer instructions email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'success' => true,
                'payment_method' => 'bank_transfer',
                'subscription_id' => $subscription->id,
                'payment_id' => $payment->id,
                'reference_number' => $referenceNumber,
                'amount' => $amount / 100,
                'currency' => $plan->currency ?? 'USD',
                'bank_info' => $bankInfo,
                'ticket_id' => $ticket->id,
                'instructions' => $this->getTransferInstructions($referenceNumber, $amount / 100),
                'support_contacts' => [
                    'whatsapp' => config('billing.support.whatsapp'),
                    'email' => config('billing.support.email'),
                    'phone' => config('billing.support.phone'),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Bank transfer checkout creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);

            return [
                'success' => false,
                'error' => 'Error al procesar la transferencia bancaria',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function handleWebhook(array $payload, string $signature = null): bool
    {
        // Bank transfers don't have webhooks - they're processed manually
        return true;
    }

    public function cancelSubscription(Subscription $subscription): bool
    {
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
        // Bank transfer refunds must be processed manually
        $payment = Payment::where('provider_payment_id', $paymentId)
            ->where('provider', 'bank_transfer')
            ->first();

        if (!$payment) {
            return false;
        }

        // Create a ticket for manual refund processing
        Ticket::create([
            'user_id' => $payment->user_id,
            'subject' => "Solicitud de Reembolso - Transferencia Bancaria",
            'message' => "Solicitud de reembolso para el pago {$paymentId}.\nMonto original: $" . number_format($payment->amount_cents / 100, 2) . "\nMonto a reembolsar: $" . number_format(($amountCents ?? $payment->amount_cents) / 100, 2),
            'status' => 'open',
            'priority' => 'high',
            'tags' => ['reembolso', 'transferencia', 'pago'],
        ]);

        return true;
    }

    public function getProviderName(): string
    {
        return 'bank_transfer';
    }

    public function isEnabled(): bool
    {
        return config('billing.bank_transfer.enabled', true);
    }

    public function validateWebhookSignature(array $payload, string $signature = null): bool
    {
        // No signature validation needed for manual transfers
        return true;
    }

    /**
     * Approve a pending bank transfer payment manually
     */
    public function approvePayment(Payment $payment): bool
    {
        if ($payment->provider !== 'bank_transfer' || $payment->status !== 'pending') {
            return false;
        }

        try {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'raw_payload' => array_merge($payment->raw_payload ?? [], [
                    'approved_at' => now()->toISOString(),
                    'approved_by' => auth()->user()?->id ?? 'system',
                    'approval_method' => 'manual',
                ]),
            ]);

            // Update subscription status
            if ($payment->subscription) {
                $payment->subscription->update([
                    'status' => 'active',
                    'current_period_end' => now()->add($payment->subscription->plan->interval, 1),
                ]);

                // Redeem coupon if used
                if ($payment->subscription->coupon) {
                    $payment->subscription->coupon->redeem();
                }
            }

            // Send confirmation email
            try {
                // Mail::to($payment->user->email)->send(new PaymentApprovedMail($payment));
            } catch (\Exception $e) {
                Log::warning('Failed to send payment approval email', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to approve bank transfer payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function getBankTransferInstructions(): array
    {
        return [
            'bank_name' => config('billing.bank_transfer.bank_name', 'Banco Ejemplo'),
            'account_holder' => config('billing.bank_transfer.account_holder', 'MOZO QR S.A.'),
            'account_number' => config('billing.bank_transfer.account_number', '1234567890'),
            'routing_number' => config('billing.bank_transfer.routing_number', '021000021'),
            'swift_code' => config('billing.bank_transfer.swift_code', 'EXAMPLE'),
            'bank_address' => config('billing.bank_transfer.bank_address', 'Dirección del Banco'),
        ];
    }

    private function getTransferInstructions(string $referenceNumber, float $amount): string
    {
        $bankInfo = $this->getBankTransferInstructions();

        return "Para completar su suscripción, por favor realice una transferencia bancaria con los siguientes datos:\n\n" .
               "Banco: {$bankInfo['bank_name']}\n" .
               "Beneficiario: {$bankInfo['account_holder']}\n" .
               "Número de Cuenta: {$bankInfo['account_number']}\n" .
               "Número de Referencia: {$referenceNumber}\n" .
               "Monto: $" . number_format($amount, 2) . "\n\n" .
               "IMPORTANTE: Incluya el número de referencia en el concepto de la transferencia.\n\n" .
               "Una vez realizada la transferencia, nuestro equipo verificará el pago y activará su suscripción dentro de 24-48 horas hábiles.\n\n" .
               "Si tiene alguna pregunta, puede contactarnos a través de:\n" .
               "WhatsApp: " . config('billing.support.whatsapp', 'No configurado') . "\n" .
               "Email: " . config('billing.support.email', 'soporte@mozoqr.com') . "\n" .
               "Teléfono: " . config('billing.support.phone', 'No configurado');
    }
}
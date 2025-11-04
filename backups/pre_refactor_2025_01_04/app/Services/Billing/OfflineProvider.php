<?php

namespace App\Services\Billing;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

class OfflineProvider implements PaymentProviderInterface
{
    public function createCheckout(User $user, string $planCode, ?string $couponCode = null): array
    {
        $plan = Plan::where('code', $planCode)->firstOrFail();
        // Sólo indica que el flujo es offline (ya lo manejamos en MembershipController)
        return [
            'mode' => 'offline',
            'amount_cents' => $plan->price_cents,
            'currency' => $plan->currency,
        ];
    }

    public function handleWebhook(array $payload, array $headers = []): array
    {
        // No hay webhooks para offline
        return ['type' => 'offline', 'status' => 'noop'];
    }

    public function cancelSubscription(Subscription $subscription): bool
    {
        // No acción remota
        return true;
    }
}

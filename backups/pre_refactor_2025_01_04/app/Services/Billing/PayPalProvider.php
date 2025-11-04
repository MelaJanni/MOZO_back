<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\User;

class PayPalProvider implements PaymentProviderInterface
{
    public function createCheckout(User $user, string $planCode, ?string $couponCode = null): array
    {
        // TODO: Implementar con PayPal Subscriptions
        return [
            'mode' => 'redirect',
            'redirect_url' => url('/payments/redirect/paypal/'.uniqid()),
        ];
    }

    public function handleWebhook(array $payload, array $headers = []): array
    {
        // TODO: validar firma y normalizar
        return [
            'type' => 'stub',
            'status' => 'received',
        ];
    }

    public function cancelSubscription(Subscription $subscription): bool
    {
        // TODO: cancelar en PayPal
        return true;
    }
}

<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\User;

interface PaymentProviderInterface
{
    /**
     * Inicia un checkout y devuelve URL de redirección u otros datos.
     */
    public function createCheckout(User $user, string $planCode, ?string $couponCode = null): array;

    /**
     * Procesa el webhook del proveedor y devuelve un resultado normalizado.
     */
    public function handleWebhook(array $payload, array $headers = []): array;

    /**
     * Cancela una suscripción en el proveedor.
     */
    public function cancelSubscription(Subscription $subscription): bool;
}

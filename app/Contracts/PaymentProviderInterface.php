<?php

namespace App\Contracts;

use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Coupon;

interface PaymentProviderInterface
{
    /**
     * Create a checkout session/preference for a subscription
     */
    public function createCheckout(
        User $user,
        Plan $plan,
        ?Coupon $coupon = null,
        array $metadata = []
    ): array;

    /**
     * Handle incoming webhook from payment provider
     */
    public function handleWebhook(array $payload, string $signature = null): bool;

    /**
     * Cancel an active subscription
     */
    public function cancelSubscription(Subscription $subscription): bool;

    /**
     * Fetch subscription status from provider
     */
    public function fetchSubscriptionStatus(Subscription $subscription): array;

    /**
     * Create a one-time payment
     */
    public function createOneTimePayment(
        User $user,
        int $amountCents,
        string $description,
        array $metadata = []
    ): array;

    /**
     * Process a refund
     */
    public function processRefund(string $paymentId, int $amountCents = null): bool;

    /**
     * Get provider name
     */
    public function getProviderName(): string;

    /**
     * Check if provider is enabled
     */
    public function isEnabled(): bool;

    /**
     * Validate webhook signature
     */
    public function validateWebhookSignature(array $payload, string $signature = null): bool;
}
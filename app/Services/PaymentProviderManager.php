<?php

namespace App\Services;

use App\Contracts\PaymentProviderInterface;
use App\Services\PaymentProviders\MercadoPagoProvider;
use App\Services\PaymentProviders\PayPalProvider;
use App\Services\PaymentProviders\OfflineBankTransferProvider;
use Illuminate\Support\Facades\Log;

class PaymentProviderManager
{
    private array $providers = [];

    public function __construct()
    {
        $this->registerProviders();
    }

    private function registerProviders(): void
    {
        $this->providers = [
            'mercadopago' => new MercadoPagoProvider(),
            'paypal' => new PayPalProvider(),
            'bank_transfer' => new OfflineBankTransferProvider(),
        ];
    }

    public function getProvider(string $providerName): ?PaymentProviderInterface
    {
        return $this->providers[$providerName] ?? null;
    }

    public function getEnabledProviders(): array
    {
        return array_filter($this->providers, function ($provider) {
            return $provider->isEnabled();
        });
    }

    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);
    }

    public function isProviderEnabled(string $providerName): bool
    {
        $provider = $this->getProvider($providerName);
        return $provider && $provider->isEnabled();
    }

    public function getDefaultProvider(): ?PaymentProviderInterface
    {
        $defaultProviderName = config('billing.default_provider', 'mercadopago');
        return $this->getProvider($defaultProviderName);
    }

    /**
     * Get provider configurations for frontend
     */
    public function getProvidersConfig(): array
    {
        $config = [];

        foreach ($this->getEnabledProviders() as $name => $provider) {
            $config[$name] = [
                'name' => $provider->getProviderName(),
                'enabled' => $provider->isEnabled(),
                'display_name' => $this->getProviderDisplayName($name),
                'description' => $this->getProviderDescription($name),
                'logo' => $this->getProviderLogo($name),
            ];
        }

        return $config;
    }

    private function getProviderDisplayName(string $providerName): string
    {
        return match($providerName) {
            'mercadopago' => 'Mercado Pago',
            'paypal' => 'PayPal',
            'bank_transfer' => 'Transferencia Bancaria',
            default => ucfirst($providerName),
        };
    }

    private function getProviderDescription(string $providerName): string
    {
        return match($providerName) {
            'mercadopago' => 'Paga con tarjeta de crédito, débito o efectivo',
            'paypal' => 'Paga con tu cuenta PayPal o tarjeta',
            'bank_transfer' => 'Transferencia bancaria directa (procesamiento manual)',
            default => 'Método de pago disponible',
        };
    }

    private function getProviderLogo(string $providerName): string
    {
        return match($providerName) {
            'mercadopago' => '/images/payments/mercadopago.png',
            'paypal' => '/images/payments/paypal.png',
            'bank_transfer' => '/images/payments/bank-transfer.png',
            default => '/images/payments/default.png',
        };
    }

    /**
     * Handle webhook for any provider
     */
    public function handleWebhook(string $providerName, array $payload, string $signature = null): bool
    {
        try {
            $provider = $this->getProvider($providerName);

            if (!$provider) {
                Log::warning("Unknown payment provider for webhook: {$providerName}");
                return false;
            }

            return $provider->handleWebhook($payload, $signature);

        } catch (\Exception $e) {
            Log::error("Payment webhook handling failed for {$providerName}", [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return false;
        }
    }

    /**
     * Get billing configuration for .env setup
     */
    public function getRequiredEnvVariables(): array
    {
        return [
            'billing.default_provider' => 'BILLING_DEFAULT_PROVIDER',
            'billing.grace_days' => 'BILLING_GRACE_DAYS',

            // MercadoPago
            'billing.mercadopago.enabled' => 'MERCADOPAGO_ENABLED',
            'billing.mercadopago.sandbox' => 'MERCADOPAGO_SANDBOX',
            'billing.mercadopago.sandbox_access_token' => 'MERCADOPAGO_SANDBOX_ACCESS_TOKEN',
            'billing.mercadopago.production_access_token' => 'MERCADOPAGO_PRODUCTION_ACCESS_TOKEN',

            // PayPal
            'billing.paypal.enabled' => 'PAYPAL_ENABLED',
            'billing.paypal.sandbox' => 'PAYPAL_SANDBOX',
            'billing.paypal.sandbox_client_id' => 'PAYPAL_SANDBOX_CLIENT_ID',
            'billing.paypal.sandbox_client_secret' => 'PAYPAL_SANDBOX_CLIENT_SECRET',
            'billing.paypal.production_client_id' => 'PAYPAL_PRODUCTION_CLIENT_ID',
            'billing.paypal.production_client_secret' => 'PAYPAL_PRODUCTION_CLIENT_SECRET',

            // Bank Transfer
            'billing.bank_transfer.enabled' => 'BANK_TRANSFER_ENABLED',
            'billing.bank_transfer.bank_name' => 'BANK_TRANSFER_BANK_NAME',
            'billing.bank_transfer.account_holder' => 'BANK_TRANSFER_ACCOUNT_HOLDER',
            'billing.bank_transfer.account_number' => 'BANK_TRANSFER_ACCOUNT_NUMBER',
            'billing.bank_transfer.routing_number' => 'BANK_TRANSFER_ROUTING_NUMBER',
            'billing.bank_transfer.swift_code' => 'BANK_TRANSFER_SWIFT_CODE',
            'billing.bank_transfer.bank_address' => 'BANK_TRANSFER_BANK_ADDRESS',

            // Support
            'billing.support.email' => 'BILLING_SUPPORT_EMAIL',
            'billing.support.phone' => 'BILLING_SUPPORT_PHONE',
            'billing.support.whatsapp' => 'BILLING_SUPPORT_WHATSAPP',
        ];
    }
}
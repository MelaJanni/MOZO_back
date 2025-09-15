<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Provider
    |--------------------------------------------------------------------------
    */
    'default_provider' => env('BILLING_DEFAULT_PROVIDER', 'mercadopago'),

    /*
    |--------------------------------------------------------------------------
    | Grace Period Days
    |--------------------------------------------------------------------------
    */
    'grace_days' => env('BILLING_GRACE_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | Legacy Plans (for compatibility)
    |--------------------------------------------------------------------------
    */
    'plans' => [
        'free' => null,
        'monthly' => 'P1M', // ISO8601 period notation (informativo)
        'annual' => 'P1Y',
    ],

    /*
    |--------------------------------------------------------------------------
    | MercadoPago Configuration
    |--------------------------------------------------------------------------
    */
    'mercadopago' => [
        'enabled' => env('MERCADOPAGO_ENABLED', false),
        'sandbox' => env('MERCADOPAGO_SANDBOX', true),
        'sandbox_access_token' => env('MERCADOPAGO_SANDBOX_ACCESS_TOKEN'),
        'production_access_token' => env('MERCADOPAGO_PRODUCTION_ACCESS_TOKEN'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PayPal Configuration
    |--------------------------------------------------------------------------
    */
    'paypal' => [
        'enabled' => env('PAYPAL_ENABLED', false),
        'sandbox' => env('PAYPAL_SANDBOX', true),
        'sandbox_client_id' => env('PAYPAL_SANDBOX_CLIENT_ID'),
        'sandbox_client_secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET'),
        'production_client_id' => env('PAYPAL_PRODUCTION_CLIENT_ID'),
        'production_client_secret' => env('PAYPAL_PRODUCTION_CLIENT_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bank Transfer Configuration
    |--------------------------------------------------------------------------
    */
    'bank_transfer' => [
        'enabled' => env('BANK_TRANSFER_ENABLED', true),
        'bank_name' => env('BANK_TRANSFER_BANK_NAME', 'Banco Ejemplo'),
        'account_holder' => env('BANK_TRANSFER_ACCOUNT_HOLDER', 'MOZO QR S.A.'),
        'account_number' => env('BANK_TRANSFER_ACCOUNT_NUMBER', '1234567890'),
        'routing_number' => env('BANK_TRANSFER_ROUTING_NUMBER', '021000021'),
        'swift_code' => env('BANK_TRANSFER_SWIFT_CODE', 'EXAMPLE'),
        'bank_address' => env('BANK_TRANSFER_BANK_ADDRESS', 'Dirección del Banco'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Support Configuration
    |--------------------------------------------------------------------------
    */
    'support' => [
        'email' => env('BILLING_SUPPORT_EMAIL', 'soporte@mozoqr.com'),
        'phone' => env('BILLING_SUPPORT_PHONE'),
        'whatsapp' => env('BILLING_SUPPORT_WHATSAPP'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Mappings
    |--------------------------------------------------------------------------
    */
    'status_mapping' => [
        'pending' => 'Pendiente',
        'active' => 'Activa',
        'in_trial' => 'En Prueba',
        'past_due' => 'Vencida',
        'canceled' => 'Cancelada',
        'failed' => 'Fallida',
    ],

    'payment_status_mapping' => [
        'pending' => 'Pendiente',
        'paid' => 'Pagado',
        'failed' => 'Fallido',
        'refunded' => 'Reembolsado',
        'partially_refunded' => 'Reembolsado Parcial',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook & Dunning Configuration
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        'retry_attempts' => 3,
        'retry_delay' => 60,
        'timeout' => 30,
    ],

    'dunning' => [
        'reminder_days' => [7, 3, 1],
        'retry_attempts' => 3,
        'retry_intervals' => [1, 3, 7],
    ],
];

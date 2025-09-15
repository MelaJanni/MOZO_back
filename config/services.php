<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'firebase' => [
    // Deshabilitado por defecto para evitar caídas si falta el JSON en prod
    'enabled' => env('FIREBASE_ENABLED', false),
        'project_id' => env('FIREBASE_PROJECT_ID', 'mozoqr-7d32c'),
        'server_key' => env('FIREBASE_SERVER_KEY'),
        'service_account_path' => env('FIREBASE_SERVICE_ACCOUNT_PATH', storage_path('app/firebase/firebase.json')),
        // Configuración para el frontend
        'api_key' => env('FIREBASE_API_KEY'),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
        'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
        'app_id' => env('FIREBASE_APP_ID'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],

    'mercado_pago' => [
        'access_token' => env('MERCADO_PAGO_ACCESS_TOKEN'),
        'public_key' => env('MERCADO_PAGO_PUBLIC_KEY'),
        'environment' => env('MERCADO_PAGO_ENVIRONMENT', 'sandbox'), // sandbox o production
        'webhook_secret' => env('MERCADO_PAGO_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'environment' => env('PAYPAL_ENVIRONMENT', 'sandbox'), // sandbox o live
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    'bank_transfer' => [
        'bank_name' => env('BANK_TRANSFER_BANK_NAME', 'Banco de la Nación Argentina'),
        'account_number' => env('BANK_TRANSFER_ACCOUNT_NUMBER', '1234-5678-9012-3456'),
        'cbu' => env('BANK_TRANSFER_CBU', '0110123456789012345678'),
        'cuit' => env('BANK_TRANSFER_CUIT', '20-12345678-9'),
        'account_holder' => env('BANK_TRANSFER_ACCOUNT_HOLDER', 'MOZO QR S.A.S.'),
        'whatsapp_number' => env('BANK_TRANSFER_WHATSAPP', '+5491112345678'),
        'email' => env('BANK_TRANSFER_EMAIL', 'pagos@mozoqr.com'),
    ],

];

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'MercadoPago',
                'slug' => 'mercadopago',
                'description' => 'Paga con tarjeta de crédito, débito o efectivo a través de MercadoPago',
                'config' => [
                    'access_token' => null,
                    'public_key' => null,
                    'test_access_token' => null,
                    'test_public_key' => null,
                ],
                'fees' => [
                    'percentage' => 3.99,
                    'fixed' => 0,
                ],
                'supported_currencies' => ['ARS', 'USD'],
                'is_enabled' => true,
                'is_test_mode' => true,
                'sort_order' => 1,
                'min_amount' => 10.00,
                'max_amount' => 500000.00,
                'webhook_url' => url('/api/webhooks/mercadopago'),
                'logo_url' => 'https://http2.mlstatic.com/storage/logos-api-admin/ce3f8260-ee55-11ea-9685-65c5ac2558e7-xl@2x.png',
                'color_primary' => '#009EE3',
                'color_secondary' => '#FFFFFF',
            ],
            [
                'name' => 'PayPal',
                'slug' => 'paypal',
                'description' => 'Paga de forma segura con tu cuenta PayPal',
                'config' => [
                    'client_id' => null,
                    'client_secret' => null,
                    'test_client_id' => null,
                    'test_client_secret' => null,
                ],
                'fees' => [
                    'percentage' => 4.40,
                    'fixed' => 0.30,
                ],
                'supported_currencies' => ['USD'],
                'is_enabled' => false, // Deshabilitado por defecto
                'is_test_mode' => true,
                'sort_order' => 2,
                'min_amount' => 1.00,
                'max_amount' => 10000.00,
                'webhook_url' => url('/api/webhooks/paypal'),
                'logo_url' => 'https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg',
                'color_primary' => '#0070BA',
                'color_secondary' => '#003087',
            ],
            [
                'name' => 'Stripe',
                'slug' => 'stripe',
                'description' => 'Paga con tarjeta de crédito de forma segura con Stripe',
                'config' => [
                    'public_key' => null,
                    'secret_key' => null,
                    'test_public_key' => null,
                    'test_secret_key' => null,
                ],
                'fees' => [
                    'percentage' => 2.9,
                    'fixed' => 0.30,
                ],
                'supported_currencies' => ['USD', 'EUR'],
                'is_enabled' => false, // Deshabilitado por defecto
                'is_test_mode' => true,
                'sort_order' => 3,
                'min_amount' => 0.50,
                'max_amount' => 999999.99,
                'webhook_url' => url('/api/webhooks/stripe'),
                'logo_url' => 'https://stripe.com/img/v3/home/social.png',
                'color_primary' => '#635BFF',
                'color_secondary' => '#0A2540',
            ],
            [
                'name' => 'Transferencia Bancaria',
                'slug' => 'bank_transfer',
                'description' => 'Pago manual por transferencia bancaria',
                'config' => [
                    'bank_name' => 'Banco Ejemplo',
                    'account_number' => '1234567890',
                    'cbu' => '0123456789012345678901',
                    'alias' => 'MOZO.EMPRESA',
                ],
                'fees' => [
                    'percentage' => 0,
                    'fixed' => 0,
                ],
                'supported_currencies' => ['ARS'],
                'is_enabled' => true,
                'is_test_mode' => false,
                'sort_order' => 4,
                'min_amount' => 1.00,
                'max_amount' => null,
                'webhook_url' => null,
                'logo_url' => null,
                'color_primary' => '#28A745',
                'color_secondary' => '#155724',
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::updateOrCreate(
                ['slug' => $method['slug']], // Buscar por slug
                $method // Datos a crear/actualizar
            );
        }

        $this->command->info('Payment methods seeded successfully!');
    }
}
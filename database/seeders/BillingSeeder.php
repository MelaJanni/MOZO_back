<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class BillingSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'starter',
                'name' => 'Plan Básico',
                'description' => 'Perfecto para pequeños restaurantes',
                'billing_period' => 'monthly',
                'prices' => ['ARS' => 15000, 'USD' => 50],
                'default_currency' => 'ARS',
                'yearly_discount_percentage' => 20,
                'quarterly_discount_percentage' => 10,
                'trial_days' => 14,
                'trial_enabled' => true,
                'trial_requires_payment_method' => false,
                'limits' => [
                    'max_businesses' => 1,
                    'max_tables' => 10,
                    'max_staff' => 3,
                ],
                'features' => [
                    'Códigos QR personalizados',
                    'Menú digital',
                    'Notificaciones móviles',
                    'Soporte por email',
                ],
                'sort_order' => 1,
                'is_featured' => false,
                'is_popular' => false,
                'is_active' => true,
                'tax_percentage' => 21,
                'tax_inclusive' => true,
                'provider_plan_ids' => [
                    'mercadopago' => null,
                    'paypal' => null,
                ],
            ],
            [
                'code' => 'professional',
                'name' => 'Plan Profesional',
                'description' => 'Para restaurantes en crecimiento',
                'billing_period' => 'monthly',
                'prices' => ['ARS' => 25000, 'USD' => 80],
                'default_currency' => 'ARS',
                'yearly_discount_percentage' => 20,
                'quarterly_discount_percentage' => 10,
                'trial_days' => 14,
                'trial_enabled' => true,
                'trial_requires_payment_method' => false,
                'limits' => [
                    'max_businesses' => 2,
                    'max_tables' => 25,
                    'max_staff' => 8,
                ],
                'features' => [
                    'Todo lo del Plan Básico',
                    'Analytics avanzados',
                    'Múltiples ubicaciones',
                    'Integración con POS',
                    'Soporte telefónico',
                ],
                'sort_order' => 2,
                'is_featured' => true,
                'is_popular' => true,
                'is_active' => true,
                'tax_percentage' => 21,
                'tax_inclusive' => true,
                'provider_plan_ids' => [
                    'mercadopago' => null,
                    'paypal' => null,
                ],
            ],
            [
                'code' => 'enterprise',
                'name' => 'Plan Empresarial',
                'description' => 'Para cadenas y grandes restaurantes',
                'billing_period' => 'monthly',
                'prices' => ['ARS' => 50000, 'USD' => 150, 'EUR' => 140],
                'default_currency' => 'ARS',
                'yearly_discount_percentage' => 25,
                'quarterly_discount_percentage' => 15,
                'trial_days' => 30,
                'trial_enabled' => true,
                'trial_requires_payment_method' => true,
                'limits' => [
                    'max_businesses' => 10,
                    'max_tables' => 100,
                    'max_staff' => 50,
                ],
                'features' => [
                    'Todo lo del Plan Profesional',
                    'API personalizada',
                    'Reportes avanzados',
                    'Gestión de inventario',
                    'Soporte prioritario 24/7',
                    'Onboarding personalizado',
                ],
                'sort_order' => 3,
                'is_featured' => false,
                'is_popular' => false,
                'is_active' => true,
                'tax_percentage' => 21,
                'tax_inclusive' => true,
                'provider_plan_ids' => [
                    'mercadopago' => null,
                    'paypal' => null,
                ],
            ],
        ];

        foreach ($plans as $data) {
            Plan::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}

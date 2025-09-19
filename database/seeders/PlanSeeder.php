<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'STARTER',
                'name' => 'Plan Inicial',
                'description' => 'Perfecto para emprendedores que recién comienzan',
                'billing_period' => 'monthly',
                'price_cents' => 999, // $9.99
                'prices' => ['ARS' => 15000, 'USD' => 50],
                'default_currency' => 'ARS',
                'yearly_discount_percentage' => 15.00,
                'quarterly_discount_percentage' => 5.00,
                'trial_days' => 15,
                'trial_enabled' => true,
                'trial_requires_payment_method' => false,
                'limits' => [
                    'max_businesses' => 1,
                    'max_tables' => 10,
                    'max_staff' => 3,
                    'max_menus' => 5,
                    'max_qr_codes' => 10,
                ],
                'features' => [
                    'QR Code básico',
                    'Gestión de mesas',
                    'Llamadas de mozo',
                    'Menús digitales',
                    'Panel administrativo',
                    'Soporte por email',
                ],
                'sort_order' => 1,
                'is_featured' => false,
                'is_popular' => false,
                'is_active' => true,
                'tax_percentage' => 21.00,
                'tax_inclusive' => false,
                'metadata' => [
                    'target_audience' => 'Emprendedores',
                    'recommended_for' => 'Pequeños negocios',
                ],
                'provider_plan_ids' => [],
            ],
            [
                'code' => 'PROFESSIONAL',
                'name' => 'Plan Profesional',
                'description' => 'Para restaurantes establecidos que buscan crecer',
                'billing_period' => 'monthly',
                'price_cents' => 2999, // $29.99
                'prices' => ['ARS' => 45000, 'USD' => 150],
                'default_currency' => 'ARS',
                'yearly_discount_percentage' => 20.00,
                'quarterly_discount_percentage' => 10.00,
                'trial_days' => 30,
                'trial_enabled' => true,
                'trial_requires_payment_method' => true,
                'limits' => [
                    'max_businesses' => 3,
                    'max_tables' => 50,
                    'max_staff' => 15,
                    'max_menus' => 20,
                    'max_qr_codes' => 50,
                ],
                'features' => [
                    'Todo lo del plan Inicial',
                    'Múltiples negocios',
                    'Análisis y reportes',
                    'Integración con redes sociales',
                    'Personalización avanzada',
                    'Soporte telefónico',
                    'Gestión de inventario básica',
                ],
                'sort_order' => 2,
                'is_featured' => false,
                'is_popular' => true, // Plan más popular
                'is_active' => true,
                'tax_percentage' => 21.00,
                'tax_inclusive' => false,
                'metadata' => [
                    'target_audience' => 'Restaurantes medianos',
                    'recommended_for' => 'Negocios en crecimiento',
                ],
                'provider_plan_ids' => [],
            ],
            [
                'code' => 'ENTERPRISE',
                'name' => 'Plan Empresarial',
                'description' => 'Solución completa para cadenas y franquicias',
                'billing_period' => 'monthly',
                'price_cents' => 9999, // $99.99
                'prices' => ['ARS' => 150000, 'USD' => 500, 'EUR' => 450],
                'default_currency' => 'ARS',
                'yearly_discount_percentage' => 25.00,
                'quarterly_discount_percentage' => 15.00,
                'trial_days' => 45,
                'trial_enabled' => true,
                'trial_requires_payment_method' => true,
                'limits' => [
                    'max_businesses' => 999, // Ilimitado prácticamente
                    'max_tables' => 999,
                    'max_staff' => 999,
                    'max_menus' => 999,
                    'max_qr_codes' => 999,
                ],
                'features' => [
                    'Todo lo del plan Profesional',
                    'Negocios ilimitados',
                    'Dashboard ejecutivo',
                    'API personalizada',
                    'Integración con POS',
                    'Soporte 24/7',
                    'Gestión de inventario avanzada',
                    'Multi-idioma',
                    'White-label disponible',
                    'Consultor dedicado',
                ],
                'sort_order' => 3,
                'is_featured' => true, // Plan destacado
                'is_popular' => false,
                'is_active' => true,
                'tax_percentage' => 21.00,
                'tax_inclusive' => false,
                'metadata' => [
                    'target_audience' => 'Cadenas y franquicias',
                    'recommended_for' => 'Grandes empresas',
                    'includes_consultant' => true,
                ],
                'provider_plan_ids' => [],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['code' => $plan['code']], // Buscar por código
                $plan // Datos a crear/actualizar
            );
        }

        $this->command->info('Plans seeded successfully!');
    }
}

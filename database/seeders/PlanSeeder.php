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
                'code' => 'monthly',
                'name' => 'Mensual',
                'interval' => 'month',
                'price_cents' => 999,
                'currency' => 'USD',
                'trial_days' => 0,
                'is_active' => true,
            ],
            [
                'code' => 'annual',
                'name' => 'Anual',
                'interval' => 'year',
                'price_cents' => 9999,
                'currency' => 'USD',
                'trial_days' => 0,
                'is_active' => true,
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

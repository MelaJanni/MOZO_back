<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'type' => 'percent',
                'value' => 10,
                'max_redemptions' => 1000,
                'redeemed_count' => 0,
                'is_active' => true,
                'metadata' => ['seeded' => true],
            ],
            [
                'code' => 'OFF50',
                'type' => 'fixed',
                'value' => 5000, // $50.00
                'max_redemptions' => 500,
                'redeemed_count' => 0,
                'is_active' => true,
                'metadata' => ['seeded' => true],
            ],
            [
                'code' => 'FREEMONTH',
                'type' => 'free_time',
                'free_days' => 30,
                'max_redemptions' => 10000,
                'redeemed_count' => 0,
                'is_active' => true,
                'metadata' => ['seeded' => true],
            ],
        ];

        foreach ($coupons as $data) {
            Coupon::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}

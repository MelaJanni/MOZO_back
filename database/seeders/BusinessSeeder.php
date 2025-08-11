<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = [
            [
                'name' => 'McDonalds', 
                'industry' => 'Comida Rápida',
                'address' => 'Av. Corrientes 1234, CABA',
                'phone' => '+5491123456789',
                'email' => 'info@mcdonalds.com',
            ],
            [
                'name' => 'Starbucks', 
                'industry' => 'Cafetería',
                'address' => 'Av. Santa Fe 5678, CABA',
                'phone' => '+5491198765432',
                'email' => 'info@starbucks.com',
            ],
        ];

        foreach ($businesses as $businessData) {
            if (!Business::where('name', $businessData['name'])->exists()) {
                Business::create([
                    'name' => $businessData['name'],
                    'industry' => $businessData['industry'],
                    'address' => $businessData['address'],
                    'phone' => $businessData['phone'],
                    'email' => $businessData['email'],
                ]);
            }
        }
    }

}

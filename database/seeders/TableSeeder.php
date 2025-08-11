<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Table;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tablesData = [
            [
                'business_name' => 'McDonalds',
                'tables' => [
                    ['number' => 1, 'code' => 'JoA4vw', 'name' => 'Mesa 1'],
                    ['number' => 2, 'code' => 'K3mX9q', 'name' => 'Mesa 2'], 
                    ['number' => 3, 'code' => 'P8wT2n', 'name' => 'Mesa 3'],
                    ['number' => 4, 'code' => 'L7qR5m', 'name' => 'Mesa 4'],
                    ['number' => 5, 'code' => 'N9wE3x', 'name' => 'Mesa 5'],
                ]
            ],
            [
                'business_name' => 'Starbucks',
                'tables' => [
                    ['number' => 1, 'code' => 'A5xY7r', 'name' => 'Mesa 1'],
                    ['number' => 2, 'code' => 'B9mN4s', 'name' => 'Mesa 2'],
                    ['number' => 3, 'code' => 'C6tF8p', 'name' => 'Mesa 3'],
                ]
            ],
        ];

        foreach ($tablesData as $businessData) {
            $business = Business::where('name', $businessData['business_name'])->first();
            
            if ($business) {
                foreach ($businessData['tables'] as $tableData) {
                    if (!Table::where('business_id', $business->id)
                               ->where('number', $tableData['number'])
                               ->exists()) {
                        Table::create([
                            'business_id' => $business->id,
                            'number' => $tableData['number'],
                            'code' => $tableData['code'],
                            'name' => $tableData['name'],
                            'notifications_enabled' => true,
                        ]);
                    }
                }
            }
        }
    }
} 
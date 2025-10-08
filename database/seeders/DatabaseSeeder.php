<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        echo "🏗️  Inicializando datos base...\n\n";
        $this->call([
            RoleSeeder::class,
            BillingSeeder::class, // planes definitivos
            CouponSeeder::class,  // cupones base
            SystemSuperAdminSeeder::class, // Usuario admin del panel
            // DemoDataSeeder::class, // DESHABILITADO - No crear datos demo
        ]);
        echo "\n🎉 BASE DE DATOS LISTA - Solo super admin del panel\n";
    }
}
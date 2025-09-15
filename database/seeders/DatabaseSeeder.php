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
            SuperAdminSeeder::class, // asigna super_admin desde .env si corresponde
            DemoDataSeeder::class,   // datos demo: negocios, usuarios, mesas, suscripciones y pagos
        ]);
        echo "\n🎉 SEEDER COMPLETADO\n";
    }
}
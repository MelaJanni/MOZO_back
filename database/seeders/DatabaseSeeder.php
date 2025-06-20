<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BusinessSeeder::class,
            UserSeeder::class,
            StaffSeeder::class,
            ArchivedStaffSeeder::class,
            TableSeeder::class,
            MenuSeeder::class,
            // QrCodeSeeder::class, // Obsoleto
            ProfileSeeder::class,
            NotificationSeeder::class,
            ReviewSeeder::class,
            // GenerateQrsForExistingTablesSeeder::class, // Obsoleto gracias al TableObserver
        ]);
    }
}

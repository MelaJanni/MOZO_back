<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Cambiar ENUMs a VARCHAR(50) para permitir valores libres
        DB::statement("ALTER TABLE waiter_profiles MODIFY employment_type VARCHAR(50) NULL");
        DB::statement("ALTER TABLE waiter_profiles MODIFY current_schedule VARCHAR(50) NULL");
    }

    public function down(): void
    {
        // Restaurar ENUMs originales
        DB::statement("ALTER TABLE waiter_profiles MODIFY employment_type ENUM('employee','freelancer','contractor') NULL");
        DB::statement("ALTER TABLE waiter_profiles MODIFY current_schedule ENUM('morning','afternoon','night','mixed') NULL");
    }
};

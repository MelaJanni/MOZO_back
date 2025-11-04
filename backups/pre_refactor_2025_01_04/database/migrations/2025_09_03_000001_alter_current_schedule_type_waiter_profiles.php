<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cambiar enum -> VARCHAR(50) NULL para permitir rangos como "17-8" o descripciones libres
        DB::statement("ALTER TABLE `waiter_profiles` MODIFY `current_schedule` VARCHAR(50) NULL");
    }

    public function down(): void
    {
        // Revertir a un enum aproximado (mismo que el original de esta base)
        DB::statement("ALTER TABLE `waiter_profiles` MODIFY `current_schedule` ENUM('full_time','part_time','freelance','contract') DEFAULT 'part_time'");
    }
};

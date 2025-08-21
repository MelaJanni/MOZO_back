<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // Cambiar height de decimal(4,2) a decimal(5,2) para permitir valores como 175.5
            if (Schema::hasColumn('staff', 'height')) {
                $table->decimal('height', 5, 2)->nullable()->change();
            }
            
            // Asegurar que weight también tenga la precisión correcta
            if (Schema::hasColumn('staff', 'weight')) {
                $table->decimal('weight', 5, 2)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'height')) {
                $table->decimal('height', 4, 2)->nullable()->change();
            }
        });
    }
};
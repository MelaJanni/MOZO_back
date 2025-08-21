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
            // Hacer hire_date nullable ya que las solicitudes pendientes no tienen fecha de contratación
            if (Schema::hasColumn('staff', 'hire_date')) {
                $table->date('hire_date')->nullable()->change();
            }
            
            // Hacer position default 'Mozo' para solicitudes automáticas
            if (Schema::hasColumn('staff', 'position')) {
                $table->string('position')->default('Mozo')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'hire_date')) {
                $table->date('hire_date')->nullable(false)->change();
            }
            if (Schema::hasColumn('staff', 'position')) {
                $table->string('position')->nullable(false)->change();
            }
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Primero actualizar registros existentes sin provider
            DB::statement("UPDATE subscriptions SET provider = 'manual' WHERE provider IS NULL OR provider = ''");

            // Luego modificar la columna para tener valor por defecto
            $table->string('provider')->default('manual')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('provider')->nullable()->change();
        });
    }
};

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
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->json('working_hours')->nullable();
            $table->json('notification_preferences')->nullable();
            // Hacer que el campo code sea nullable para compatibilidad
            $table->string('code')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'address',
                'phone',
                'email',
                'logo',
                'working_hours',
                'notification_preferences'
            ]);
            // Restaurar la restricción de no nulo en code
            $table->string('code')->nullable(false)->change();
        });
    }
};

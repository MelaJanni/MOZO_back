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
        // Agrega índice único para garantizar un solo admin por negocio (estado actual del sistema)
        Schema::table('business_admins', function (Blueprint $table) {
            // Evitar fallo si ya existe (por seguridad en reruns) usando comprobación manual
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('business_admins');
            if (!array_key_exists('business_admins_business_id_unique_single', $indexes)) {
                $table->unique('business_id', 'business_admins_business_id_unique_single');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_admins', function (Blueprint $table) {
            // Elimina únicamente el índice que agregamos
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('business_admins');
            if (array_key_exists('business_admins_business_id_unique_single', $indexes)) {
                $table->dropUnique('business_admins_business_id_unique_single');
            }
        });
    }
};

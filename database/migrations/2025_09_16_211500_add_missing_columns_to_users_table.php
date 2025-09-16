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
        Schema::table('users', function (Blueprint $table) {
            // Verificar si la columna is_system_super_admin no existe antes de agregarla
            if (!Schema::hasColumn('users', 'is_system_super_admin')) {
                $table->boolean('is_system_super_admin')->default(false)->after('google_avatar');
            }

            // Verificar si la columna is_lifetime_paid no existe antes de agregarla
            if (!Schema::hasColumn('users', 'is_lifetime_paid')) {
                $table->boolean('is_lifetime_paid')->default(false)->after('is_system_super_admin');
            }

            // Verificar si la columna membership_expires_at no existe antes de agregarla
            if (!Schema::hasColumn('users', 'membership_expires_at')) {
                $table->timestamp('membership_expires_at')->nullable()->after('is_lifetime_paid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar columnas solo si existen
            $columnsToCheck = ['is_system_super_admin', 'is_lifetime_paid', 'membership_expires_at'];
            $columnsToRemove = [];

            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columnsToRemove[] = $column;
                }
            }

            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }
};
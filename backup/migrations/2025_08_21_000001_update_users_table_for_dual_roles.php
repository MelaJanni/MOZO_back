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
        Schema::table('users', function (Blueprint $table) {
            // Agregar campos faltantes si no existen
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'waiter'])->default('waiter')->after('password');
            }
            if (!Schema::hasColumn('users', 'active_business_id')) {
                $table->foreignId('active_business_id')->nullable()->after('role')->constrained('businesses')->onDelete('set null');
            }
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'active_business_id')) {
                $table->dropForeign(['active_business_id']);
                $table->dropColumn('active_business_id');
            }
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
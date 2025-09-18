<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Admin profiles: agregar columnas si faltan
        Schema::table('admin_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_profiles', 'business_name')) {
                $table->string('business_name')->nullable()->after('display_name');
            }
            if (!Schema::hasColumn('admin_profiles', 'office_extension')) {
                $table->string('office_extension')->nullable()->after('corporate_phone');
            }
            if (!Schema::hasColumn('admin_profiles', 'business_description')) {
                $table->text('business_description')->nullable()->after('office_extension');
            }
            if (!Schema::hasColumn('admin_profiles', 'business_website')) {
                $table->string('business_website')->nullable()->after('business_description');
            }
            if (!Schema::hasColumn('admin_profiles', 'social_media')) {
                $table->json('social_media')->nullable()->after('business_website');
            }
            if (!Schema::hasColumn('admin_profiles', 'permissions')) {
                $table->json('permissions')->nullable()->after('social_media');
            }
            if (!Schema::hasColumn('admin_profiles', 'is_primary_admin')) {
                $table->boolean('is_primary_admin')->default(false)->after('permissions');
            }
        });

        // Waiter profiles: asegurar tipos flexibles
        Schema::table('waiter_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('waiter_profiles', 'current_schedule')) {
                $table->string('current_schedule')->nullable()->after('experience_years');
            }
            // employment_type ya existe en muchas bases; si es enum incompatible, no lo tocamos aquí para evitar errores en diferentes motores.
            // Campos base ya deberían existir con la migración de creación; agregamos solo si faltaran
            if (!Schema::hasColumn('waiter_profiles', 'is_available_for_hire')) {
                $table->boolean('is_available_for_hire')->default(true)->after('skills');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_profiles', function (Blueprint $table) {
            foreach (['business_name','office_extension','business_description','business_website','social_media','permissions','is_primary_admin'] as $col) {
                if (Schema::hasColumn('admin_profiles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::table('waiter_profiles', function (Blueprint $table) {
            foreach (['current_schedule','is_available_for_hire'] as $col) {
                if (Schema::hasColumn('waiter_profiles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

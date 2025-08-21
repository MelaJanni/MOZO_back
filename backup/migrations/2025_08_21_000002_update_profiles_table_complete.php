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
        Schema::table('profiles', function (Blueprint $table) {
            // Datos básicos (ya existen en tabla original)
            if (!Schema::hasColumn('profiles', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('profiles', 'business_id')) {
                $table->foreignId('business_id')->nullable()->after('description')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('profiles', 'is_active')) {
                $table->boolean('is_active')->default(false)->after('business_id');
            }
            if (!Schema::hasColumn('profiles', 'activated_at')) {
                $table->timestamp('activated_at')->nullable()->after('is_active');
            }
            
            // Datos personales OBLIGATORIOS para mozos
            if (!Schema::hasColumn('profiles', 'phone')) {
                $table->string('phone')->nullable()->after('activated_at');
            }
            if (!Schema::hasColumn('profiles', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('profiles', 'bio')) {
                $table->text('bio')->nullable()->after('address');
            }
            if (!Schema::hasColumn('profiles', 'profile_picture')) {
                $table->string('profile_picture')->nullable()->after('bio');
            }
            
            // date_of_birth, gender, height, weight, skills, latitude, longitude ya existen en tabla original
            
            // Datos laborales OBLIGATORIOS para mozos
            if (!Schema::hasColumn('profiles', 'experience_years')) {
                $table->integer('experience_years')->nullable()->after('weight');
            }
            if (!Schema::hasColumn('profiles', 'employment_type')) {
                $table->string('employment_type')->nullable()->after('experience_years');
            }
            if (!Schema::hasColumn('profiles', 'current_schedule')) {
                $table->text('current_schedule')->nullable()->after('employment_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $columnsToCheck = [
                'phone', 'address', 'bio', 'profile_picture', 
                'experience_years', 'employment_type', 'current_schedule'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            if (Schema::hasColumn('profiles', 'business_id')) {
                $table->dropForeign(['business_id']);
                $table->dropColumn(['business_id', 'is_active', 'activated_at', 'description']);
            }
        });
    }
};
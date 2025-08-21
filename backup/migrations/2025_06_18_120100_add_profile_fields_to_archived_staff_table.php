<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('archived_staff', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('hire_date');
            $table->decimal('height', 4, 2)->nullable()->after('birth_date');
            $table->decimal('weight', 5, 2)->nullable()->after('height');
            $table->string('gender', 10)->nullable()->after('weight');
            $table->integer('experience_years')->nullable()->after('gender');
            $table->integer('seniority_years')->nullable()->after('experience_years');
            $table->string('education')->nullable()->after('seniority_years');
            $table->string('employment_type')->nullable()->after('education');
            $table->string('current_schedule')->nullable()->after('employment_type');
            $table->string('avatar_path')->nullable()->after('current_schedule');
        });
    }

    public function down(): void
    {
        Schema::table('archived_staff', function (Blueprint $table) {
            $table->dropColumn([
                'birth_date',
                'height',
                'weight',
                'gender',
                'experience_years',
                'seniority_years',
                'education',
                'employment_type',
                'current_schedule',
                'avatar_path',
            ]);
        });
    }
}; 
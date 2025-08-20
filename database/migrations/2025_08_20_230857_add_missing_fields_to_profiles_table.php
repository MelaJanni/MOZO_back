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
            // Datos personales del mozo
            $table->string('phone')->nullable()->after('description');
            $table->text('address')->nullable()->after('phone');
            $table->text('bio')->nullable()->after('address');
            $table->string('profile_picture')->nullable()->after('bio');
            $table->date('date_of_birth')->nullable()->after('profile_picture');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->decimal('height', 5, 2)->nullable()->after('gender');
            $table->decimal('weight', 5, 2)->nullable()->after('height');
            
            // Datos laborales
            $table->integer('experience_years')->nullable()->after('weight');
            $table->string('employment_type')->nullable()->after('experience_years'); // tiempo_completo, medio_tiempo, freelance
            $table->text('current_schedule')->nullable()->after('employment_type');
            $table->json('skills')->nullable()->after('current_schedule');
            
            // Ubicación
            $table->decimal('latitude', 10, 8)->nullable()->after('skills');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'address', 'bio', 'profile_picture', 'date_of_birth', 
                'gender', 'height', 'weight', 'experience_years', 'employment_type',
                'current_schedule', 'skills', 'latitude', 'longitude'
            ]);
        });
    }
};

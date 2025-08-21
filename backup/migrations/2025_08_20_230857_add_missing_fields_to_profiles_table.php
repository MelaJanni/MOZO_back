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
            // Datos personales del mozo (only new columns)
            $table->string('phone')->nullable()->after('activated_at');
            $table->text('address')->nullable()->after('phone');
            $table->text('bio')->nullable()->after('address');
            $table->string('profile_picture')->nullable()->after('bio');
            
            // Datos laborales (only new columns)
            $table->integer('experience_years')->nullable()->after('profile_picture');
            $table->string('employment_type')->nullable()->after('experience_years'); // tiempo_completo, medio_tiempo, freelance
            $table->text('current_schedule')->nullable()->after('employment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'address', 'bio', 'profile_picture', 'experience_years', 
                'employment_type', 'current_schedule'
            ]);
        });
    }
};

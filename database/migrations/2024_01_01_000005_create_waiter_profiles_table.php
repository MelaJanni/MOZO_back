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
        Schema::create('waiter_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            
            // Datos del perfil de mozo (GLOBALES - no por negocio)
            $table->string('avatar')->nullable();
            $table->string('display_name')->nullable();
            $table->text('bio')->nullable();
            $table->string('phone')->nullable();
            
            // Información personal
            $table->date('birth_date')->nullable();
            $table->decimal('height', 3, 2)->nullable();
            $table->integer('weight')->nullable();
            $table->enum('gender', ['masculino', 'femenino', 'otro'])->nullable();
            $table->integer('experience_years')->nullable()->default(0);
            
            // Ubicación y disponibilidad general
            $table->string('current_location')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('availability_hours')->nullable();
            $table->json('skills')->nullable();
            
            // Configuración del perfil
            $table->boolean('is_available_for_hire')->default(true);
            $table->boolean('is_available')->default(true);
            // current_schedule string libre (migraciones posteriores pueden ajustarla)
            $table->string('current_schedule')->nullable();
            $table->enum('employment_type', ['employee', 'freelancer', 'contractor'])->default('freelancer');
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('total_reviews')->default(0);
            
            $table->timestamps();
            
            $table->index(['is_available_for_hire']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiter_profiles');
    }
};
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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            
            // Datos del perfil de mozo
            $table->string('avatar')->nullable();
            $table->string('display_name')->nullable(); // Nombre que quiere mostrar como mozo
            $table->text('bio')->nullable();
            $table->string('phone')->nullable();
            
            // Información profesional
            $table->date('birth_date')->nullable();
            $table->decimal('height', 3, 2)->nullable(); // en metros
            $table->integer('weight')->nullable(); // en kg
            $table->enum('gender', ['masculino', 'femenino', 'otro'])->nullable();
            $table->integer('experience_years')->default(0);
            $table->enum('employment_type', ['por horas', 'tiempo completo', 'tiempo parcial', 'solo fines de semana'])->nullable();
            $table->enum('current_schedule', ['mañana', 'tarde', 'noche', 'mixto'])->nullable();
            
            // Ubicación y disponibilidad
            $table->string('current_location')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('availability_hours')->nullable(); // horarios disponibles
            $table->json('skills')->nullable(); // habilidades/especialidades
            
            // Configuración del perfil
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available')->default(true);
            $table->decimal('rating', 3, 2)->nullable(); // rating promedio
            $table->integer('total_reviews')->default(0);
            
            $table->timestamps();
            
            // Índices
            $table->unique(['user_id', 'business_id']);
            $table->index(['business_id', 'is_active', 'is_available']);
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

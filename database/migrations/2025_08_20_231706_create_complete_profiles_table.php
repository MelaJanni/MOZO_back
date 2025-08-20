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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(false);
            $table->timestamp('activated_at')->nullable();
            
            // Datos personales del mozo (SOLO EDITA EL MOZO)
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('bio')->nullable();
            $table->string('profile_picture')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            
            // Datos laborales (SOLO EDITA EL MOZO)
            $table->integer('experience_years')->nullable();
            $table->string('employment_type')->nullable(); // tiempo_completo, medio_tiempo, freelance
            $table->text('current_schedule')->nullable();
            $table->json('skills')->nullable();
            
            // Ubicación (SOLO EDITA EL MOZO)
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};

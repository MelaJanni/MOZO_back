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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Datos básicos
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'invited', 'rejected'])->default('pending');
            
            // Datos laborales - SOLO ADMIN PUEDE EDITAR
            $table->string('position')->default('Mozo'); // cargo
            $table->decimal('salary', 10, 2)->nullable(); // salario
            $table->text('notes')->nullable(); // notas del admin
            $table->date('hire_date')->nullable(); // fecha de contratación (automática al confirmar)
            
            // Datos personales automáticos desde Profile del User
            $table->date('birth_date')->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->string('gender')->nullable();
            $table->integer('experience_years')->nullable();
            $table->integer('seniority_years')->nullable();
            $table->string('education')->nullable();
            $table->string('employment_type')->nullable();
            $table->text('current_schedule')->nullable();
            $table->string('avatar_path')->nullable();
            
            // Sistema de invitaciones
            $table->string('invitation_token')->nullable();
            $table->timestamp('invitation_sent_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};

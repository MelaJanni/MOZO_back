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
        Schema::create('business_waiters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->enum('employment_status', ['active', 'inactive', 'suspended'])->default('active');
            $table->enum('employment_type', ['por horas', 'tiempo completo', 'tiempo parcial', 'solo fines de semana'])->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->json('work_schedule')->nullable(); // Horarios de trabajo
            $table->timestamp('hired_at')->useCurrent();
            $table->timestamp('last_shift_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'business_id']);
            $table->index(['business_id', 'employment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_waiters');
    }
};
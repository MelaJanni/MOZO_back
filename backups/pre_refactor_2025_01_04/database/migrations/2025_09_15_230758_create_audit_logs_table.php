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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event'); // created, updated, deleted, payment_processed, etc.
            $table->string('auditable_type'); // Modelo afectado
            $table->unsignedBigInteger('auditable_id'); // ID del modelo afectado
            $table->unsignedBigInteger('user_id')->nullable(); // Usuario que ejecutó la acción
            $table->json('old_values')->nullable(); // Valores anteriores
            $table->json('new_values')->nullable(); // Valores nuevos
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable(); // Información adicional
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id']);
            $table->index(['event']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

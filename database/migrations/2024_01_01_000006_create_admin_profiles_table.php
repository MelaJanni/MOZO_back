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
        Schema::create('admin_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            
            // Datos del perfil de admin (GLOBALES - no por negocio)
            $table->string('avatar')->nullable();
            $table->string('display_name')->nullable();
            $table->string('position')->nullable(); // Cargo general
            $table->string('corporate_email')->nullable();
            $table->string('corporate_phone')->nullable();
            $table->text('bio')->nullable();
            $table->timestamp('last_active_at')->nullable();
            
            // Configuración global de notificaciones
            $table->boolean('notify_new_orders')->default(true);
            $table->boolean('notify_staff_requests')->default(true);
            $table->boolean('notify_reviews')->default(true);
            $table->boolean('notify_payments')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_profiles');
    }
};
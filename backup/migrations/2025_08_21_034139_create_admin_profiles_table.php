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
            
            // Datos del perfil de administrador
            $table->string('avatar')->nullable();
            $table->string('display_name')->nullable(); // Nombre que quiere mostrar como admin
            $table->string('position')->default('Administrador');
            
            // Información corporativa personal
            $table->string('corporate_email')->nullable();
            $table->string('corporate_phone')->nullable();
            $table->string('office_extension')->nullable();
            $table->text('bio')->nullable();
            
            // Configuración del perfil
            $table->timestamp('last_active_at')->nullable();
            
            // Preferencias de notificaciones generales
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

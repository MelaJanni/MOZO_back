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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            
            // Datos del perfil de administrador
            $table->string('avatar')->nullable();
            $table->string('display_name')->nullable(); // Nombre que quiere mostrar como admin
            $table->string('business_name')->nullable(); // Nombre del negocio para este perfil
            $table->string('position')->default('Administrador'); // Cargo en el negocio
            
            // Información de contacto corporativa
            $table->string('corporate_email')->nullable();
            $table->string('corporate_phone')->nullable();
            $table->string('office_extension')->nullable();
            
            // Información del negocio
            $table->text('business_description')->nullable();
            $table->string('business_website')->nullable();
            $table->json('social_media')->nullable(); // redes sociales del negocio
            
            // Configuración del perfil
            $table->boolean('is_primary_admin')->default(false); // admin principal del negocio
            $table->json('permissions')->nullable(); // permisos específicos
            $table->timestamp('last_active_at')->nullable();
            
            // Preferencias de notificaciones
            $table->boolean('notify_new_orders')->default(true);
            $table->boolean('notify_staff_requests')->default(true);
            $table->boolean('notify_reviews')->default(true);
            $table->boolean('notify_payments')->default(true);
            
            $table->timestamps();
            
            // Índices
            $table->unique(['user_id', 'business_id']);
            $table->index(['business_id', 'is_primary_admin']);
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

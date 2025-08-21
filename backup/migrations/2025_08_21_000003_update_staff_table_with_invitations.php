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
        Schema::table('staff', function (Blueprint $table) {
            // Agregar user_id si no existe (conexión con usuario registrado)
            if (!Schema::hasColumn('staff', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('business_id')->constrained()->onDelete('set null');
            }
            
            // Sistema de invitaciones
            if (!Schema::hasColumn('staff', 'invitation_token')) {
                $table->string('invitation_token')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('staff', 'invitation_sent_at')) {
                $table->timestamp('invitation_sent_at')->nullable()->after('invitation_token');
            }
            
            // Actualizar status para incluir más estados
            if (Schema::hasColumn('staff', 'status')) {
                $table->string('status')->default('pending')->change();
            } else {
                $table->enum('status', ['pending', 'confirmed', 'invited', 'rejected'])->default('pending')->after('phone');
            }
            
            // Campos de perfil obligatorios (ya existen por migraciones anteriores)
            // birth_date, height, weight, gender, experience_years, employment_type, current_schedule, avatar_path
            
            // Asegurar campos opcionales
            if (!Schema::hasColumn('staff', 'seniority_years')) {
                $table->integer('seniority_years')->nullable()->after('experience_years');
            }
            if (!Schema::hasColumn('staff', 'education')) {
                $table->string('education')->nullable()->after('seniority_years');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn(['user_id', 'invitation_token', 'invitation_sent_at']);
            }
        });
    }
};
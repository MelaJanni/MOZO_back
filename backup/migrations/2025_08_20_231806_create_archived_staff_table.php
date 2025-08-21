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
        Schema::create('archived_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('archived_by')->constrained('users')->onDelete('cascade');
            
            // Datos básicos del staff archivado
            $table->string('name');
            $table->string('email');
            $table->string('position')->nullable();
            $table->json('original_data'); // Datos completos del staff original
            
            // Metadata del archivado
            $table->timestamp('archived_at');
            $table->string('archive_reason')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_staff');
    }
};

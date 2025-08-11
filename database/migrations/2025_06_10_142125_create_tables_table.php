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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->integer('number');
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            // Removemos la referencia a restaurant_id por ahora - se agregará después si es necesaria
            $table->unsignedBigInteger('restaurant_id')->nullable();
            $table->boolean('notifications_enabled')->default(false);
            $table->timestamps();
            
            // Número de mesa único por negocio
            $table->unique(['number', 'business_id']);
            // Código QR único por restaurante (sin restricción foreign key)
            $table->unique(['code', 'restaurant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};

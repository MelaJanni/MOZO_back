<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // MercadoPago, Stripe, PayPal, etc.
            $table->string('slug')->unique(); // mercadopago, stripe, paypal
            $table->text('description')->nullable();

            // Configuración y credenciales
            $table->json('config')->nullable(); // API keys, webhook URLs, etc.
            $table->json('fees')->nullable(); // {"percentage": 3.5, "fixed": 10.00}

            // Soporte de monedas
            $table->json('supported_currencies')->nullable(); // ["ARS", "USD"]

            // Estados y configuración
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_test_mode')->default(false);
            $table->integer('sort_order')->default(0);

            // Límites y restricciones
            $table->decimal('min_amount', 10, 2)->nullable();
            $table->decimal('max_amount', 10, 2)->nullable();

            // Configuración de webhooks
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();

            // Logo y UI
            $table->string('logo_url')->nullable();
            $table->string('color_primary')->nullable(); // #hexcolor
            $table->string('color_secondary')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods');

            // Identificadores externos (del procesador de pagos)
            $table->string('gateway_transaction_id')->nullable(); // ID en MercadoPago, Stripe, etc.
            $table->string('gateway_order_id')->nullable(); // Order ID del gateway
            $table->string('gateway_reference')->nullable(); // Referencia adicional

            // Datos de la transacción
            $table->decimal('amount', 10, 2); // Monto sin procesar
            $table->unsignedInteger('amount_cents'); // Monto en centavos para precisión
            $table->string('currency', 3)->default('ARS');

            // Comisiones y fees
            $table->decimal('gateway_fee', 10, 2)->default(0); // Comisión del gateway
            $table->decimal('platform_fee', 10, 2)->default(0); // Comisión nuestra
            $table->decimal('net_amount', 10, 2); // Monto neto que recibimos

            // Estados de la transacción
            $table->enum('status', [
                'pending',      // Pendiente de procesar
                'processing',   // En proceso
                'completed',    // Completada exitosamente
                'failed',       // Falló
                'canceled',     // Cancelada
                'refunded',     // Reembolsada
                'partially_refunded' // Reembolso parcial
            ])->default('pending');

            // Tipo de transacción
            $table->enum('type', [
                'payment',          // Pago normal
                'refund',          // Reembolso
                'partial_refund',  // Reembolso parcial
                'chargeback',      // Contracargo
                'fee'              // Comisión
            ])->default('payment');

            // Metadatos del gateway
            $table->json('gateway_response')->nullable(); // Respuesta completa del gateway
            $table->json('gateway_metadata')->nullable(); // Metadata adicional

            // Datos del cliente al momento del pago
            $table->string('customer_email')->nullable();
            $table->json('customer_data')->nullable(); // Datos adicionales del cliente

            // Información de tarjeta (si aplica)
            $table->string('card_last_four')->nullable();
            $table->string('card_brand')->nullable(); // visa, mastercard, etc.
            $table->string('card_country')->nullable();

            // Fechas importantes
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            // Descripción y notas
            $table->text('description')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('internal_notes')->nullable();

            // Webhooks
            $table->json('webhooks_received')->nullable(); // Log de webhooks recibidos
            $table->timestamp('last_webhook_at')->nullable();

            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['user_id', 'status']);
            $table->index(['gateway_transaction_id']);
            $table->index(['status', 'type']);
            $table->index(['processed_at']);
            $table->unique(['gateway_transaction_id', 'payment_method_id'], 'unique_gateway_transaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

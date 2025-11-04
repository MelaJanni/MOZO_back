<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();

            // Numeración AFIP (secuencial por punto de venta)
            $table->string('point_of_sale', 4)->default('0001'); // 0001, 0002, etc.
            $table->string('invoice_number', 8); // 00000001, 00000002, etc.
            $table->string('full_number', 13)->unique(); // 0001-00000001
            $table->enum('invoice_type', ['B', 'C'])->default('B'); // Tipo B o C

            // AFIP - Facturación Electrónica
            $table->string('cae', 14)->nullable(); // Código Autorización Electrónico
            $table->date('cae_expiration')->nullable();
            $table->json('afip_response')->nullable(); // Respuesta completa de AFIP
            $table->string('afip_request_id')->nullable(); // ID del request a AFIP

            // Datos del cliente
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_cuit', 13)->nullable(); // CUIT del cliente
            $table->text('customer_address')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_state')->nullable();
            $table->string('customer_zip_code')->nullable();
            $table->string('customer_country', 3)->default('ARG');

            // Datos de la empresa (emisor)
            $table->string('company_name')->default('Tu Empresa SRL');
            $table->string('company_cuit', 13);
            $table->text('company_address');
            $table->string('company_city');
            $table->string('company_state');
            $table->string('company_zip_code');

            // Conceptos facturados
            $table->json('line_items'); // Array de items facturados
            $table->text('description')->nullable(); // Descripción general

            // Montos (en centavos para precisión)
            $table->unsignedInteger('subtotal_cents'); // Sin impuestos
            $table->unsignedInteger('tax_cents'); // Impuestos (IVA)
            $table->unsignedInteger('total_cents'); // Total final
            $table->string('currency', 3)->default('ARS');

            // Porcentajes aplicados
            $table->decimal('tax_percentage', 5, 2)->default(21.00); // IVA
            $table->decimal('discount_percentage', 5, 2)->default(0);

            // Estados
            $table->enum('status', ['draft', 'sent', 'paid', 'canceled', 'refunded'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('due_date')->nullable();

            // Archivos
            $table->string('pdf_path')->nullable(); // Ruta del PDF generado
            $table->string('xml_path')->nullable(); // Ruta del XML para AFIP

            // Notas y observaciones
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable(); // Solo para admin

            $table->timestamps();

            // Índices
            $table->index(['customer_email', 'status']);
            $table->index(['point_of_sale', 'invoice_number']);
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

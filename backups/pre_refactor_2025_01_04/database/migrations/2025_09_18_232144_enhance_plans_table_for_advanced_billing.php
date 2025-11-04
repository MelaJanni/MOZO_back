<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Agregar descripción detallada
            $table->text('description')->nullable()->after('name');

            // Soporte multimoneda y precios más flexibles
            $table->decimal('price_ars', 10, 2)->nullable()->after('price_cents');
            $table->decimal('price_usd', 10, 2)->nullable()->after('price_ars');

            // Períodos de facturación más flexibles
            $table->enum('billing_period', ['monthly', 'quarterly', 'yearly'])->default('monthly')->after('price_usd');

            // Descuentos por períodos largos
            $table->decimal('yearly_discount_percentage', 5, 2)->default(0)->after('billing_period');
            $table->decimal('quarterly_discount_percentage', 5, 2)->default(0)->after('yearly_discount_percentage');

            // Trial configuration más avanzada
            $table->boolean('trial_enabled')->default(false)->after('trial_days');
            $table->boolean('trial_requires_payment_method')->default(true)->after('trial_enabled');

            // Límites y features configurables
            $table->json('limits')->nullable()->after('trial_requires_payment_method'); // max_businesses, max_tables, max_staff
            $table->json('features')->nullable()->after('limits'); // analytics, api_access, etc.

            // Display y ordenamiento
            $table->integer('sort_order')->default(0)->after('features');
            $table->boolean('is_featured')->default(false)->after('sort_order');
            $table->boolean('is_popular')->default(false)->after('is_featured');

            // Configuración de impuestos
            $table->decimal('tax_percentage', 5, 2)->default(21.00)->after('is_popular'); // IVA Argentina
            $table->boolean('tax_inclusive')->default(false)->after('tax_percentage');

            // Mantener price_cents por compatibilidad pero hacer nullable
            $table->unsignedInteger('price_cents')->nullable()->change();
        });

        // Eliminar columna interval que será reemplazada por billing_period
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('interval');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'price_ars',
                'price_usd',
                'billing_period',
                'yearly_discount_percentage',
                'quarterly_discount_percentage',
                'trial_enabled',
                'trial_requires_payment_method',
                'limits',
                'features',
                'sort_order',
                'is_featured',
                'is_popular',
                'tax_percentage',
                'tax_inclusive'
            ]);

            $table->enum('interval', ['monthly', 'yearly'])->after('name');
            $table->unsignedInteger('price_cents')->nullable(false)->change();
        });
    }
};

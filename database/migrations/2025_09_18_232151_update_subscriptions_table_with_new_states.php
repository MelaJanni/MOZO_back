<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Actualizar los estados de suscripción
            $table->dropColumn('status');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->enum('status', [
                'pending',     // Pendiente de pago
                'active',      // Activa y funcionando
                'canceled',    // Cancelada por usuario
                'expired',     // Expirada sin renovación
                'suspended'    // Suspendida por admin/fallo de pago
            ])->default('pending')->after('provider_subscription_id');

            // Campos adicionales para manejo avanzado
            $table->timestamp('suspended_at')->nullable()->after('trial_ends_at');
            $table->text('suspension_reason')->nullable()->after('suspended_at');

            // Período de gracia
            $table->timestamp('grace_period_ends_at')->nullable()->after('suspension_reason');

            // Tracking de upgrades/downgrades
            $table->foreignId('previous_plan_id')->nullable()->constrained('plans')->after('plan_id');
            $table->timestamp('plan_changed_at')->nullable()->after('previous_plan_id');

            // Datos de facturación
            $table->decimal('amount_paid', 10, 2)->nullable()->after('auto_renew');
            $table->string('currency', 3)->default('ARS')->after('amount_paid');

            // Upgrade prorrateado
            $table->decimal('proration_amount', 10, 2)->nullable()->after('currency');
            $table->json('proration_details')->nullable()->after('proration_amount');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'suspended_at',
                'suspension_reason',
                'grace_period_ends_at',
                'previous_plan_id',
                'plan_changed_at',
                'amount_paid',
                'currency',
                'proration_amount',
                'proration_details'
            ]);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->enum('status', ['active', 'in_trial', 'past_due', 'canceled', 'on_hold'])
                  ->default('in_trial')
                  ->after('provider_subscription_id');
        });
    }
};

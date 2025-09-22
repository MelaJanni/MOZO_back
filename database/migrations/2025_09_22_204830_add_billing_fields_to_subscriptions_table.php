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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('billing_period')->nullable()->after('status');
            $table->decimal('price_at_creation', 10, 2)->nullable()->after('billing_period');
            $table->timestamp('next_billing_date')->nullable()->after('current_period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['billing_period', 'price_at_creation', 'next_billing_date']);
        });
    }
};

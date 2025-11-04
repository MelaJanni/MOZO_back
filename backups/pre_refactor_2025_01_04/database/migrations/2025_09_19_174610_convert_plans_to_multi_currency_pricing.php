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
        Schema::table('plans', function (Blueprint $table) {
            // Add new prices JSON column
            $table->json('prices')->nullable()->after('billing_period');

            // Set default currency
            $table->string('default_currency', 3)->default('ARS')->after('prices');
        });

        // Migrate existing data
        \DB::table('plans')->get()->each(function ($plan) {
            $prices = [];

            if ($plan->price_ars) {
                $prices['ARS'] = (float) $plan->price_ars;
            }

            if ($plan->price_usd) {
                $prices['USD'] = (float) $plan->price_usd;
            }

            // If no prices set, add default ARS price of 0
            if (empty($prices)) {
                $prices['ARS'] = 0;
            }

            \DB::table('plans')
                ->where('id', $plan->id)
                ->update([
                    'prices' => json_encode($prices),
                    'default_currency' => 'ARS'
                ]);
        });

        // Remove old columns
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['price_ars', 'price_usd']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Add back old columns
            $table->decimal('price_ars', 10, 2)->nullable()->after('billing_period');
            $table->decimal('price_usd', 10, 2)->nullable()->after('price_ars');
        });

        // Migrate data back
        \DB::table('plans')->get()->each(function ($plan) {
            $prices = json_decode($plan->prices, true) ?? [];

            \DB::table('plans')
                ->where('id', $plan->id)
                ->update([
                    'price_ars' => $prices['ARS'] ?? null,
                    'price_usd' => $prices['USD'] ?? null,
                ]);
        });

        // Remove new columns
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['prices', 'default_currency']);
        });
    }
};

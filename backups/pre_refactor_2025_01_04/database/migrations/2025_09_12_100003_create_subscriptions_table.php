<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans');
            $table->string('provider'); // mp|paypal|offline
            $table->string('provider_subscription_id')->nullable();
            $table->enum('status', ['active', 'in_trial', 'past_due', 'canceled', 'on_hold'])->default('in_trial');
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

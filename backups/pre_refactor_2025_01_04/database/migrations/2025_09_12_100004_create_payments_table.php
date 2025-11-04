<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_payment_id')->nullable();
            $table->integer('amount_cents');
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['paid', 'pending', 'failed', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['provider', 'provider_payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

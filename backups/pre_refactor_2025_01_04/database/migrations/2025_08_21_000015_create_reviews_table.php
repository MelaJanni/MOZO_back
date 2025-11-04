<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('table_id')->nullable()->constrained('tables')->onDelete('set null');
                $table->foreignId('staff_id')->nullable()->constrained('staff')->onDelete('set null');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->string('customer_name')->nullable();
                $table->string('customer_email')->nullable();
                $table->unsignedTinyInteger('rating')->default(5);
                $table->text('comment')->nullable();
                $table->json('service_details')->nullable();
                $table->boolean('is_approved')->default(false);
                $table->boolean('is_featured')->default(false);
                $table->timestamps();

                $table->index(['business_id', 'staff_id']);
                $table->index(['business_id', 'table_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};

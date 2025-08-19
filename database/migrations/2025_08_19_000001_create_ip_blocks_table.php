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
        Schema::create('ip_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45); // Soporte para IPv4 e IPv6
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('blocked_by')->constrained('users')->onDelete('cascade');
            $table->enum('reason', ['spam', 'abuse', 'manual'])->default('manual');
            $table->text('notes')->nullable();
            $table->timestamp('blocked_at');
            $table->timestamp('expires_at')->nullable(); // null = permanente
            $table->timestamp('unblocked_at')->nullable();
            $table->json('metadata')->nullable(); // Info adicional como user agent, etc.
            $table->timestamps();
            
            $table->unique(['ip_address', 'business_id'], 'unique_ip_per_business');
            $table->index(['ip_address', 'blocked_at']);
            $table->index(['business_id', 'blocked_at']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_blocks');
    }
};
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
        Schema::create('table_silences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained()->onDelete('cascade');
            $table->foreignId('silenced_by')->nullable()->constrained('users')->onDelete('set null'); // Mozo que silenció
            $table->enum('reason', ['automatic', 'manual']); // Automático por spam o manual por mozo
            $table->timestamp('silenced_at');
            $table->timestamp('unsilenced_at')->nullable();
            $table->integer('call_count')->default(0); // Número de llamadas que causaron el silencio
            $table->text('notes')->nullable(); // Notas del mozo
            $table->timestamps();
            
            $table->index(['table_id', 'silenced_at', 'unsilenced_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_silences');
    }
};
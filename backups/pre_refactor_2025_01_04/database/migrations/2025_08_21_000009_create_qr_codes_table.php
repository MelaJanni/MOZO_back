<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('url');
            $table->timestamps();
            $table->index(['table_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};

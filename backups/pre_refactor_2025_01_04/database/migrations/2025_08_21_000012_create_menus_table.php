<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('menus')) {
            Schema::create('menus', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->string('file_path');
                $table->boolean('is_default')->default(false);
                $table->integer('display_order')->default(0);
                $table->timestamps();

                $table->index(['business_id', 'is_default']);
                $table->index(['business_id', 'display_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};

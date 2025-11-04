<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('table_profile_table', function (Blueprint $table) {
            $table->unsignedBigInteger('table_profile_id');
            $table->unsignedBigInteger('table_id');
            $table->timestamps();

            $table->primary(['table_profile_id', 'table_id']);
            $table->foreign('table_profile_id')->references('id')->on('table_profiles')->onDelete('cascade');
            $table->foreign('table_id')->references('id')->on('tables')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_profile_table');
    }
};

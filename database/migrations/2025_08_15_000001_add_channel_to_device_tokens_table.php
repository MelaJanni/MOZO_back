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
        Schema::table('device_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('device_tokens', 'channel')) {
                $table->string('channel')->nullable()->after('platform');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('device_tokens', 'channel')) {
                $table->dropColumn('channel');
            }
        });
    }
};

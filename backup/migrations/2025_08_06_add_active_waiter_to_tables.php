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
        Schema::table('tables', function (Blueprint $table) {
            $table->foreignId('active_waiter_id')->nullable()->after('notifications_enabled')->constrained('users')->onDelete('set null');
            $table->timestamp('waiter_assigned_at')->nullable()->after('active_waiter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropForeign(['active_waiter_id']);
            $table->dropColumn(['active_waiter_id', 'waiter_assigned_at']);
        });
    }
};
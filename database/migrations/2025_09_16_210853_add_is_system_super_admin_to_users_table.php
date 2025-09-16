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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_system_super_admin')->default(false)->after('google_avatar');
            $table->boolean('is_lifetime_paid')->default(false)->after('is_system_super_admin');
            $table->timestamp('membership_expires_at')->nullable()->after('is_lifetime_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_system_super_admin', 'is_lifetime_paid', 'membership_expires_at']);
        });
    }
};

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
        Schema::table('business_user', function (Blueprint $table) {
            $table->timestamp('joined_at')->nullable()->after('business_id');
            $table->string('status')->default('active')->after('joined_at');
            $table->string('role')->default('waiter')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_user', function (Blueprint $table) {
            $table->dropColumn(['joined_at', 'status', 'role']);
        });
    }
};
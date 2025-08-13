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
        Schema::table('profiles', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->foreignId('business_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(false)->after('description');
            $table->timestamp('activated_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropColumn(['description', 'business_id', 'is_active', 'activated_at']);
        });
    }
};

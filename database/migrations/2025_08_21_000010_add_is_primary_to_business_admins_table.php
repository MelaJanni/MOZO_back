<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('business_admins', function (Blueprint $table) {
            if (!Schema::hasColumn('business_admins', 'is_primary')) {
                $table->boolean('is_primary')->default(true)->after('is_active');
            }
        });
        if (Schema::hasColumn('business_admins', 'is_primary')) {
            DB::table('business_admins')->update(['is_primary' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('business_admins', function (Blueprint $table) {
            if (Schema::hasColumn('business_admins', 'is_primary')) {
                $table->dropColumn('is_primary');
            }
        });
    }
};

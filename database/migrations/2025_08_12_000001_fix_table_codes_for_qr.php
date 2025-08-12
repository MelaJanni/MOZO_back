<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update table with ID 1 (Mesa 1 de McDonalds) to have the QR code
        DB::table('tables')
            ->where('id', 1)
            ->where('business_id', 1)
            ->update([
                'code' => 'JoA4vw',
                'name' => 'Mesa 1',
                'updated_at' => now()
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the changes
        DB::table('tables')
            ->where('id', 1)
            ->where('business_id', 1)
            ->update([
                'code' => null,
                'name' => null,
                'updated_at' => now()
            ]);
    }
};
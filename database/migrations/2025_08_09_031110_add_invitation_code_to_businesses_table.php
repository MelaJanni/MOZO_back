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
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('invitation_code', 50)->unique()->nullable()->after('code');
        });

        // Generar códigos de invitación para negocios existentes
        $businesses = DB::table('businesses')->whereNull('invitation_code')->get();
        foreach ($businesses as $business) {
            $invitationCode = 'BIZ' . str_pad($business->id, 4, '0', STR_PAD_LEFT);
            DB::table('businesses')
                ->where('id', $business->id)
                ->update(['invitation_code' => $invitationCode]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('invitation_code');
        });
    }
};

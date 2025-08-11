<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Business;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing businesses to have proper codes for QR functionality
        $businesses = [
            'McDonalds' => 'mcdonalds',
            'Starbucks' => 'starbucks'
        ];

        foreach ($businesses as $name => $code) {
            Business::where('name', $name)
                    ->whereNull('code')
                    ->orWhere('code', '')
                    ->update(['code' => $code]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset codes to null
        Business::whereIn('code', ['mcdonalds', 'starbucks'])
                ->update(['code' => null]);
    }
};
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Business;
use App\Models\Table;

class QrWebController extends Controller
{
    public function showTablePage($restaurantSlug, $tableCode)
    {
        // Debug: Log what we're looking for
        \Log::info('QR Page Request', [
            'restaurantSlug' => $restaurantSlug,
            'tableCode' => $tableCode
        ]);

        // Buscar negocio por múltiples criterios
        $business = Business::where(function($query) use ($restaurantSlug) {
            // Exact name match (case insensitive)
            $query->whereRaw('LOWER(name) = ?', [strtolower($restaurantSlug)])
                  // Name without spaces (case insensitive) 
                  ->orWhereRaw('LOWER(REPLACE(name, " ", "")) = ?', [strtolower(str_replace(' ', '', $restaurantSlug))])
                  // Code match
                  ->orWhere('code', $restaurantSlug);
        })->first();
        
        \Log::info('Business Search Result', [
            'found' => $business ? 'yes' : 'no',
            'business_id' => $business ? $business->id : null,
            'business_name' => $business ? $business->name : null
        ]);
        
        if (!$business) {
            // Debug: Show available businesses
            $availableBusinesses = Business::select('id', 'name', 'code')->get();
            \Log::error('Business not found', [
                'searched_for' => $restaurantSlug,
                'available_businesses' => $availableBusinesses->toArray()
            ]);
            abort(404, 'Business not found: ' . $restaurantSlug);
        }

        // Buscar mesa por código
        $table = Table::where('code', $tableCode)
                     ->where('business_id', $business->id)
                     ->first();
        
        \Log::info('Table Search Result', [
            'found' => $table ? 'yes' : 'no',
            'table_id' => $table ? $table->id : null,
            'table_number' => $table ? $table->number : null
        ]);
        
        if (!$table) {
            // Debug: Show available tables for this business
            $availableTables = Table::where('business_id', $business->id)
                                   ->select('id', 'number', 'code', 'name')
                                   ->get();
            \Log::error('Table not found', [
                'searched_for' => $tableCode,
                'business_id' => $business->id,
                'business_name' => $business->name,
                'available_tables' => $availableTables->toArray()
            ]);
            abort(404, 'Table not found: ' . $tableCode . ' for business: ' . $business->name);
        }

        // Obtener URL del frontend desde configuración
        $frontendUrl = config('app.frontend_url', 'https://mozoqr.com');
        
        return view('qr.table-page', compact('business', 'table', 'frontendUrl'));
    }

    public function testQr()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'QR System is working!',
            'timestamp' => now()->toISOString()
        ]);
    }

    public function debugData()
    {
        $businesses = Business::all(['id', 'name', 'code', 'invitation_code']);
        $tables = Table::all(['id', 'business_id', 'number', 'code', 'name']);
        
        return response()->json([
            'businesses' => $businesses,
            'tables' => $tables,
            'test_lookup' => [
                'mcdonalds_business' => Business::where('name', 'McDonalds')->orWhere('code', 'mcdonalds')->first(),
                'table_JoA4vw' => Table::where('code', 'JoA4vw')->first()
            ]
        ]);
    }

    public function setupTestData()
    {
        try {
            // Create or update McDonalds
            $mcdonalds = Business::updateOrCreate(
                ['name' => 'McDonalds'],
                [
                    'code' => 'mcdonalds',
                    'industry' => 'Comida Rápida',
                    'address' => 'Av. Corrientes 1234, CABA',
                    'phone' => '+5491123456789',
                    'email' => 'info@mcdonalds.com',
                    'menu_pdf' => 'menus/mcdonalds-menu.html',
                ]
            );

            // Create test table
            $table = Table::updateOrCreate(
                ['business_id' => $mcdonalds->id, 'code' => 'JoA4vw'],
                [
                    'number' => 1,
                    'name' => 'Mesa 1',
                    'notifications_enabled' => true,
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Test data created successfully',
                'business' => $mcdonalds,
                'table' => $table,
                'qr_url' => url("/QR/mcdonalds/JoA4vw")
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
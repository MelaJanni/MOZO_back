<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Business;
use App\Models\Table;

class QrWebController extends Controller
{
    public function showTablePage($restaurantSlug, $tableCode)
    {
        // Buscar negocio por slug
        $business = Business::whereRaw('LOWER(REPLACE(name, " ", "")) = ?', [strtolower(str_replace(' ', '', $restaurantSlug))])
                           ->orWhere('code', $restaurantSlug)
                           ->first();
        
        if (!$business) {
            abort(404, 'Business not found');
        }

        // Buscar mesa por código
        $table = Table::where('code', $tableCode)
                     ->where('business_id', $business->id)
                     ->first();
        
        if (!$table) {
            abort(404, 'Table not found');
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
}
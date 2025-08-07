<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\Table;

class QrWebController extends Controller
{
    public function showTablePage($restaurantSlug, $tableCode)
    {
        // Buscar restaurante por slug
        $restaurant = Restaurant::where('slug', $restaurantSlug)->first();
        
        if (!$restaurant) {
            abort(404, 'Restaurant not found');
        }

        // Buscar mesa por código
        $table = Table::where('code', $tableCode)
                     ->where('restaurant_id', $restaurant->id)
                     ->first();
        
        if (!$table) {
            abort(404, 'Table not found');
        }

        // Obtener URL del frontend desde configuración
        $frontendUrl = config('app.frontend_url', 'https://mozoqr.com');
        
        return view('qr.table-page', compact('restaurant', 'table', 'frontendUrl'));
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
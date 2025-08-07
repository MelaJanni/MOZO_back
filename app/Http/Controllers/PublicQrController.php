<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\Table;

class PublicQrController extends Controller
{
    public function getTableInfo($restaurantSlug, $tableCode)
    {
        // Buscar restaurante por slug
        $restaurant = Restaurant::where('slug', $restaurantSlug)->first();
        
        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found'
            ], 404);
        }

        // Buscar mesa por código
        $table = Table::where('code', $tableCode)
                     ->where('restaurant_id', $restaurant->id)
                     ->first();
        
        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'restaurant' => [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'slug' => $restaurant->slug,
                    'logo' => $restaurant->logo,
                    'menu_pdf' => $restaurant->menu_pdf
                ],
                'table' => [
                    'id' => $table->id,
                    'name' => $table->name,
                    'code' => $table->code,
                    'number' => $table->number
                ]
            ]
        ]);
    }
}
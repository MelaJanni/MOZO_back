<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\Table;
use App\Models\Business;
use App\Models\Menu;
use App\Models\WaiterCall;

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
                     ->with(['activeWaiter', 'business'])
                     ->first();
        
        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found'
            ], 404);
        }

        // Buscar menú del negocio (si existe)
        $menu = null;
        if ($table->business_id) {
            $menu = Menu::where('business_id', $table->business_id)
                       ->where('is_default', true)
                       ->first() 
                    ?? Menu::where('business_id', $table->business_id)->first();
        }

        // Buscar llamada pendiente actual
        $pendingCall = WaiterCall::where('table_id', $table->id)
                                ->whereIn('status', ['pending', 'acknowledged'])
                                ->orderBy('called_at', 'desc')
                                ->first();

        $business = $table->business;

        return response()->json([
            'success' => true,
            'data' => [
                'table' => [
                    'id' => $table->id,
                    'name' => $table->name,
                    'code' => $table->code,
                    'number' => $table->number,
                    'can_call_waiter' => $table->notifications_enabled && 
                                       !is_null($table->active_waiter_id) && 
                                       !$table->isSilenced()
                ],
                'business' => $business ? [
                    'id' => $business->id,
                    'name' => $business->name,
                    'address' => $business->address,
                    'phone' => $business->phone,
                    'logo' => $business->logo
                ] : null,
                'restaurant' => [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'slug' => $restaurant->slug,
                    'logo' => $restaurant->logo,
                    'menu_pdf' => $restaurant->menu_pdf
                ],
                'menu' => $menu ? [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'file_path' => $menu->file_path,
                    'download_url' => url('/api/menus/' . $menu->id . '/download')
                ] : null,
                'waiter' => $table->activeWaiter ? [
                    'id' => $table->activeWaiter->id,
                    'name' => $table->activeWaiter->name
                ] : null,
                'pending_call' => $pendingCall ? [
                    'id' => $pendingCall->id,
                    'status' => $pendingCall->status,
                    'called_at' => $pendingCall->called_at,
                    'acknowledged_at' => $pendingCall->acknowledged_at,
                    'minutes_ago' => $pendingCall->called_at->diffInMinutes(now())
                ] : null,
                // Firebase configuration for real-time updates
                'firebase_config' => [
                    'enabled' => true,
                    'table_calls_path' => "tables/{$table->id}/waiter_calls",
                    'table_status_path' => "tables/{$table->id}/status/current"
                ]
            ]
        ]);
    }

    /**
     * Get current table status for polling (fallback if Firebase fails)
     */
    public function getTableStatus($tableId)
    {
        $table = Table::with(['activeWaiter', 'business'])
                     ->find($tableId);
        
        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found'
            ], 404);
        }

        // Buscar llamada activa
        $activeCall = WaiterCall::where('table_id', $table->id)
                               ->whereIn('status', ['pending', 'acknowledged'])
                               ->orderBy('called_at', 'desc')
                               ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'table_id' => $table->id,
                'can_call_waiter' => $table->notifications_enabled && 
                                   !is_null($table->active_waiter_id) && 
                                   !$table->isSilenced(),
                'active_call' => $activeCall ? [
                    'id' => $activeCall->id,
                    'status' => $activeCall->status,
                    'called_at' => $activeCall->called_at,
                    'acknowledged_at' => $activeCall->acknowledged_at,
                    'minutes_ago' => $activeCall->called_at->diffInMinutes(now())
                ] : null,
                'waiter' => $table->activeWaiter ? [
                    'name' => $table->activeWaiter->name
                ] : null,
                'is_silenced' => $table->isSilenced()
            ]
        ]);
    }
}
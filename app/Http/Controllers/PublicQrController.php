<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Business;
use App\Models\WaiterCall;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Hashids\Hashids;

class PublicQrController extends Controller
{
    /**
     * Resolver QR code y obtener información de mesa y menú
     */
    public function resolveQrCode(Request $request, string $businessSlug, string $tableHash): JsonResponse
    {
        try {
            // Encontrar business por slug
            $business = Business::whereRaw('LOWER(REPLACE(name, " ", "-")) = ?', [strtolower($businessSlug)])->first();
            
            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Negocio no encontrado'
                ], 404);
            }

            // Decodificar hash de mesa
            $hashids = new Hashids(config('app.key'), 6);
            $decodedIds = $hashids->decode($tableHash);
            
            if (empty($decodedIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Código QR inválido'
                ], 404);
            }

            $tableId = $decodedIds[0];

            // Buscar la mesa
            $table = Table::with(['business', 'activeWaiter'])
                ->where('id', $tableId)
                ->where('business_id', $business->id)
                ->first();

            if (!$table) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mesa no encontrada'
                ], 404);
            }

            // Obtener menú por defecto del negocio
            $defaultMenu = $business->menus()
                ->where('is_default', true)
                ->first();

            // Si no hay menú por defecto, tomar el primero ordenado
            if (!$defaultMenu) {
                $defaultMenu = $business->menus()
                    ->orderBy('display_order')
                    ->first();
            }

            // Verificar si la mesa puede recibir llamadas
            $canCallWaiter = $table->canReceiveCalls();
            $waiterInfo = null;
            
            if ($table->active_waiter_id) {
                $waiterInfo = [
                    'id' => $table->activeWaiter->id,
                    'name' => $table->activeWaiter->name
                ];
            }

            // Verificar si hay llamadas pendientes
            $pendingCall = $table->pendingCalls()
                ->latest('called_at')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'table' => [
                        'id' => $table->id,
                        'number' => $table->number,
                        'name' => $table->name,
                        'can_call_waiter' => $canCallWaiter
                    ],
                    'business' => [
                        'id' => $business->id,
                        'name' => $business->name,
                        'address' => $business->address,
                        'phone' => $business->phone,
                        'logo' => $business->logo
                    ],
                    'menu' => $defaultMenu ? [
                        'id' => $defaultMenu->id,
                        'name' => $defaultMenu->name,
                        'file_path' => $defaultMenu->file_path,
                        'download_url' => route('public.menu.download', $defaultMenu->id)
                    ] : null,
                    'waiter' => $waiterInfo,
                    'pending_call' => $pendingCall ? [
                        'id' => $pendingCall->id,
                        'status' => $pendingCall->status,
                        'called_at' => $pendingCall->called_at,
                        'acknowledged_at' => $pendingCall->acknowledged_at
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error procesando el código QR'
            ], 500);
        }
    }

    /**
     * Descargar menú públicamente
     */
    public function downloadMenu(int $menuId)
    {
        $menu = \App\Models\Menu::findOrFail($menuId);
        
        $filePath = storage_path('app/' . $menu->file_path);
        
        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Menú no encontrado'
            ], 404);
        }
        
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $menu->name . '.pdf"'
        ]);
    }

    /**
     * Obtener estado actual de la mesa para polling
     */
    public function getTableStatus(Request $request, int $tableId): JsonResponse
    {
        $table = Table::with(['activeWaiter'])
            ->findOrFail($tableId);

        // Verificar si hay llamadas pendientes o reconocidas
        $activeCall = $table->waiterCalls()
            ->whereIn('status', ['pending', 'acknowledged'])
            ->latest('called_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'table_id' => $table->id,
                'can_call_waiter' => $table->canReceiveCalls(),
                'active_call' => $activeCall ? [
                    'id' => $activeCall->id,
                    'status' => $activeCall->status,
                    'called_at' => $activeCall->called_at,
                    'acknowledged_at' => $activeCall->acknowledged_at,
                    'minutes_ago' => $activeCall->called_at->diffInMinutes(now())
                ] : null,
                'waiter' => $table->activeWaiter ? [
                    'name' => $table->activeWaiter->name
                ] : null
            ]
        ]);
    }
}
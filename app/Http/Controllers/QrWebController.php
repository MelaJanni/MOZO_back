<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Business;
use Illuminate\Http\Request;
use Hashids\Hashids;

class QrWebController extends Controller
{
    /**
     * Mostrar página de mesa con menú y botón llamar mozo
     */
    public function showTablePage(string $businessSlug, string $tableHash)
    {
        try {
            // Encontrar business por slug
            $business = Business::whereRaw('LOWER(REPLACE(name, " ", "-")) = ?', [strtolower($businessSlug)])->first();
            
            if (!$business) {
                abort(404, 'Negocio no encontrado');
            }

            // Decodificar hash de mesa
            $hashids = new Hashids(config('app.key'), 6);
            $decodedIds = $hashids->decode($tableHash);
            
            if (empty($decodedIds)) {
                abort(404, 'Código QR inválido');
            }

            $tableId = $decodedIds[0];

            // Buscar la mesa
            $table = Table::with(['business', 'activeWaiter'])
                ->where('id', $tableId)
                ->where('business_id', $business->id)
                ->first();

            if (!$table) {
                abort(404, 'Mesa no encontrada');
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
            
            // Verificar si hay llamadas pendientes
            $pendingCall = $table->pendingCalls()
                ->latest('called_at')
                ->first();

            return view('qr.table-page', [
                'table' => $table,
                'business' => $business,
                'menu' => $defaultMenu,
                'canCallWaiter' => $canCallWaiter,
                'pendingCall' => $pendingCall,
                'apiBaseUrl' => config('app.url')
            ]);

        } catch (\Exception $e) {
            abort(500, 'Error procesando el código QR');
        }
    }
}
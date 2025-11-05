<?php

namespace App\Http\Controllers;

use App\Models\WaiterCall;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Controlador para consultas de historial de llamados
 * 
 * Responsabilidades:
 * - Obtener llamados pendientes
 * - Obtener historial de llamados con paginación
 * - Filtros por fecha, estado, mesa
 */
class CallHistoryController extends Controller
{
    /**
     * Lista llamados pendientes del mozo autenticado
     */
    public function getPendingCalls(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        $calls = WaiterCall::with(['table'])
            ->forWaiter($waiter->id)
            ->pending()
            ->orderBy('called_at', 'asc')
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'table' => [
                        'id' => $call->table->id,
                        'number' => $call->table->number,
                        'name' => $call->table->name
                    ],
                    'message' => $call->message,
                    'called_at' => $call->called_at,
                    'minutes_ago' => $call->called_at->diffInMinutes(now()),
                    'urgency' => $call->metadata['urgency'] ?? 'normal',
                    'status' => $call->status
                ];
            });

        return response()->json([
            'success' => true,
            'pending_calls' => $calls,
            'count' => $calls->count()
        ]);
    }

    /**
     * Obtiene historial de llamados con paginación
     */
    public function getCallHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        $filter = $request->input('filter', 'today'); // today, hour, historic
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 20);

        $query = WaiterCall::with(['table', 'waiter']);

        // Aplicar filtros según pertenencia real a roles
        if ($user->isWaiter()) {
            $query->forWaiter($user->id);
        } elseif ($user->isAdmin($request->business_id ?? null)) {
            // Los admins ven todas las llamadas de su business activo
            $query->whereHas('table', function ($q) use ($request) {
                $q->where('business_id', $request->business_id);
            });
        }

        // Aplicar filtros temporales
        switch ($filter) {
            case 'hour':
                $query->where('called_at', '>=', Carbon::now()->subHour());
                break;
            case 'today':
                $query->whereDate('called_at', Carbon::today());
                break;
            case 'historic':
                // Sin filtro temporal para histórico
                break;
        }

        $query->orderBy('called_at', 'desc');

        $calls = $query->paginate($limit, ['*'], 'page', $page);

        $formattedCalls = $calls->getCollection()->map(function ($call) {
            return [
                'id' => $call->id,
                'table' => [
                    'number' => $call->table->number,
                    'name' => $call->table->name
                ],
                'waiter' => [
                    'name' => $call->waiter->name ?? 'Sin asignar'
                ],
                'message' => $call->message,
                'status' => $call->status,
                'called_at' => $call->called_at,
                'acknowledged_at' => $call->acknowledged_at,
                'completed_at' => $call->completed_at,
                'response_time' => $call->formatted_response_time,
                'urgency' => $call->metadata['urgency'] ?? 'normal'
            ];
        });

        return response()->json([
            'success' => true,
            'calls' => $formattedCalls,
            'pagination' => [
                'current_page' => $calls->currentPage(),
                'last_page' => $calls->lastPage(),
                'per_page' => $calls->perPage(),
                'total' => $calls->total()
            ],
            'filter_applied' => $filter
        ]);
    }
}

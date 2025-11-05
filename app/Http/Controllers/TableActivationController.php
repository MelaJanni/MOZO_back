<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para operaciones de activación de mesas
 * 
 * Responsabilidades:
 * - Activar/desactivar mesas individuales
 * - Operaciones bulk de activación
 * - Asignación de mozos a mesas
 * - Listar mesas asignadas/disponibles
 */
class TableActivationController extends Controller
{
    /**
     * Activa una mesa individual
     */
    public function activateTable(Request $request, Table $table): JsonResponse
    {
        $waiter = Auth::user();

        // Verificar que la mesa pertenezca al negocio activo del mozo
        if ($table->business_id !== $waiter->business_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a esta mesa'
            ], 403);
        }

        // Verificar si la mesa ya tiene un mozo activo
        if ($table->active_waiter_id && $table->active_waiter_id !== $waiter->id) {
            // Verificar si el mozo asignado aún existe
            $assignedWaiterExists = \App\Models\User::where('id', $table->active_waiter_id)->exists();
            
            if (!$assignedWaiterExists) {
                // El mozo asignado no existe, permitir reasignación
                Log::info('Mesa con mozo huérfano encontrada', [
                    'table_id' => $table->id,
                    'orphan_waiter_id' => $table->active_waiter_id,
                    'new_waiter_id' => $waiter->id
                ]);
            } else {
                // El mozo asignado existe, verificar si está activo
                $assignedWaiter = \App\Models\User::find($table->active_waiter_id);
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa ya tiene un mozo asignado',
                    'current_waiter' => $assignedWaiter->name,
                    'assigned_waiter_id' => $assignedWaiter->id,
                    'requesting_waiter_id' => $waiter->id,
                    'suggestion' => 'Si eres el mozo original, contacta al administrador para reasignar la mesa'
                ], 409);
            }
        }

        // Si ya está asignado a este mozo, no hacer nada
        if ($table->active_waiter_id === $waiter->id) {
            return response()->json([
                'success' => true,
                'message' => 'Ya estás asignado a esta mesa',
                'table' => [
                    'id' => $table->id,
                    'number' => $table->number,
                    'name' => $table->name,
                    'assigned_at' => $table->waiter_assigned_at
                ]
            ]);
        }

        // Asignar mozo a la mesa
        $table->assignWaiter($waiter);

        return response()->json([
            'success' => true,
            'message' => 'Mesa activada correctamente',
            'table' => [
                'id' => $table->id,
                'number' => $table->number,
                'name' => $table->name,
                'assigned_at' => $table->waiter_assigned_at,
                'notifications_enabled' => $table->notifications_enabled
            ]
        ]);
    }

    /**
     * Desactiva una mesa individual
     */
    public function deactivateTable(Request $request, Table $table): JsonResponse
    {
        $waiter = Auth::user();

        // Verificar que el mozo esté asignado a esta mesa
        if ($table->active_waiter_id !== $waiter->id) {
            return response()->json([
                'success' => false,
                'message' => 'No estás asignado a esta mesa'
            ], 409);
        }

        // Cancelar llamadas pendientes antes de desasignar
        $pendingCalls = $table->pendingCalls();
        $cancelledCount = $pendingCalls->count();
        $pendingCalls->update(['status' => 'cancelled']);

        // Desasignar mozo
        $table->unassignWaiter();

        return response()->json([
            'success' => true,
            'message' => 'Mesa desactivada correctamente',
            'table' => [
                'id' => $table->id,
                'number' => $table->number,
                'name' => $table->name
            ],
            'cancelled_calls' => $cancelledCount
        ]);
    }

    /**
     * Activa múltiples mesas (operación bulk)
     */
    public function activateMultipleTables(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        $request->validate([
            'table_ids' => 'required|array|min:1|max:50',
            'table_ids.*' => 'integer|exists:tables,id'
        ]);

        $tableIds = $request->input('table_ids');
        
        // Obtener mesas y verificar permisos
        $tables = Table::whereIn('id', $tableIds)
            ->where('business_id', $waiter->business_id)
            ->get();

        if ($tables->count() !== count($tableIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Algunas mesas no existen o no tienes acceso a ellas'
            ], 400);
        }

        $results = [];
        $successful = 0;
        $errors = 0;

        DB::beginTransaction();

        try {
            foreach ($tables as $table) {
                if ($table->active_waiter_id && $table->active_waiter_id !== $waiter->id) {
                    $results[] = [
                        'table_id' => $table->id,
                        'table_number' => $table->number,
                        'success' => false,
                        'message' => 'Mesa ya tiene mozo asignado: ' . $table->activeWaiter->name
                    ];
                    $errors++;
                } else {
                    $table->assignWaiter($waiter);
                    $results[] = [
                        'table_id' => $table->id,
                        'table_number' => $table->number,
                        'success' => true,
                        'message' => 'Mesa activada correctamente'
                    ];
                    $successful++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Activadas {$successful} mesas. {$errors} errores.",
                'summary' => [
                    'total_requested' => count($tableIds),
                    'successful' => $successful,
                    'errors' => $errors
                ],
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error activating multiple tables', [
                'waiter_id' => $waiter->id,
                'table_ids' => $tableIds,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error procesando las mesas'
            ], 500);
        }
    }

    /**
     * Desactiva múltiples mesas (operación bulk)
     */
    public function deactivateMultipleTables(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        $request->validate([
            'table_ids' => 'required|array|min:1|max:50',
            'table_ids.*' => 'integer|exists:tables,id'
        ]);

        $tableIds = $request->input('table_ids');
        
        // Obtener solo las mesas donde este mozo está asignado
        $tables = Table::whereIn('id', $tableIds)
            ->where('active_waiter_id', $waiter->id)
            ->where('business_id', $waiter->business_id)
            ->get();

        $results = [];
        $successful = 0;
        $totalCancelledCalls = 0;

        DB::beginTransaction();

        try {
            foreach ($tables as $table) {
                // Cancelar llamadas pendientes
                $pendingCalls = $table->pendingCalls();
                $cancelledCount = $pendingCalls->count();
                $pendingCalls->update(['status' => 'cancelled']);
                $totalCancelledCalls += $cancelledCount;

                // Desasignar mozo
                $table->unassignWaiter();

                $results[] = [
                    'table_id' => $table->id,
                    'table_number' => $table->number,
                    'success' => true,
                    'message' => 'Mesa desactivada correctamente',
                    'cancelled_calls' => $cancelledCount
                ];
                $successful++;
            }

            // Verificar mesas que no estaban asignadas a este mozo
            $notAssignedIds = array_diff($tableIds, $tables->pluck('id')->toArray());
            foreach ($notAssignedIds as $tableId) {
                $table = Table::find($tableId);
                $results[] = [
                    'table_id' => $tableId,
                    'table_number' => $table ? $table->number : 'Desconocida',
                    'success' => false,
                    'message' => 'No estás asignado a esta mesa'
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Desactivadas {$successful} mesas. {$totalCancelledCalls} llamadas canceladas.",
                'summary' => [
                    'total_requested' => count($tableIds),
                    'successful' => $successful,
                    'not_assigned' => count($notAssignedIds),
                    'total_cancelled_calls' => $totalCancelledCalls
                ],
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deactivating multiple tables', [
                'waiter_id' => $waiter->id,
                'table_ids' => $tableIds,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error procesando las mesas'
            ], 500);
        }
    }

    /**
     * Lista mesas asignadas al mozo autenticado
     */
    public function getAssignedTables(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        $tables = Table::where('active_waiter_id', $waiter->id)
            ->where('business_id', $waiter->business_id)
            ->with(['pendingCalls', 'activeSilence'])
            ->get()
            ->map(function ($table) {
                return [
                    'id' => $table->id,
                    'number' => $table->number,
                    'name' => $table->name,
                    'notifications_enabled' => $table->notifications_enabled,
                    'assigned_at' => $table->waiter_assigned_at,
                    'pending_calls_count' => $table->pendingCalls()->count(),
                    'is_silenced' => $table->isSilenced(),
                    'silence_info' => ($activeSilence = $table->activeSilence()->first()) ? [
                        'reason' => $activeSilence->reason,
                        'remaining_time' => $activeSilence->formatted_remaining_time,
                        'notes' => $activeSilence->notes
                    ] : null
                ];
            });

        return response()->json([
            'success' => true,
            'assigned_tables' => $tables,
            'count' => $tables->count()
        ]);
    }

    /**
     * Lista mesas disponibles (sin mozo asignado)
     */
    public function getAvailableTables(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        $tables = Table::where('business_id', $waiter->business_id)
            ->whereNull('active_waiter_id')
            ->get()
            ->map(function ($table) {
                return [
                    'id' => $table->id,
                    'number' => $table->number,
                    'name' => $table->name,
                    'notifications_enabled' => $table->notifications_enabled,
                    'capacity' => $table->capacity,
                    'location' => $table->location
                ];
            });

        return response()->json([
            'success' => true,
            'available_tables' => $tables,
            'count' => $tables->count()
        ]);
    }
}

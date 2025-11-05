<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\TableSilence;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Controlador para operaciones de silencio de mesas
 * 
 * Responsabilidades:
 * - Silenciar/desilenciar mesas individuales
 * - Operaciones bulk de silencio
 * - Listar mesas silenciadas
 * - Auto-silencio por spam
 */
class TableSilenceController extends Controller
{
    /**
     * Silencia una mesa individual
     */
    public function silenceTable(Request $request, Table $table): JsonResponse
    {
        $waiter = Auth::user();

        $request->validate([
            'duration_minutes' => 'sometimes|integer|min:1|max:120', // Máximo 2 horas
            'notes' => 'nullable|string|max:500'
        ]);

        $durationMinutes = $request->input('duration_minutes', 30);

        // Verificar si ya está silenciada
        $existingSilence = null;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('table_silences')) {
                $existingSilence = TableSilence::where('table_id', $table->id)
                    ->active()
                    ->first();
            }
        } catch (\Exception $e) {
            // Tabla no existe, continuar sin silencio
        }

        if ($existingSilence && $existingSilence->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'La mesa ya está silenciada',
                'current_silence' => [
                    'reason' => $existingSilence->reason,
                    'remaining_time' => $existingSilence->formatted_remaining_time
                ]
            ], 409);
        }

        // Crear nuevo silencio
        $silence = TableSilence::create([
            'table_id' => $table->id,
            'silenced_by' => $waiter->id,
            'reason' => 'manual',
            'silenced_at' => now(),
            'notes' => $request->input('notes')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mesa silenciada correctamente',
            'silence' => [
                'id' => $silence->id,
                'reason' => 'manual',
                'silenced_at' => $silence->silenced_at,
                'notes' => $silence->notes
            ]
        ]);
    }

    /**
     * Desilencia una mesa individual
     */
    public function unsilenceTable(Request $request, Table $table): JsonResponse
    {
        $silence = TableSilence::where('table_id', $table->id)
            ->active()
            ->first();

        if (!$silence) {
            return response()->json([
                'success' => false,
                'message' => 'La mesa no está silenciada'
            ], 404);
        }

        $silence->unsilence();

        return response()->json([
            'success' => true,
            'message' => 'Silencio removido de la mesa',
            'unsilenced_at' => $silence->unsilenced_at
        ]);
    }

    /**
     * Lista todas las mesas silenciadas
     */
    public function getSilencedTables(Request $request): JsonResponse
    {
        // Por ahora, retornar lista vacía ya que la tabla table_silences no está migrada
        // TODO: Implementar cuando se migre la tabla table_silences
        return response()->json([
            'success' => true,
            'silenced_tables' => [],
            'count' => 0,
            'message' => 'Funcionalidad de silencio de mesas pendiente de implementación'
        ]);
    }

    /**
     * Silencia múltiples mesas (operación bulk)
     */
    public function silenceMultipleTables(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        $request->validate([
            'table_ids' => 'required|array|min:1|max:50',
            'table_ids.*' => 'integer|exists:tables,id',
            'duration_minutes' => 'sometimes|integer|min:1|max:120',
            'notes' => 'sometimes|string|max:500'
        ]);

        $tableIds = $request->input('table_ids');
        $durationMinutes = $request->input('duration_minutes', 30);
        $notes = $request->input('notes');
        
        // Obtener mesas del mismo negocio
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
        $alreadySilenced = 0;

        DB::beginTransaction();

        try {
            foreach ($tables as $table) {
                // Verificar si ya está silenciada
                $existingSilence = $table->activeSilence();
                if ($existingSilence && $existingSilence->isActive()) {
                    $results[] = [
                        'table_id' => $table->id,
                        'table_number' => $table->number,
                        'success' => false,
                        'message' => 'Mesa ya está silenciada',
                        'remaining_time' => $existingSilence->formatted_remaining_time
                    ];
                    $alreadySilenced++;
                } else {
                    // Crear nuevo silencio
                    TableSilence::create([
                        'table_id' => $table->id,
                        'silenced_by' => $waiter->id,
                        'reason' => 'manual',
                        'silenced_at' => now(),
                        'notes' => $notes
                    ]);

                    $results[] = [
                        'table_id' => $table->id,
                        'table_number' => $table->number,
                        'success' => true,
                        'message' => 'Mesa silenciada correctamente',
                        'duration_minutes' => $durationMinutes
                    ];
                    $successful++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Silenciadas {$successful} mesas. {$alreadySilenced} ya estaban silenciadas.",
                'summary' => [
                    'total_requested' => count($tableIds),
                    'successful' => $successful,
                    'already_silenced' => $alreadySilenced,
                    'duration_minutes' => $durationMinutes
                ],
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error silencing multiple tables', [
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
     * Desilencia múltiples mesas (operación bulk)
     */
    public function unsilenceMultipleTables(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        $request->validate([
            'table_ids' => 'required|array|min:1|max:50',
            'table_ids.*' => 'integer|exists:tables,id'
        ]);

        $tableIds = $request->input('table_ids');
        
        // Obtener mesas silenciadas del mismo negocio
        $silences = TableSilence::whereIn('table_id', $tableIds)
            ->whereHas('table', function ($q) use ($waiter) {
                $q->where('business_id', $waiter->business_id);
            })
            ->active()
            ->get();

        $results = [];
        $successful = 0;
        $notSilenced = 0;

        DB::beginTransaction();

        try {
            foreach ($tableIds as $tableId) {
                $silence = $silences->where('table_id', $tableId)->first();
                
                if ($silence) {
                    $silence->unsilence();
                    $table = Table::find($tableId);
                    
                    $results[] = [
                        'table_id' => $tableId,
                        'table_number' => $table->number,
                        'success' => true,
                        'message' => 'Silencio removido correctamente'
                    ];
                    $successful++;
                } else {
                    $table = Table::find($tableId);
                    $results[] = [
                        'table_id' => $tableId,
                        'table_number' => $table ? $table->number : 'Desconocida',
                        'success' => false,
                        'message' => 'Mesa no está silenciada'
                    ];
                    $notSilenced++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Removido silencio de {$successful} mesas. {$notSilenced} no estaban silenciadas.",
                'summary' => [
                    'total_requested' => count($tableIds),
                    'successful' => $successful,
                    'not_silenced' => $notSilenced
                ],
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error unsilencing multiple tables', [
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
     * Auto-silencia una mesa por spam (privado)
     */
    private function autoSilenceTable(Table $table, int $callCount)
    {
        TableSilence::create([
            'table_id' => $table->id,
            'reason' => 'automatic',
            'silenced_at' => now(),
            'call_count' => $callCount,
            'notes' => "Silenciado automáticamente por {$callCount} llamadas en 10 minutos"
        ]);

        Log::warning('Table auto-silenced for spam', [
            'table_id' => $table->id,
            'call_count' => $callCount
        ]);
    }
}

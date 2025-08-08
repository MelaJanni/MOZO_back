<?php

namespace App\Http\Controllers;

use App\Models\WaiterCall;
use App\Models\Table;
use App\Models\TableSilence;
use App\Services\FirebaseService;
use App\Services\FirebaseRealtimeService;
use App\Notifications\FcmDatabaseNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WaiterCallController extends Controller
{
    private $firebaseService;
    private $firebaseRealtimeService;

    public function __construct(FirebaseService $firebaseService, FirebaseRealtimeService $firebaseRealtimeService)
    {
        $this->firebaseService = $firebaseService;
        $this->firebaseRealtimeService = $firebaseRealtimeService;
    }

    /**
     * Mesa llama a mozo
     */
    public function callWaiter(Request $request, Table $table): JsonResponse
    {
        try {
            // Verificar si la mesa tiene notificaciones habilitadas
            if (!$table->notifications_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Las notificaciones están desactivadas para esta mesa'
                ], 400);
            }

            // Verificar si la mesa tiene un mozo activo asignado
            if (!$table->active_waiter_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa no tiene un mozo asignado actualmente. Por favor, llame manualmente al mozo.',
                    'action_required' => 'call_manually'
                ], 422);
            }

            // Verificar si la mesa está silenciada
            $activeSilence = TableSilence::where('table_id', $table->id)
                ->active()
                ->first();

            if ($activeSilence && $activeSilence->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa está temporalmente silenciada',
                    'silenced_until' => $activeSilence->remaining_time,
                    'reason' => $activeSilence->reason,
                    'formatted_time' => $activeSilence->formatted_remaining_time
                ], 429);
            }

            // Verificar llamadas recientes para spam protection
            $recentCalls = WaiterCall::where('table_id', $table->id)
                ->where('called_at', '>=', Carbon::now()->subMinutes(10))
                ->count();

            // Si ya hay 3 o más llamadas en 10 minutos, silenciar automáticamente
            if ($recentCalls >= 3) {
                $this->autoSilenceTable($table, $recentCalls + 1);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Mesa silenciada automáticamente por múltiples llamadas. Intente nuevamente en 10 minutos.',
                    'reason' => 'spam_protection',
                    'silenced_for' => '10 minutos'
                ], 429);
            }

            // Verificar si hay una llamada pendiente muy reciente (< 30 segundos)
            $veryRecentCall = WaiterCall::where('table_id', $table->id)
                ->where('status', 'pending')
                ->where('called_at', '>=', Carbon::now()->subSeconds(30))
                ->first();

            if ($veryRecentCall) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya hay una llamada pendiente muy reciente. Por favor espere.',
                    'pending_call' => [
                        'id' => $veryRecentCall->id,
                        'called_at' => $veryRecentCall->called_at,
                        'seconds_ago' => $veryRecentCall->called_at->diffInSeconds(now())
                    ]
                ], 409);
            }

            // Crear la llamada
            $call = WaiterCall::create([
                'table_id' => $table->id,
                'waiter_id' => $table->active_waiter_id,
                'status' => 'pending',
                'message' => $request->input('message', 'Llamada desde mesa ' . $table->number),
                'called_at' => now(),
                'metadata' => [
                    'client_info' => $request->input('client_info'),
                    'urgency' => $request->input('urgency', 'normal'),
                    'ip_address' => $request->ip()
                ]
            ]);

            // Enviar notificación FCM al mozo
            $this->sendNotificationToWaiter($call);

            // 🔥 ESCRIBIR EN FIRESTORE PARA TIEMPO REAL
            $this->firebaseRealtimeService->writeWaiterCall($call, 'created');

            return response()->json([
                'success' => true,
                'message' => 'Mozo llamado exitosamente. Aguarde por favor.',
                'call' => [
                    'id' => $call->id,
                    'table_number' => $table->number,
                    'waiter_name' => $table->activeWaiter->name ?? 'Mozo',
                    'called_at' => $call->called_at,
                    'status' => 'pending'
                ],
                'estimated_response_time' => '2-3 minutos'
            ]);

        } catch (\Exception $e) {
            Log::error('Error calling waiter', [
                'table_id' => $table->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error procesando la llamada. Intente nuevamente.'
            ], 500);
        }
    }

    /**
     * Mozo responde a llamada (acknowledge)
     */
    public function acknowledgeCall(Request $request, WaiterCall $call): JsonResponse
    {
        $waiter = Auth::user();

        // Verificar que el mozo puede responder esta llamada
        if ($call->waiter_id !== $waiter->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para responder esta llamada'
            ], 403);
        }

        if ($call->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Esta llamada ya fue procesada',
                'current_status' => $call->status
            ], 409);
        }

        // Marcar como reconocida
        $call->acknowledge();

        // 🔥 ESCRIBIR EN FIRESTORE - Mesa verá "mozo llamado"
        $this->firebaseRealtimeService->writeWaiterCall($call, 'acknowledged');
        
        return response()->json([
            'success' => true,
            'message' => 'Llamada confirmada',
            'call' => [
                'id' => $call->id,
                'status' => 'acknowledged',
                'acknowledged_at' => $call->acknowledged_at,
                'response_time' => $call->formatted_response_time,
                'table_number' => $call->table->number
            ]
        ]);
    }

    /**
     * Mozo completa llamada
     */
    public function completeCall(Request $request, WaiterCall $call): JsonResponse
    {
        $waiter = Auth::user();

        if ($call->waiter_id !== $waiter->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para completar esta llamada'
            ], 403);
        }

        if (!in_array($call->status, ['pending', 'acknowledged'])) {
            return response()->json([
                'success' => false,
                'message' => 'Esta llamada no puede ser completada',
                'current_status' => $call->status
            ], 409);
        }

        // Si no estaba acknowledged, hacerlo primero
        if ($call->status === 'pending') {
            $call->acknowledge();
        }

        // Marcar como completada
        $call->complete();

        // 🔥 ESCRIBIR EN FIRESTORE - Atención completada
        $this->firebaseRealtimeService->completeWaiterCall($call);

        return response()->json([
            'success' => true,
            'message' => 'Llamada completada',
            'call' => [
                'id' => $call->id,
                'status' => 'completed',
                'completed_at' => $call->completed_at,
                'total_time' => $call->called_at->diffInSeconds($call->completed_at)
            ]
        ]);
    }

    /**
     * Obtener llamadas pendientes para un mozo
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
     * Historial de llamadas con filtros
     */
    public function getCallHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        $filter = $request->input('filter', 'today'); // today, hour, historic
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 20);

        $query = WaiterCall::with(['table', 'waiter']);

        // Aplicar filtros según el rol del usuario
        if ($user->role === 'waiter') {
            $query->forWaiter($user->id);
        } elseif ($user->role === 'admin') {
            // Los admins ven todas las llamadas de su business
            $query->whereHas('table', function ($q) use ($user) {
                $q->where('business_id', $user->business_id);
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

    /**
     * Silenciar mesa manualmente
     */
    public function silenceTable(Request $request, Table $table): JsonResponse
    {
        $waiter = Auth::user();

        $request->validate([
            'duration_minutes' => 'sometimes|integer|min:1|max:120', // Máximo 2 horas
            'notes' => 'sometimes|string|max:500'
        ]);

        $durationMinutes = $request->input('duration_minutes', 30);

        // Verificar si ya está silenciada
        $existingSilence = TableSilence::where('table_id', $table->id)
            ->active()
            ->first();

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

        // 🔥 ESCRIBIR EN FIRESTORE - Mesa silenciada
        $this->firebaseRealtimeService->writeTableStatus($table, 'silenced', [
            'silenced_by' => $waiter->name,
            'duration_minutes' => $durationMinutes,
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
     * Quitar silencio de mesa
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

        // 🔥 ESCRIBIR EN FIRESTORE - Mesa des-silenciada
        $this->firebaseRealtimeService->writeTableStatus($table, 'unsilenced', [
            'unsilenced_by' => Auth::user()->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Silencio removido de la mesa',
            'unsilenced_at' => $silence->unsilenced_at
        ]);
    }

    /**
     * Obtener estado de mesas silenciadas
     */
    public function getSilencedTables(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = TableSilence::with(['table', 'silencedBy'])
            ->active()
            ->whereHas('table', function ($q) use ($user) {
                $q->where('business_id', $user->business_id);
            });

        $silences = $query->get()->map(function ($silence) {
            return [
                'id' => $silence->id,
                'table' => [
                    'id' => $silence->table->id,
                    'number' => $silence->table->number,
                    'name' => $silence->table->name
                ],
                'reason' => $silence->reason,
                'silenced_by' => $silence->silencedBy->name ?? 'Sistema',
                'silenced_at' => $silence->silenced_at,
                'remaining_time' => $silence->formatted_remaining_time,
                'notes' => $silence->notes,
                'can_unsilence' => $silence->reason === 'manual'
            ];
        });

        return response()->json([
            'success' => true,
            'silenced_tables' => $silences,
            'count' => $silences->count()
        ]);
    }

    /**
     * Enviar notificación FCM al mozo
     */
    private function sendNotificationToWaiter(WaiterCall $call)
    {
        try {
            $title = "🔔 Llamada de Mesa {$call->table->number}";
            $body = $call->message;
            $data = [
                'type' => 'waiter_call',
                'call_id' => (string)$call->id,
                'table_id' => (string)$call->table->id,
                'table_number' => (string)$call->table->number,
                'urgency' => $call->metadata['urgency'] ?? 'normal',
                'action' => 'acknowledge_call'
            ];

            // Enviar via FCM
            $this->firebaseService->sendToUser($call->waiter_id, $title, $body, $data);

            // También guardar en el sistema de notificaciones de Laravel
            $call->waiter->notify(new FcmDatabaseNotification($title, $body, $data));

            Log::info('Waiter call notification sent', [
                'call_id' => $call->id,
                'waiter_id' => $call->waiter_id,
                'table_id' => $call->table->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send waiter call notification', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Silenciar mesa automáticamente por spam
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

    /**
     * Mozo se activa/asigna a una mesa
     */
    public function activateTable(Request $request, Table $table): JsonResponse
    {
        $waiter = Auth::user();

        // Verificar que la mesa pertenezca al mismo negocio
        if ($table->business_id !== $waiter->business_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a esta mesa'
            ], 403);
        }

        // Verificar si la mesa ya tiene un mozo activo
        if ($table->active_waiter_id && $table->active_waiter_id !== $waiter->id) {
            return response()->json([
                'success' => false,
                'message' => 'Esta mesa ya tiene un mozo asignado',
                'current_waiter' => $table->activeWaiter->name
            ], 409);
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
     * Mozo se desactiva/desasigna de una mesa
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
     * Mozo se activa en múltiples mesas
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
     * Mozo se desactiva de múltiples mesas
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
     * Silenciar múltiples mesas
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
     * Quitar silencio de múltiples mesas
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
     * Obtener mesas asignadas al mozo
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
                    'pending_calls_count' => $table->pendingCalls->count(),
                    'is_silenced' => $table->isSilenced(),
                    'silence_info' => $table->activeSilence() ? [
                        'reason' => $table->activeSilence()->reason,
                        'remaining_time' => $table->activeSilence()->formatted_remaining_time,
                        'notes' => $table->activeSilence()->notes
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
     * Obtener mesas disponibles para asignar
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
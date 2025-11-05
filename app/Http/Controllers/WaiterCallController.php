<?php

namespace App\Http\Controllers;

use App\Models\WaiterCall;
use App\Models\Table;
use App\Models\TableSilence;
use App\Models\IpBlock;
use App\Services\FirebaseService;
use App\Services\UnifiedFirebaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * WaiterCallController - Sistema CORE de llamadas de mesas a mozos
 * 
 * 🎯 RESPONSABILIDADES (post-refactorización FASE 3.1):
 * 
 * OPERACIONES DE LLAMADAS:
 * - callWaiter(): Crear llamada desde QR de mesa con protección anti-spam/IP
 * - acknowledgeCall(): Mozo responde llamada pendiente
 * - completeCall(): Mozo marca llamada como completada
 * - createNotification(): Endpoint legacy para compatibilidad con frontend
 * - getNotificationStatus(): Consultar estado de llamada
 * 
 * SEGURIDAD Y PROTECCIÓN:
 * - IP blocking: Rechazo silencioso de IPs bloqueadas (sin alertar spammer)
 * - Spam protection: Auto-silence de mesas con 3+ llamadas en 10 minutos
 * - Table silence: Respeto de mesas silenciadas manualmente
 * - Duplicate prevention: Evita llamadas duplicadas <30 segundos
 * 
 * INTEGRACIONES FIREBASE V2:
 * - WaiterCallNotificationService: Servicio unificado V2 para notificaciones
 * - Procesamiento asíncrono vía queue (ProcessWaiterCallNotification job)
 * - Fallback síncrono usando WaiterCallNotificationService directamente
 * - Respuestas <200ms con procesamiento en background
 * 
 * 📊 CONTROLLERS ESPECIALIZADOS (funcionalidad migrada):
 * - CallHistoryController: Historial y consultas de llamadas
 * - TableSilenceController: Gestión de silencios (individual/bulk)
 * - TableActivationController: Asignación mozos a mesas (individual/bulk)
 * - DashboardController: Estadísticas y dashboard para mozos
 * - BusinessWaiterController: Multi-negocio y gestión de negocios
 * - IpBlockController: Bloqueo y gestión de IPs maliciosas
 * 
 * 🔧 MÉTODOS PRIVADOS:
 * - autoSilenceTable(): Auto-silence por spam detection
 * 
 * 📏 MÉTRICAS:
 * - Tamaño: ~650 líneas (reducido desde 2,704 líneas)
 * - Métodos públicos: 5 endpoints activos
 * - Métodos privados: 3 helpers internos
 * - Reducción: 76% del código original distribuido en 6 controllers
 * 
 * @see routes/api.php - Rutas prefijo: waiter/calls
 * @see \App\Models\WaiterCall - Modelo principal con scopes y métodos
 * @see \App\Models\Table - Mesas con mozos asignados y silencios
 * @see \App\Services\WaiterCallNotificationService - Servicio V2 de notificaciones
 * @see \App\Jobs\ProcessWaiterCallNotification - Job asíncrono para processing
 */
class WaiterCallController extends Controller
{
    private $waiterCallService;

    public function __construct(\App\Services\WaiterCallNotificationService $waiterCallService)
    {
        $this->waiterCallService = $waiterCallService;
    }

    /**
     * ============================================================================
     * CORE CALL OPERATIONS
     * ============================================================================
     */

    /**
     * Mesa llama a mozo desde QR code
     * 
     * Flujo completo con protecciones:
     * 1. IP blocking check (silent rejection)
     * 2. Table validations (notifications_enabled, active_waiter assigned)
     * 3. Silence check
     * 4. Spam protection (3+ calls in 10min auto-silences table)
     * 5. Duplicate prevention (<30 sec)
     * 6. Create WaiterCall record
     * 7. Async notification via queue or sync fallback
     * 8. Firebase Realtime DB update
     * 
     * @param int $tableId ID de la mesa desde la cual se llama
     * @param Request $request body: { message?: string, urgency?: 'low'|'normal'|'high' }
     * @return JsonResponse { success: bool, message: string, call?: object, estimated_response_time?: string }
     */
    public function callWaiter(int $tableId, Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'nullable|string|max:500',
            'urgency' => 'nullable|in:low,normal,high'
        ]);

        // 1. VERIFICAR SI LA IP ESTÁ BLOQUEADA (rechazo silencioso)
        $clientIp = $request->ip();
        $table = Table::findOrFail($tableId);

        if (IpBlock::isIpBlocked($clientIp, $table->business_id)) {
            // Respuesta fake de "éxito" para no alertar al spammer
            Log::info('Blocked IP attempted call', [
                'ip' => $clientIp,
                'table_id' => $tableId,
                'business_id' => $table->business_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tu solicitud ha sido recibida',
                'call' => [
                    'id' => rand(1000, 9999),
                    'table_number' => $table->number,
                    'status' => 'pending',
                    'called_at' => now(),
                    'message' => $request->input('message', "Llamada desde mesa {$table->number}")
                ],
                'estimated_response_time' => '2-3 minutos'
            ]);
        }

        // 2. VALIDACIONES DE MESA
        if (!$table->notifications_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Las notificaciones están desactivadas para esta mesa'
            ], 400);
        }

        if (!$table->active_waiter_id) {
            return response()->json([
                'success' => false,
                'message' => 'Esta mesa no tiene un mozo asignado actualmente'
            ], 422);
        }

        // 3. VERIFICAR SI LA MESA ESTÁ SILENCIADA
        if ($table->isSilenced()) {
            $silence = $table->activeSilence()->first();
            return response()->json([
                'success' => false,
                'message' => 'Esta mesa está temporalmente silenciada. Por favor espera.',
                'silence_info' => [
                    'reason' => $silence->reason,
                    'remaining_time' => $silence->formatted_remaining_time,
                    'notes' => $silence->notes
                ]
            ], 429);
        }

        // 4. PROTECCIÓN ANTI-SPAM: detectar múltiples llamadas
        $recentCallsCount = WaiterCall::where('table_id', $tableId)
            ->where('called_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recentCallsCount >= 3) {
            // Auto-silenciar mesa por spam
            $this->autoSilenceTable($table, $recentCallsCount);

            Log::warning('Table auto-silenced for spam', [
                'table_id' => $tableId,
                'recent_calls_count' => $recentCallsCount,
                'ip' => $clientIp
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Has realizado demasiadas llamadas. La mesa ha sido temporalmente silenciada.',
                'call_count' => $recentCallsCount
            ], 429);
        }

        // 5. EVITAR DUPLICADOS MUY RECIENTES (<30 segundos)
        $veryRecentCall = WaiterCall::where('table_id', $tableId)
            ->where('status', 'pending')
            ->where('called_at', '>=', now()->subSeconds(30))
            ->first();

        if ($veryRecentCall) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una llamada reciente pendiente para esta mesa',
                'existing_call' => [
                    'id' => $veryRecentCall->id,
                    'called_at' => $veryRecentCall->called_at,
                    'seconds_ago' => $veryRecentCall->called_at->diffInSeconds(now())
                ]
            ], 409);
        }

        // 6. CREAR LLAMADA
        $message = trim($request->input('message', ''));
        if (empty($message)) {
            $message = "Llamada desde mesa {$table->number}";
        }

        $call = WaiterCall::create([
            'table_id' => $tableId,
            'waiter_id' => $table->active_waiter_id,
            'status' => 'pending',
            'message' => $message,
            'called_at' => now(),
            'metadata' => [
                'urgency' => $request->input('urgency', 'normal'),
                'ip_address' => $clientIp,
                'user_agent' => $request->userAgent(),
                'source' => 'qr_code',
                'client_info' => [
                    'ip' => $clientIp,
                    'user_agent' => $request->userAgent()
                ]
            ]
        ]);

        // 7. PROCESAMIENTO ASÍNCRONO (queue) O SÍNCRONO (fallback)
        if (config('queue.default') !== 'sync') {
            // Queue asíncrono para respuesta ultra-rápida
            dispatch(new \App\Jobs\ProcessWaiterCallNotification($call))->onQueue('high-priority');
        } else {
            // Fallback síncrono: usar servicio V2
            try {
                $this->waiterCallService->processNewCall($call);
            } catch (\Exception $e) {
                Log::error('Sync notification failed', [
                    'call_id' => $call->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Llamada enviada al mozo exitosamente',
            'call' => [
                'id' => $call->id,
                'table_number' => $table->number,
                'waiter_name' => $table->activeWaiter->name ?? 'Mozo',
                'status' => 'pending',
                'called_at' => $call->called_at,
                'message' => $call->message,
                'urgency' => $call->metadata['urgency'] ?? 'normal'
            ],
            'estimated_response_time' => '2-3 minutos'
        ]);
    }

    /**
     * Mozo reconoce/acepta una llamada pendiente
     * 
     * @param int $callId ID de la llamada a reconocer
     * @return JsonResponse
     */
    public function acknowledgeCall(int $callId): JsonResponse
    {
        $waiter = Auth::user();
        $call = WaiterCall::findOrFail($callId);

        // Verificar que el mozo tiene permiso para esta llamada
        if ($call->waiter_id !== $waiter->id) {
            return response()->json([
                'success' => false,
                'message' => 'Esta llamada no está asignada a ti'
            ], 403);
        }

        // Verificar que esté pendiente
        if ($call->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Esta llamada ya no está pendiente',
                'current_status' => $call->status
            ], 409);
        }

        // Marcar como reconocida
        $call->acknowledge();

        // Actualizar Firebase usando servicio V2
        $this->waiterCallService->processAcknowledgedCall($call);

        return response()->json([
            'success' => true,
            'message' => 'Llamada reconocida',
            'call' => [
                'id' => $call->id,
                'status' => 'acknowledged',
                'acknowledged_at' => $call->acknowledged_at,
                'response_time' => $call->called_at->diffInSeconds($call->acknowledged_at) . ' segundos'
            ]
        ]);
    }

    /**
     * Mozo completa/cierra una llamada
     * 
     * @param int $callId ID de la llamada a completar
     * @return JsonResponse
     */
    public function completeCall(int $callId): JsonResponse
    {
        $waiter = Auth::user();
        $call = WaiterCall::findOrFail($callId);

        // Verificar permiso
        if ($call->waiter_id !== $waiter->id) {
            return response()->json([
                'success' => false,
                'message' => 'Esta llamada no está asignada a ti'
            ], 403);
        }

        // Verificar estado (puede completar desde pending o acknowledged)
        if (!in_array($call->status, ['pending', 'acknowledged'])) {
            return response()->json([
                'success' => false,
                'message' => 'Esta llamada no puede ser completada',
                'current_status' => $call->status
            ], 409);
        }

        // Auto-acknowledge si estaba pending
        if ($call->status === 'pending') {
            $call->acknowledge();
        }

        // Marcar como completada
        $call->complete();

        // Procesar llamada completada usando servicio V2
        $this->waiterCallService->processCompletedCall($call);

        return response()->json([
            'success' => true,
            'message' => 'Llamada completada',
            'call' => [
                'id' => $call->id,
                'status' => 'completed',
                'completed_at' => $call->completed_at,
                'total_time' => $call->called_at->diffInSeconds($call->completed_at) . ' segundos'
            ]
        ]);
    }

    /**
     * ============================================================================
     * LEGACY COMPATIBILITY ENDPOINTS
     * ============================================================================
     * 
     * Estos endpoints mantienen compatibilidad con frontend legacy que usa:
     * - POST /api/restaurant/{id}/tables/{table_id}/notifications (createNotification)
     * - GET /api/waiter/notifications/{id} (getNotificationStatus)
     * 
     * Internamente usan el mismo flujo que callWaiter pero con parámetros legacy.
     */

    /**
     * Crear notificación de mozo (compatibilidad con frontend existente)
     * 
     * Este endpoint es usado por el frontend legacy. Internamente delega a la 
     * lógica principal de callWaiter pero acepta parámetros legacy como restaurant_id.
     * 
     * @deprecated Use callWaiter() instead
     */
    public function createNotification(Request $request): JsonResponse
    {
        // 🔥 PROOF OF EXECUTION - Debug tracking
        try {
            \Illuminate\Support\Facades\Http::timeout(3)->put(
                "https://mozoqr-7d32c-default-rtdb.firebaseio.com/proof_execution/method_called_" . time() . ".json",
                ['message' => 'createNotification method executed', 'timestamp' => now()->toIso8601String()]
            );
        } catch (\Exception $e) {
            // Silently continue if Firebase debug write fails
        }

        try {
            Log::info('Waiter notification request received (LEGACY)', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'body' => $request->all()
            ]);

            $request->validate([
                'restaurant_id' => 'required|integer',
                'table_id' => 'required|integer',
                'message' => 'sometimes|string|max:500',
                'urgency' => 'sometimes|in:low,normal,high'
            ]);

            $clientIp = $request->ip();
            $table = Table::with(['activeWaiter', 'business'])->find($request->table_id);

            if (!$table) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mesa no encontrada'
                ], 404);
            }

            // VERIFICAR IP BLOQUEADA (respuesta fake de éxito)
            if (IpBlock::isIpBlocked($clientIp, $table->business_id)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notificación enviada al mozo exitosamente',
                    'data' => [
                        'id' => fake()->randomNumber(),
                        'table_number' => $table->number,
                        'waiter_name' => $table->activeWaiter->name ?? 'Mozo',
                        'status' => 'pending',
                        'called_at' => now(),
                        'message' => $request->input('message', 'Llamada desde mesa ' . $table->number),
                        'blocked' => true
                    ],
                    'blocked' => true,
                    'blocked_ip' => true
                ]);
            }

            // Validaciones básicas
            if (!$table->notifications_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Las notificaciones están desactivadas para esta mesa'
                ], 400);
            }

            if (!$table->active_waiter_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa no tiene un mozo asignado actualmente'
                ], 422);
            }

            // 🔥 FIX TEMPORAL: Si el waiter asignado no existe, usar waiter 2 para testing
            $actualWaiterId = $table->active_waiter_id;
            $waiterExists = \App\Models\User::where('id', $actualWaiterId)->exists();
            if (!$waiterExists && $actualWaiterId == 1) {
                $actualWaiterId = 2;
            }

            // CREAR LLAMADA
            $call = WaiterCall::create([
                'table_id' => $table->id,
                'waiter_id' => $actualWaiterId,
                'status' => 'pending',
                'message' => $request->input('message', 'Llamada desde mesa ' . $table->number),
                'called_at' => now(),
                'metadata' => [
                    'urgency' => $request->input('urgency', 'normal'),
                    'restaurant_id' => $request->input('restaurant_id'),
                    'source' => 'legacy_frontend',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]
            ]);

            // 🔥 ESCRIBIR A FIREBASE INMEDIATAMENTE (modo directo para testing)
            try {
                \Illuminate\Support\Facades\Http::timeout(3)->put(
                    "https://mozoqr-7d32c-default-rtdb.firebaseio.com/waiters/{$call->waiter_id}/calls/{$call->id}.json",
                    [
                        'id' => (string)$call->id,
                        'table_number' => (int)$table->number,
                        'table_id' => (int)$call->table_id,
                        'message' => (string)$call->message,
                        'urgency' => (string)($call->metadata['urgency'] ?? 'normal'),
                        'status' => 'pending',
                        'timestamp' => time() * 1000,
                        'called_at' => time() * 1000,
                        'waiter_id' => (string)$call->waiter_id,
                        'client_info' => [
                            'ip_address' => $call->metadata['ip_address'] ?? null,
                            'user_agent' => $call->metadata['user_agent'] ?? null,
                            'source' => $call->metadata['source'] ?? 'legacy_frontend'
                        ]
                    ]
                );

                // Debug write también
                \Illuminate\Support\Facades\Http::timeout(3)->put(
                    "https://mozoqr-7d32c-default-rtdb.firebaseio.com/debug_direct/call_{$call->id}.json",
                    [
                        'call_id' => $call->id,
                        'waiter_id' => $call->waiter_id,
                        'table_number' => $table->number,
                        'message' => $call->message,
                        'created_at' => now()->toIso8601String(),
                        'client_info' => [
                            'ip_address' => $call->metadata['ip_address'] ?? null,
                            'user_agent' => $call->metadata['user_agent'] ?? null,
                            'source' => $call->metadata['source'] ?? 'legacy_frontend'
                        ]
                    ]
                );
            } catch (\Exception $e) {
                Log::warning('Direct Firebase write failed', ['error' => $e->getMessage()]);
            }

            // Verificar silencio DESPUÉS de escribir a Firebase
            if ($table->isSilenced()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Solicitud procesada (mesa silenciada)',
                    'data' => [
                        'id' => $call->id,
                        'table_number' => $table->number,
                        'waiter_name' => $table->activeWaiter->name ?? 'Mozo',
                        'status' => 'silenced',
                        'called_at' => $call->called_at,
                        'message' => $call->message,
                        'firebase_written' => true
                    ]
                ]);
            }

            // PROCESAMIENTO ASÍNCRONO
            if (config('queue.default') !== 'sync') {
                dispatch(new \App\Jobs\ProcessWaiterCallNotification($call))->onQueue('high-priority');
            } else {
                try {
                    $this->unifiedFirebaseService->writeCall($call, 'created');
                } catch (\Exception $e) {
                    Log::warning('Unified Firebase service failed', ['error' => $e->getMessage()]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Notificación enviada al mozo exitosamente',
                'data' => [
                    'id' => $call->id,
                    'table_number' => $table->number,
                    'waiter_name' => $table->activeWaiter->name ?? 'Mozo',
                    'status' => 'pending',
                    'called_at' => $call->called_at,
                    'message' => $call->message
                ],
                'debug_info' => [
                    'firebase_write_attempted' => true,
                    'waiter_id' => $call->waiter_id,
                    'queue_config' => config('queue.default')
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de solicitud inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating waiter notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $debugMessage = config('app.debug') ? $e->getMessage() : 'Error procesando la solicitud. Intente nuevamente.';

            return response()->json([
                'success' => false,
                'message' => $debugMessage,
                'debug_info' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    /**
     * Obtener estado de notificación de mozo (compatibilidad con frontend existente)
     * 
     * @deprecated Use standard call endpoints instead
     */
    public function getNotificationStatus($id): JsonResponse
    {
        try {
            $call = WaiterCall::select(['id', 'table_id', 'waiter_id', 'status', 'message', 'called_at', 'acknowledged_at', 'completed_at'])
                ->with(['table:id,number', 'waiter:id,name'])
                ->find($id);

            if (!$call) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $call->id,
                    'table_id' => $call->table_id,
                    'table_number' => $call->table->number,
                    'waiter_name' => $call->waiter->name ?? 'Mozo',
                    'status' => $call->status,
                    'message' => $call->message,
                    'called_at' => $call->called_at,
                    'acknowledged_at' => $call->acknowledged_at,
                    'completed_at' => $call->completed_at,
                    'is_acknowledged' => $call->status === 'acknowledged',
                    'is_completed' => $call->status === 'completed',
                    'response_time_minutes' => $call->acknowledged_at ?
                        $call->called_at->diffInMinutes($call->acknowledged_at) : null
                ]
            ], 200, [
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting notification status', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo estado de notificación'
            ], 500);
        }
    }

    /**
     * ============================================================================
     * PRIVATE HELPER METHODS
     * ============================================================================
     */

    /**
     * Enviar notificación push FCM al mozo
     * 
     * @param WaiterCall $call Llamada para la cual enviar notificación
     * @return void
     */
    private function sendNotificationToWaiter(WaiterCall $call): void
    {
        try {
            $urgency = $call->metadata['urgency'] ?? 'normal';
            $priority = in_array($urgency, ['high', 'urgent']) ? 'high' : 'normal';

            $this->firebaseService->sendToUser(
                $call->waiter_id,
                [
                    'title' => "Mesa {$call->table->number} te está llamando",
                    'body' => $call->message,
                    'data' => [
                        'type' => 'waiter_call',
                        'call_id' => (string)$call->id,
                        'table_id' => (string)$call->table_id,
                        'table_number' => (string)$call->table->number,
                        'urgency' => $urgency,
                        'action' => 'VIEW_CALL',
                        'timestamp' => (string)time()
                    ]
                ],
                $priority
            );

            Log::info('Push notification sent to waiter', [
                'call_id' => $call->id,
                'waiter_id' => $call->waiter_id,
                'priority' => $priority
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Auto-silenciar mesa por detección de spam
     * 
     * @param Table $table Mesa a silenciar
     * @param int $callCount Número de llamadas detectadas
     * @return void
     */
    private function autoSilenceTable(Table $table, int $callCount): void
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
     * Escribir inmediatamente a Firebase Realtime Database
     * 
     * Método directo de escritura para testing y debugging.
     * 
     * @param WaiterCall $call Llamada a escribir
     * @return void
     */
    private function writeImmediateFirebase(WaiterCall $call): void
    {
        try {
            $testData = [
                'id' => 'call_' . $call->id,
                'message' => $call->message,
                'timestamp' => now()->toIso8601String(),
                'table_number' => (string)$call->table->number,
                'waiter_id' => (string)$call->waiter_id,
                'urgency' => $call->metadata['urgency'] ?? 'normal'
            ];

            $url = "https://mozoqr-7d32c-default-rtdb.firebaseio.com/waiters/{$call->waiter_id}/calls/call_{$call->id}.json";

            \Illuminate\Support\Facades\Http::timeout(3)->put($url, $testData);
        } catch (\Exception $e) {
            Log::warning('Immediate Firebase write failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ============================================================================
     * QUERY OPERATIONS - Migrado desde WaiterController FASE 3.2
     * ============================================================================
     */

    /**
     * Obtener llamadas pendientes del mozo
     * Filtradas por negocio activo
     */
    public function getPendingCalls(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            $businessId = $waiter->business_id ?? $waiter->active_business_id;

            if (!$businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un negocio activo seleccionado'
                ], 400);
            }

            $calls = WaiterCall::where('waiter_id', $waiter->id)
                ->where('status', 'pending')
                ->whereHas('table', function ($q) use ($businessId) {
                    $q->where('business_id', $businessId);
                })
                ->with(['table'])
                ->orderBy('called_at', 'desc')
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
                        'ip_address' => $call->ip_address
                    ];
                });

            return response()->json([
                'success' => true,
                'pending_calls' => $calls,
                'count' => $calls->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting pending calls', [
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo las llamadas pendientes'
            ], 500);
        }
    }

    /**
     * Obtener llamadas recientes (últimas 50)
     * Incluye todos los estados: pending, acknowledged, completed
     */
    public function getRecentCalls(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            $businessId = $waiter->business_id ?? $waiter->active_business_id;

            if (!$businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un negocio activo seleccionado'
                ], 400);
            }

            $calls = WaiterCall::where('waiter_id', $waiter->id)
                ->whereHas('table', function ($q) use ($businessId) {
                    $q->where('business_id', $businessId);
                })
                ->with(['table'])
                ->orderBy('called_at', 'desc')
                ->take(50)
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
                        'status' => $call->status,
                        'called_at' => $call->called_at,
                        'acknowledged_at' => $call->acknowledged_at,
                        'completed_at' => $call->completed_at,
                        'minutes_ago' => $call->called_at->diffInMinutes(now())
                    ];
                });

            return response()->json([
                'success' => true,
                'recent_calls' => $calls,
                'count' => $calls->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting recent calls', [
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo las llamadas recientes'
            ], 500);
        }
    }

    /**
     * Resincronizar llamada con Firebase
     * Reescribe el estado actual en Firebase (útil para debugging)
     */
    public function resyncCall(Request $request, $callId): JsonResponse
    {
        $waiter = Auth::user();
        try {
            $call = WaiterCall::with(['table','waiter'])->find($callId);
            if (!$call) {
                return response()->json([
                    'success' => false,
                    'message' => 'Llamada no encontrada'
                ], 404);
            }

            // Autorización: debe ser el mozo asignado o el mozo actual de la mesa
            $isAssignedWaiter = ((int)$call->waiter_id === (int)$waiter->id);
            $isCurrentTableWaiter = ((int)($call->table->active_waiter_id ?? 0) === (int)$waiter->id);
            if (!($isAssignedWaiter || $isCurrentTableWaiter)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para resincronizar esta llamada'
                ], 403);
            }

            // Sincronizar con Firebase según estado
            if ($call->status === 'completed') {
                $this->unifiedFirebaseService->removeCall($call);
            } else {
                $event = $call->status === 'acknowledged' ? 'acknowledged' : 'created';
                $this->unifiedFirebaseService->writeCall($call, $event);
            }

            return response()->json([
                'success' => true,
                'message' => 'Re-sincronizado con Firebase',
                'status' => $call->status
            ]);

        } catch (\Throwable $t) {
            Log::error('Error resyncing call', [
                'call_id' => $callId,
                'error' => $t->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error en resincronización'
            ], 500);
        }
    }

    /**
     * Crear llamada manualmente desde admin/mozo
     * Requiere que la mesa tenga mozo asignado
     */
    public function createManualCall(Request $request, Table $table): JsonResponse
    {
        try {
            $request->validate([
                'message' => 'sometimes|string|max:255'
            ]);

            if (!$table->active_waiter_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa no tiene un mozo asignado actualmente'
                ], 422);
            }

            // Crear la llamada
            $call = WaiterCall::create([
                'table_id' => $table->id,
                'waiter_id' => $table->active_waiter_id,
                'message' => $request->input('message', 'El cliente solicita atención'),
                'called_at' => now(),
                'status' => 'pending',
                'ip_address' => $request->ip()
            ]);

            // Notificación inmediata en Firebase
            try {
                $this->unifiedFirebaseService->writeCall($call, $table, [
                    'priority' => 'high',
                    'immediate' => true
                ]);
            } catch (\Exception $e) {
                Log::warning('Firebase notification failed', [
                    'call_id' => $call->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Llamada enviada exitosamente al mozo',
                'call' => [
                    'id' => $call->id,
                    'message' => $call->message,
                    'called_at' => $call->called_at
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating call', [
                'table_id' => $table->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error enviando la llamada'
            ], 500);
        }
    }
}

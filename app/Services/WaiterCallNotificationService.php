<?php

namespace App\Services;

use App\Models\WaiterCall;
use Illuminate\Support\Facades\Log;

/**
 * WaiterCallNotificationService - Notificaciones de llamadas de mesa
 *
 * Responsabilidades:
 * 1. Procesar eventos de llamadas (new, acknowledged, completed)
 * 2. Escribir datos en Firebase Realtime Database
 * 3. Actualizar índices (waiters, tables, businesses)
 * 4. Enviar notificaciones FCM a mozos
 *
 * Reemplaza: UnifiedFirebaseService.php
 */
class WaiterCallNotificationService
{
    private FirebaseNotificationService $firebase;
    private TokenManager $tokenManager;

    public function __construct(
        FirebaseNotificationService $firebase,
        TokenManager $tokenManager
    ) {
        $this->firebase = $firebase;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Procesar nueva llamada de mesa
     *
     * @param WaiterCall $call
     * @return bool
     */
    public function processNewCall(WaiterCall $call): bool
    {
        try {
            // Cargar relaciones necesarias
            $call->load(['table', 'waiter']);

            // Solo procesar si está pendiente
            if ($call->status !== 'pending') {
                Log::info('Skipping notification for non-pending call', [
                    'call_id' => $call->id,
                    'status' => $call->status
                ]);
                return false;
            }

            // 1. Escribir en Firebase RTDB
            $this->writeCallToFirebase($call, 'created');

            // 2. Actualizar índices en paralelo
            $this->updateWaiterIndex($call);
            $this->updateTableIndex($call);
            $this->updateBusinessIndex($call);

            // 3. Enviar notificación al mozo
            $this->sendNotificationToWaiter($call);

            Log::info('New waiter call processed successfully', [
                'call_id' => $call->id,
                'waiter_id' => $call->waiter_id,
                'table_number' => $call->table?->number
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to process new waiter call', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Procesar llamada reconocida por mozo
     *
     * @param WaiterCall $call
     * @return bool
     */
    public function processAcknowledgedCall(WaiterCall $call): bool
    {
        try {
            $call->load(['table', 'waiter']);

            // Actualizar datos en Firebase
            $this->writeCallToFirebase($call, 'acknowledged');

            // Actualizar índices
            $this->updateWaiterIndex($call);
            $this->updateTableIndex($call);
            $this->updateBusinessIndex($call);

            Log::info('Waiter call acknowledged', [
                'call_id' => $call->id,
                'waiter_id' => $call->waiter_id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to process acknowledged call', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Procesar llamada completada
     *
     * @param WaiterCall $call
     * @return bool
     */
    public function processCompletedCall(WaiterCall $call): bool
    {
        try {
            $call->load(['table', 'waiter']);

            // Eliminar de active_calls
            $this->firebase->deleteFromPath("active_calls/{$call->id}");

            // Actualizar índices (marca como sin llamadas activas)
            $this->updateWaiterIndex($call);
            $this->updateTableIndex($call);
            $this->updateBusinessIndex($call);

            Log::info('Waiter call completed and removed', [
                'call_id' => $call->id,
                'waiter_id' => $call->waiter_id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to process completed call', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    // ========================================================================
    // MÉTODOS PRIVADOS - FIREBASE RTDB
    // ========================================================================

    /**
     * Escribir llamada en Firebase Realtime Database
     *
     * @param WaiterCall $call
     * @param string $eventType
     * @return void
     */
    private function writeCallToFirebase(WaiterCall $call, string $eventType): void
    {
        $callData = [
            // Información básica
            'id' => (string)$call->id,
            'table_id' => (string)$call->table_id,
            'table_number' => (int)($call->table?->number ?? 0),
            'waiter_id' => (string)$call->waiter_id,
            'waiter_name' => $call->waiter?->name ?? 'Mozo',

            // Estados y mensajes
            'status' => $call->status,
            'message' => $call->message,
            'urgency' => $call->metadata['urgency'] ?? 'normal',

            // Timestamps
            'called_at' => $call->called_at->timestamp * 1000,
            'acknowledged_at' => $call->acknowledged_at?->timestamp * 1000,
            'completed_at' => $call->completed_at?->timestamp * 1000,

            // Metadatos
            'response_time_seconds' => $call->response_time,
            'source' => $call->metadata['source'] ?? 'qr_page',
            'business_id' => (string)($call->table?->business_id ?? ''),

            // Info de seguridad y tracking
            'client_info' => [
                'ip_address' => $call->metadata['ip_address'] ?? null,
                'user_agent' => $call->metadata['user_agent'] ?? null,
                'source_type' => $call->metadata['source'] ?? 'unknown',
            ],

            // Metadata del evento
            'last_updated' => now()->timestamp * 1000,
            'event_type' => $eventType
        ];

        $this->firebase->writeToPath("active_calls/{$call->id}", $callData);
    }

    /**
     * Actualizar índice de mozo en Firebase
     *
     * @param WaiterCall $call
     * @return void
     */
    private function updateWaiterIndex(WaiterCall $call): void
    {
        try {
            // Obtener todas las llamadas activas del mozo desde DB
            $activeCalls = \App\Models\WaiterCall::where('waiter_id', $call->waiter_id)
                ->whereIn('status', ['pending', 'acknowledged'])
                ->pluck('id')
                ->map(fn($id) => (string)$id)
                ->toArray();

            $pendingCount = \App\Models\WaiterCall::where('waiter_id', $call->waiter_id)
                ->where('status', 'pending')
                ->count();

            $waiterData = [
                'active_calls' => array_values($activeCalls),
                'stats' => [
                    'pending_count' => $pendingCount,
                    'total_active_calls' => count($activeCalls),
                    'last_update' => now()->timestamp * 1000
                ]
            ];

            $this->firebase->writeToPath("waiters/{$call->waiter_id}", $waiterData);

        } catch (\Exception $e) {
            Log::warning('Failed to update waiter index', [
                'waiter_id' => $call->waiter_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar índice de mesa en Firebase
     *
     * @param WaiterCall $call
     * @return void
     */
    private function updateTableIndex(WaiterCall $call): void
    {
        try {
            $tableData = [
                'current_call' => $call->status === 'completed' ? null : (string)$call->id,
                'last_call' => (string)$call->id,
                'stats' => [
                    'last_call_at' => $call->called_at->timestamp * 1000,
                    'status' => $call->status
                ]
            ];

            $this->firebase->writeToPath("tables/{$call->table_id}", $tableData);

        } catch (\Exception $e) {
            Log::warning('Failed to update table index', [
                'table_id' => $call->table_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar índice de negocio en Firebase
     *
     * @param WaiterCall $call
     * @return void
     */
    private function updateBusinessIndex(WaiterCall $call): void
    {
        try {
            $businessId = $call->table->business_id;

            // Obtener todas las llamadas activas del negocio
            $allActiveCalls = \App\Models\WaiterCall::whereHas('table', function($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                })
                ->whereIn('status', ['pending', 'acknowledged'])
                ->pluck('id')
                ->map(fn($id) => (string)$id)
                ->toArray();

            $pendingCount = \App\Models\WaiterCall::whereHas('table', function($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                })
                ->where('status', 'pending')
                ->count();

            $businessData = [
                'active_calls' => array_values($allActiveCalls),
                'stats' => [
                    'total_pending' => $pendingCount,
                    'total_active_calls' => count($allActiveCalls),
                    'last_update' => now()->timestamp * 1000
                ]
            ];

            $this->firebase->writeToPath("businesses/{$businessId}", $businessData);

        } catch (\Exception $e) {
            Log::warning('Failed to update business index', [
                'business_id' => $call->table->business_id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar notificación FCM al mozo asignado
     *
     * @param WaiterCall $call
     * @return void
     */
    private function sendNotificationToWaiter(WaiterCall $call): void
    {
        try {
            $tableNumber = $call->table?->number ?? '?';
            $title = "🔔 Mesa {$tableNumber}";
            $body = $call->message ?: 'Nueva llamada';

            $data = [
                'type' => 'waiter_call',
                'call_id' => (string)$call->id,
                'table_id' => (string)$call->table_id,
                'table_number' => (string)$tableNumber,
                'waiter_id' => (string)$call->waiter_id,
                'status' => $call->status,
                'urgency' => $call->metadata['urgency'] ?? 'normal',
                'timestamp' => (string)now()->timestamp,
                'source' => 'waiter_call_system'
            ];

            // Enviar usando FirebaseNotificationService
            $result = $this->firebase->sendToUser(
                $call->waiter_id,
                $title,
                $body,
                $data,
                'high' // Alta prioridad para llamadas de mesa
            );

            Log::info('Waiter notification sent', [
                'call_id' => $call->id,
                'waiter_id' => $call->waiter_id,
                'sent' => $result['sent'] ?? 0,
                'total' => $result['total'] ?? 0
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send waiter notification', [
                'call_id' => $call->id,
                'waiter_id' => $call->waiter_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar todos los datos de Firebase de un negocio
     * Se ejecuta cuando se elimina un negocio desde el panel de admin
     *
     * @param int $businessId
     * @return array
     */
    public function deleteBusinessData(int $businessId): array
    {
        $deletedPaths = [];
        $errors = [];

        try {
            Log::info('Starting Firebase cleanup for business', ['business_id' => $businessId]);

            // 1. Eliminar datos principales del negocio
            try {
                $this->firebase->deleteFromPath("businesses_staff/business_{$businessId}");
                $deletedPaths[] = "businesses_staff/business_{$businessId}";
            } catch (\Exception $e) {
                $errors[] = "businesses_staff/business_{$businessId}: " . $e->getMessage();
            }

            // 2. Eliminar índice del negocio
            try {
                $this->firebase->deleteFromPath("businesses/{$businessId}");
                $deletedPaths[] = "businesses/{$businessId}";
            } catch (\Exception $e) {
                $errors[] = "businesses/{$businessId}: " . $e->getMessage();
            }

            // 3. Eliminar datos de staff
            $staffUsers = \App\Models\Staff::where('business_id', $businessId)->pluck('user_id')->unique();
            foreach ($staffUsers as $userId) {
                try {
                    $this->firebase->deleteFromPath("users_staff/{$userId}");
                    $this->firebase->deleteFromPath("waiters/{$userId}");
                    $deletedPaths[] = "users_staff/{$userId}";
                    $deletedPaths[] = "waiters/{$userId}";
                } catch (\Exception $e) {
                    $errors[] = "user_staff/{$userId}: " . $e->getMessage();
                }
            }

            // 4. Eliminar llamadas activas
            $activeCalls = \App\Models\WaiterCall::whereHas('table', function($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })->pluck('id');

            foreach ($activeCalls as $callId) {
                try {
                    $this->firebase->deleteFromPath("active_calls/{$callId}");
                    $deletedPaths[] = "active_calls/{$callId}";
                } catch (\Exception $e) {
                    $errors[] = "active_calls/{$callId}: " . $e->getMessage();
                }
            }

            // 5. Eliminar índices de mesas
            $tables = \App\Models\Table::where('business_id', $businessId)->pluck('id');
            foreach ($tables as $tableId) {
                try {
                    $this->firebase->deleteFromPath("tables/{$tableId}");
                    $deletedPaths[] = "tables/{$tableId}";
                } catch (\Exception $e) {
                    $errors[] = "tables/{$tableId}: " . $e->getMessage();
                }
            }

            Log::info('Firebase cleanup completed', [
                'business_id' => $businessId,
                'deleted_paths' => count($deletedPaths),
                'errors' => count($errors)
            ]);

            return [
                'success' => true,
                'business_id' => $businessId,
                'deleted_paths' => $deletedPaths,
                'errors' => $errors,
                'summary' => [
                    'total_deleted' => count($deletedPaths),
                    'total_errors' => count($errors)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Firebase cleanup failed critically', [
                'business_id' => $businessId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'business_id' => $businessId,
                'deleted_paths' => $deletedPaths,
                'errors' => array_merge($errors, [$e->getMessage()]),
                'summary' => [
                    'total_deleted' => count($deletedPaths),
                    'total_errors' => count($errors) + 1
                ]
            ];
        }
    }
}

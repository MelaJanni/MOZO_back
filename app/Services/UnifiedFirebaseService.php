<?php

namespace App\Services;

use App\Models\WaiterCall;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class UnifiedFirebaseService
{
    private $baseUrl = 'https://mozoqr-7d32c-default-rtdb.firebaseio.com';
    /**
     * Mapeo de eventType interno -> tipo de data en FCM
     */
    private const EVENT_TYPE_MAP = [
        'created' => 'new_call',
        'acknowledged' => 'acknowledged',
        'completed' => 'completed',
    ];
    
    /**
     * 🔥 CREAR/ACTUALIZAR LLAMADA EN ESTRUCTURA UNIFICADA
     */
    public function writeCall(WaiterCall $call, string $eventType = 'created'): bool
    {
        try {
            // 1. 🎯 DATOS UNIFICADOS - Una sola fuente de verdad
            $unifiedCallData = [
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
                
                // Metadatos útiles
                'response_time_seconds' => $call->response_time,
                'source' => $call->metadata['source'] ?? 'qr_page',
                'business_id' => (string)($call->table?->business_id ?? ''),
                
                // 🛡️ Información de seguridad y tracking
                'client_info' => [
                    'ip_address' => $call->metadata['ip_address'] ?? null,
                    'user_agent' => $call->metadata['user_agent'] ?? null,
                    'source_type' => $call->metadata['source'] ?? 'unknown',
                    'timestamp' => $call->called_at->timestamp * 1000
                ],
                
                // Información completa de mesa
                'table' => [
                    'id' => (string)($call->table?->id ?? ''),
                    'number' => (int)($call->table?->number ?? 0),
                    'name' => $call->table?->name ?? (isset($call->table) ? ("Mesa ".($call->table->number ?? '')) : 'Mesa'),
                    'notifications_enabled' => $call->table?->notifications_enabled ?? true
                ],
                
                // Estado del mozo
                'waiter' => [
                    'id' => (string)$call->waiter_id,
                    'name' => $call->waiter?->name ?? 'Mozo',
                    'is_online' => true, // Podemos implementar esto después
                    'last_seen' => now()->timestamp * 1000
                ],
                
                // Metadata del evento
                'last_updated' => now()->timestamp * 1000,
                'event_type' => $eventType
            ];

            // 2. 🚀 ESCRITURAS PARALELAS - Máxima velocidad
            $promises = [
                // Core: Datos completos de la llamada
                $this->writeToPath("active_calls/{$call->id}", $unifiedCallData),
                
                // Índices rápidos
                $this->updateWaiterIndex($call),
                $this->updateTableIndex($call),
                $this->updateBusinessIndex($call)
            ];

            // Ejecutar todas las escrituras en paralelo
            $results = $this->executeParallel($promises);
            
            Log::info("Unified Firebase write completed", [
                'call_id' => $call->id,
                'event_type' => $eventType,
                'results' => array_count_values($results)
            ]);

            // 3. 📡 ENVIAR FCM UNIFICADO (solo para eventos soportados)
            $this->sendUnifiedFcmEvent($call, $eventType);

            return true;

        } catch (\Exception $e) {
            Log::error("Unified Firebase write failed", [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 🔥 ELIMINAR LLAMADA COMPLETADA
     */
    public function removeCall(WaiterCall $call): bool
    {
        try {
            // Enviar evento de completado antes de eliminar (si status completed)
            if ($call->status === 'completed') {
                $this->sendUnifiedFcmEvent($call, 'completed');
            }
            $promises = [
                // Eliminar datos principales
                $this->deleteFromPath("active_calls/{$call->id}"),
                
                // Actualizar índices
                $this->removeFromWaiterIndex($call),
                $this->removeFromTableIndex($call),
                $this->removeFromBusinessIndex($call)
            ];

            $this->executeParallel($promises);
            
            Log::info("Call removed from unified structure", ['call_id' => $call->id]);
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to remove call from unified structure", [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * LEGACY SHIM: writeCallStatus(tableId, data)
     * Mantiene compatibilidad con controladores que aún llamen a este método.
     * No tiene toda la información de la llamada, por lo que solo actualiza
     * un pequeño índice por mesa y registra el evento.
     */
    public function writeCallStatus($tableId, array $data): bool
    {
        try {
            $payload = [
                'status' => $data['status'] ?? 'unknown',
                'acknowledged_at' => $data['acknowledged_at'] ?? null,
                'completed_at' => $data['completed_at'] ?? null,
                'last_update' => now()->timestamp * 1000,
                'source' => 'legacy_shim'
            ];
            // Guardar estado minimal por mesa
            $this->writeToPath("tables/{$tableId}/stats", $payload);
            \Log::notice('UnifiedFirebaseService::writeCallStatus called (legacy shim)', [
                'table_id' => $tableId,
                'data' => $payload
            ]);
            return true;
        } catch (\Throwable $e) {
            \Log::warning('Legacy writeCallStatus failed', [
                'table_id' => $tableId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 📊 ACTUALIZAR ÍNDICE DE MOZO
     */
    private function updateWaiterIndex(WaiterCall $call): string
    {
        try {
            // Obtener todas las llamadas actuales del mozo desde la base de datos
            $allCalls = \App\Models\WaiterCall::where('waiter_id', $call->waiter_id)
                ->whereIn('status', ['pending', 'acknowledged'])
                ->pluck('id')
                ->map(fn($id) => (string)$id)
                ->toArray();
            
            // Contar solo las llamadas pendientes para el pending_count
            $pendingCallsCount = \App\Models\WaiterCall::where('waiter_id', $call->waiter_id)
                ->where('status', 'pending')
                ->count();

            // Estadísticas del mozo
            $stats = [
                'pending_count' => $pendingCallsCount, // Solo pendientes
                'total_active_calls' => count($allCalls), // Pendientes + Acknowledged
                'last_update' => now()->timestamp * 1000
            ];

            $waiterData = [
                'active_calls' => array_values($allCalls), // Pendientes + Acknowledged
                'stats' => $stats
            ];

            return $this->writeToPath("waiters/{$call->waiter_id}", $waiterData);

        } catch (\Exception $e) {
            Log::warning("Failed to update waiter index", [
                'waiter_id' => $call->waiter_id,
                'error' => $e->getMessage()
            ]);
            return "error";
        }
    }

    /**
     * 🏠 ACTUALIZAR ÍNDICE DE MESA
     */
    private function updateTableIndex(WaiterCall $call): string
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

            return $this->writeToPath("tables/{$call->table_id}", $tableData);

        } catch (\Exception $e) {
            Log::warning("Failed to update table index", [
                'table_id' => $call->table_id,
                'error' => $e->getMessage()
            ]);
            return "error";
        }
    }

    /**
     * 🏢 ACTUALIZAR ÍNDICE DE NEGOCIO
     */
    private function updateBusinessIndex(WaiterCall $call): string
    {
        try {
            $businessId = $call->table->business_id;
            
            // Obtener todas las llamadas activas del negocio desde la base de datos
            $allActiveCalls = \App\Models\WaiterCall::whereHas('table', function($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                })
                ->whereIn('status', ['pending', 'acknowledged'])
                ->pluck('id')
                ->map(fn($id) => (string)$id)
                ->toArray();
            
            // Contar solo las llamadas pendientes para total_pending
            $pendingCallsCount = \App\Models\WaiterCall::whereHas('table', function($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                })
                ->where('status', 'pending')
                ->count();

            $businessData = [
                'active_calls' => array_values($allActiveCalls), // Pendientes + Acknowledged
                'stats' => [
                    'total_pending' => $pendingCallsCount, // Solo pendientes
                    'total_active_calls' => count($allActiveCalls), // Pendientes + Acknowledged
                    'last_update' => now()->timestamp * 1000
                ]
            ];

            return $this->writeToPath("businesses/{$businessId}", $businessData);

        } catch (\Exception $e) {
            Log::warning("Failed to update business index", [
                'business_id' => $call->table->business_id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return "error";
        }
    }

    /**
     * 🚀 ESCRITURA A FIREBASE
     */
    private function writeToPath(string $path, array $data): string
    {
        try {
            $url = "{$this->baseUrl}/{$path}.json";
            $response = Http::timeout(3)->put($url, $data);
            
            return $response->successful() ? "success" : "failed";

        } catch (\Exception $e) {
            Log::warning("Firebase write failed", [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return "error";
        }
    }

    /**
     * 🗑️ ELIMINAR DE FIREBASE
     */
    private function deleteFromPath(string $path): string
    {
        try {
            $url = "{$this->baseUrl}/{$path}.json";
            $response = Http::timeout(3)->delete($url);
            
            return $response->successful() ? "success" : "failed";

        } catch (\Exception $e) {
            Log::warning("Firebase delete failed", [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return "error";
        }
    }

    /**
     * ⚡ EJECUTAR OPERACIONES EN PARALELO
     */
    private function executeParallel(array $promises): array
    {
        // Por simplicidad, ejecutamos secuencialmente 
        // En producción podrías usar Guzzle Pool para verdadero paralelismo
        return $promises;
    }

    /**
     * 📋 OBTENER LLAMADAS ACTIVAS DEL MOZO
     */
    private function getWaiterActiveCalls(int $waiterId): array
    {
        try {
            $cacheKey = "waiter_calls_{$waiterId}";
            
            return Cache::remember($cacheKey, 30, function() use ($waiterId) {
                $url = "{$this->baseUrl}/waiters/{$waiterId}/active_calls.json";
                $response = Http::timeout(2)->get($url);
                
                return $response->successful() ? ($response->json() ?? []) : [];
            });

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 🏢 OBTENER LLAMADAS ACTIVAS DEL NEGOCIO
     */
    private function getBusinessActiveCalls(int $businessId): array
    {
        try {
            $cacheKey = "business_calls_{$businessId}";
            
            return Cache::remember($cacheKey, 30, function() use ($businessId) {
                $url = "{$this->baseUrl}/businesses/{$businessId}/active_calls.json";
                $response = Http::timeout(2)->get($url);
                
                return $response->successful() ? ($response->json() ?? []) : [];
            });

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 🗑️ REMOVER DE ÍNDICES (para llamadas completadas)
     */
    private function removeFromWaiterIndex(WaiterCall $call): string
    {
        return $this->updateWaiterIndex($call); // Usa la misma lógica
    }

    private function removeFromTableIndex(WaiterCall $call): string
    {
        return $this->updateTableIndex($call); // Usa la misma lógica
    }

    private function removeFromBusinessIndex(WaiterCall $call): string
    {
        return $this->updateBusinessIndex($call); // Usa la misma lógica
    }

    /**
     * 🧪 TEST DE CONECTIVIDAD
     */
    public function testConnection(): array
    {
        try {
            $testData = [
                'test' => true,
                'timestamp' => now()->timestamp,
                'message' => 'Unified Firebase test'
            ];

            $result = $this->writeToPath('test/connection', $testData);

            return [
                'status' => $result === 'success' ? 'connected' : 'failed',
                'timestamp' => now()->toISOString(),
                'structure' => 'unified'
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * 📡 Enviar evento FCM UNIFIED (new_call / acknowledged / completed)
     */
    private function sendUnifiedFcmEvent(WaiterCall $call, string $eventType): void
    {
        if (!isset(self::EVENT_TYPE_MAP[$eventType])) {
            return; // Ignorar eventos no mapeados
        }

        try {
            $firebaseService = app(\App\Services\FirebaseService::class);

            // Tokens de mozo (receptor principal). En futuro: también tokens de clientes si existen.
            $waiterTokens = \App\Models\DeviceToken::where('user_id', $call->waiter_id)
                ->pluck('token')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            // 🧪 DEBUG: Verificar tokens por plataforma
            $tokensByPlatform = \App\Models\DeviceToken::where('user_id', $call->waiter_id)
                ->get()
                ->groupBy('platform');
            
            \Log::info('🧪 DEBUG: Waiter tokens by platform', [
                'call_id' => $call->id,
                'waiter_id' => $call->waiter_id,
                'total_tokens' => count($waiterTokens),
                'web_tokens' => count($tokensByPlatform['web'] ?? []),
                'android_tokens' => count($tokensByPlatform['android'] ?? []),
                'ios_tokens' => count($tokensByPlatform['ios'] ?? []),
                'token_previews' => array_map(fn($t) => substr($t, 0, 20) . '...', $waiterTokens)
            ]);

            if (empty($waiterTokens)) {
                \Log::info('Unified FCM skipped (no waiter tokens)', [
                    'call_id' => $call->id,
                    'event_type' => $eventType
                ]);
                return;
            }

            $data = [
                'type' => self::EVENT_TYPE_MAP[$eventType],
                'call_id' => (string)$call->id,
                'table_id' => (string)$call->table_id,
                'table_number' => (string)($call->table?->number ?? ''),
                'waiter_id' => (string)$call->waiter_id,
                'urgency' => $call->metadata['urgency'] ?? 'normal',
                'status' => $call->status,
                'timestamp' => (string) now()->timestamp,
                'source' => 'unified'
            ];

            // Mensajes diferenciados
            switch ($eventType) {
                case 'created':
                    $body = $call->message ?: "Mesa ".($call->table?->number ?? '')." solicita atención";
                    break;
                case 'acknowledged':
                    $body = "Mesa ".($call->table?->number ?? '')." reconocida";
                    break;
                case 'completed':
                    $body = "Mesa ".($call->table?->number ?? '')." completada";
                    break;
                default:
                    $body = $call->message ?: 'Actualización de llamada';
            }

            $firebaseService->sendUnifiedNotificationToTokens(
                $waiterTokens,
                (int)($call->table?->number ?? 0),
                $body,
                $data
            );

            \Log::info('Unified FCM event dispatched', [
                'call_id' => $call->id,
                'event_type' => $eventType,
                'tokens' => count($waiterTokens)
            ]);

        } catch (\Exception $e) {
            \Log::warning('Failed to send unified FCM event', [
                'call_id' => $call->id ?? null,
                'event_type' => $eventType,
                'error' => $e->getMessage()
            ]);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\IpBlock;
use App\Models\WaiterCall;
use App\Models\TableSilence;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para gestión de bloqueo de IPs (anti-spam)
 * 
 * Responsabilidades:
 * - Bloquear IPs sospechosas
 * - Desbloquear IPs
 * - Listar IPs bloqueadas
 * - Debug de estado de IPs
 * - Forzar desbloqueo
 */
class IpBlockController extends Controller
{
    /**
     * Bloquea una IP por comportamiento sospechoso
     */
    public function blockIp(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        $request->validate([
            'call_id' => 'required|integer|exists:waiter_calls,id',
            'reason' => 'sometimes|in:spam,abuse,manual',
            'duration_hours' => 'sometimes|integer|min:1|max:720', // Máximo 30 días
            'notes' => 'sometimes|string|max:500',
            'also_silence_table' => 'sometimes|boolean'
        ]);

        try {
            // Obtener la llamada para extraer la IP
            $call = WaiterCall::with(['table'])->find($request->call_id);
            
            if (!$call) {
                return response()->json([
                    'success' => false,
                    'message' => 'Llamada no encontrada'
                ], 404);
            }

            // Verificar que el mozo tenga acceso a esta mesa
            if ($call->waiter_id !== $waiter->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para bloquear esta IP'
                ], 403);
            }

            // Extraer IP del metadata de la llamada
            $ipAddress = $call->metadata['ip_address'] ?? null;
            
            if (!$ipAddress) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener la IP de esta llamada'
                ], 400);
            }

            // Verificar si ya está bloqueada
            $existingBlock = IpBlock::where('ip_address', $ipAddress)
                ->where('business_id', $call->table->business_id)
                ->active()
                ->first();

            if ($existingBlock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta IP ya está bloqueada',
                    'existing_block' => [
                        'reason' => $existingBlock->reason,
                        'blocked_at' => $existingBlock->blocked_at,
                        'remaining_time' => $existingBlock->formatted_remaining_time
                    ]
                ], 409);
            }

            // Crear (o reactivar) el bloqueo
            $durationHours = $request->input('duration_hours', 24);
            $expiresAt = $durationHours ? now()->addHours($durationHours) : null;
            
            // Si existe un bloqueo previo (histórico) para esta IP (ya desbloqueado) lo reactivamos
            $historicalBlock = IpBlock::where('ip_address', $ipAddress)
                ->where('business_id', $call->table->business_id)
                ->whereNotNull('unblocked_at')
                ->orderByDesc('blocked_at')
                ->first();

            if ($historicalBlock) {
                $historicalBlock->update([
                    'reason' => $request->input('reason', 'spam'),
                    'notes' => $request->input('notes', "Bloqueado por spam desde mesa {$call->table->number}"),
                    'blocked_at' => now(),
                    'expires_at' => $expiresAt,
                    'unblocked_at' => null,
                    'metadata' => array_merge($historicalBlock->metadata ?? [], [
                        'reactivated_at' => now()->toIso8601String(),
                        'reactivated_call_id' => $call->id,
                        'table_id' => $call->table_id,
                        'user_agent' => request()->userAgent(),
                        'blocked_from_call' => true
                    ])
                ]);
                $block = $historicalBlock->fresh();
            } else {
                $block = IpBlock::blockIp($ipAddress, $call->table->business_id, $waiter->id, [
                    'reason' => $request->input('reason', 'spam'),
                    'notes' => $request->input('notes', "Bloqueado por spam desde mesa {$call->table->number}"),
                    'expires_at' => $expiresAt,
                    'metadata' => [
                        'call_id' => $call->id,
                        'table_id' => $call->table_id,
                        'user_agent' => request()->userAgent(),
                        'blocked_from_call' => true
                    ]
                ]);
            }

            $tableSilenced = false;
            if ($request->boolean('also_silence_table')) {
                $activeSilence = TableSilence::where('table_id', $call->table_id)->active()->first();
                if (!$activeSilence) {
                    TableSilence::create([
                        'table_id' => $call->table_id,
                        'silenced_by' => $waiter->id,
                        'reason' => 'manual',
                        'silenced_at' => now(),
                        'notes' => 'Silenciado junto al bloqueo de IP (petición explícita)'
                    ]);
                    $tableSilenced = true;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'IP bloqueada exitosamente. El dispositivo no podrá enviar más notificaciones.',
                'block' => [
                    'id' => $block->id,
                    'ip_address' => $ipAddress,
                    'reason' => $block->reason,
                    'blocked_at' => $block->blocked_at,
                    'expires_at' => $block->expires_at,
                    'duration' => $durationHours ? "{$durationHours} horas" : 'Permanente',
                    'notes' => $block->notes
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error blocking IP', [
                'call_id' => $request->call_id,
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error procesando el bloqueo de IP'
            ], 500);
        }
    }

    /**
     * Desbloquea una IP previamente bloqueada
     */
    public function unblockIp(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        $request->validate([
            'ip_address' => 'required|ip',
            'business_id' => 'sometimes|integer|exists:businesses,id'
        ]);

        try {
            $ipAddress = $request->ip_address;
            $businessId = $request->input('business_id', $waiter->business_id);

            // Verificar que el mozo tenga acceso al negocio
            if (!$waiter->businesses()->where('businesses.id', $businessId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este negocio'
                ], 403);
            }

            $block = IpBlock::where('ip_address', $ipAddress)
                ->where('business_id', $businessId)
                ->active()
                ->first();

            if (!$block) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta IP no está bloqueada'
                ], 404);
            }

            $block->unblock();

            return response()->json([
                'success' => true,
                'message' => 'IP desbloqueada exitosamente',
                'unblocked_at' => $block->unblocked_at
            ]);

        } catch (\Exception $e) {
            Log::error('Error unblocking IP', [
                'ip_address' => $request->ip_address,
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error desbloqueando la IP'
            ], 500);
        }
    }

    /**
     * Lista todas las IPs bloqueadas del negocio
     */
    public function getBlockedIps(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            if (!$waiter->business_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un negocio activo seleccionado'
                ], 400);
            }

            $query = IpBlock::with(['blockedBy'])
                ->where('business_id', $waiter->business_id);

            // Filtros opcionales
            if ($request->has('active_only') && $request->boolean('active_only')) {
                $query->active();
            }

            if ($request->has('reason')) {
                $query->where('reason', $request->reason);
            }

            $blocks = $query->orderBy('blocked_at', 'desc')
                ->paginate($request->input('per_page', 20));

            $formattedBlocks = $blocks->getCollection()->map(function ($block) {
                return [
                    'id' => $block->id,
                    'ip_address' => $block->ip_address,
                    'reason' => $block->reason,
                    'notes' => $block->notes,
                    'blocked_by' => $block->blockedBy->name ?? 'Sistema',
                    'blocked_at' => $block->blocked_at,
                    'expires_at' => $block->expires_at,
                    'unblocked_at' => $block->unblocked_at,
                    'is_active' => $block->isActive(),
                    'remaining_time' => $block->formatted_remaining_time,
                    'metadata' => $block->metadata
                ];
            });

            return response()->json([
                'success' => true,
                'blocked_ips' => $formattedBlocks,
                'pagination' => [
                    'current_page' => $blocks->currentPage(),
                    'last_page' => $blocks->lastPage(),
                    'per_page' => $blocks->perPage(),
                    'total' => $blocks->total()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting blocked IPs', [
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo las IPs bloqueadas'
            ], 500);
        }
    }

    /**
     * Debug: Muestra estado detallado de una IP
     */
    public function debugIpStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ip_address' => 'sometimes|ip',
            'business_id' => 'sometimes|integer|exists:businesses,id'
        ]);

        try {
            $ipAddress = $request->input('ip_address', $request->ip());
            $waiter = Auth::user();
            $businessId = $request->input('business_id', $waiter?->business_id);

            if (!$businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo determinar el business_id (proporcione business_id o seleccione un negocio activo)'
                ], 400);
            }

            // 1. Verificar todos los registros de bloqueo para esta IP
            $allBlocks = IpBlock::where('ip_address', $ipAddress)
                ->where('business_id', $businessId)
                ->orderBy('created_at', 'desc')
                ->get(['id', 'blocked_at', 'unblocked_at', 'expires_at', 'reason', 'notes']);

            // 2. Verificar específicamente si está bloqueada
            $isBlocked = IpBlock::isIpBlocked($ipAddress, $businessId);

            // 3. Obtener el bloqueo activo si existe
            $activeBlock = IpBlock::where('ip_address', $ipAddress)
                ->where('business_id', $businessId)
                ->active()
                ->first();

            // 4. Verificar manualmente las condiciones del scope active
            $manualActiveBlocks = IpBlock::where('ip_address', $ipAddress)
                ->where('business_id', $businessId)
                ->get()
                ->map(function($block) {
                    return [
                        'id' => $block->id,
                        'unblocked_at' => $block->unblocked_at,
                        'expires_at' => $block->expires_at,
                        'is_unblocked' => !is_null($block->unblocked_at),
                        'is_expired' => $block->expires_at && $block->expires_at->isPast(),
                        'should_be_active' => is_null($block->unblocked_at) && (is_null($block->expires_at) || $block->expires_at->isFuture()),
                        'isActive_method' => $block->isActive()
                    ];
                });

            return response()->json([
                'debug_info' => [
                    'checked_ip' => $ipAddress,
                    'business_id' => $businessId,
                    'current_timestamp' => now()->toISOString(),
                    'is_blocked_result' => $isBlocked,
                    'total_blocks_found' => $allBlocks->count(),
                    'active_block_found' => $activeBlock ? true : false,
                    'active_block_id' => $activeBlock?->id
                ],
                'all_blocks' => $allBlocks,
                'active_block' => $activeBlock,
                'manual_analysis' => $manualActiveBlocks,
                'scope_sql' => [
                    'active_scope' => IpBlock::where('ip_address', $ipAddress)
                        ->where('business_id', $businessId)
                        ->active()
                        ->toSql()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Fuerza el desbloqueo de una IP (admin only)
     */
    public function forceUnblockIp(Request $request): JsonResponse
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'business_id' => 'sometimes|integer|exists:businesses,id'
        ]);

        try {
            $ipAddress = $request->ip_address;
            $waiter = Auth::user();
            $businessId = $request->input('business_id', $waiter?->business_id);

            if (!$businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo determinar el business_id para desbloquear (proporcione business_id o seleccione un negocio activo)'
                ], 400);
            }

            // 1. Encontrar TODOS los bloqueos activos para esta IP
            $activeBlocks = IpBlock::where('ip_address', $ipAddress)
                ->where('business_id', $businessId)
                ->whereNull('unblocked_at')
                ->get();

            if ($activeBlocks->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Esta IP ya no está bloqueada',
                    'found_blocks' => 0
                ]);
            }

            // 2. Desbloquear todos los registros activos
            $unblocked = 0;
            foreach ($activeBlocks as $block) {
                $block->update(['unblocked_at' => now()]);
                $unblocked++;
            }

            // 3. Verificar que efectivamente se desbloqueó
            $stillBlocked = IpBlock::isIpBlocked($ipAddress, $businessId);

            return response()->json([
                'success' => true,
                'message' => "Se desbloquearon {$unblocked} registros para la IP {$ipAddress}",
                'details' => [
                    'ip_address' => $ipAddress,
                    'business_id' => $businessId,
                    'blocks_unblocked' => $unblocked,
                    'still_blocked_after_unblock' => $stillBlocked,
                    'unblocked_at' => now(),
                    'unblocked_blocks' => $activeBlocks->map(fn($block) => [
                        'id' => $block->id,
                        'originally_blocked_at' => $block->blocked_at,
                        'reason' => $block->reason
                    ])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}

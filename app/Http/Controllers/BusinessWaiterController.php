<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Table;
use App\Models\Staff;
use App\Models\WaiterCall;
use App\Services\StaffNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Controlador para operaciones multi-tenant de mozos y negocios
 * 
 * Responsabilidades:
 * - Listar negocios del mozo
 * - Obtener mesas de un negocio específico
 * - Unirse a un negocio
 * - Cambiar negocio activo
 */
class BusinessWaiterController extends Controller
{
    /**
     * Lista todos los negocios del mozo autenticado
     */
    public function getWaiterBusinesses(Request $request): JsonResponse
    {
        try {
            $waiter = Auth::user();
            
            // Obtener negocios donde el mozo es staff confirmado
            $staffMemberships = Staff::where('user_id', $waiter->id)
                ->where('status', 'confirmed')
                ->with('business')
                ->get();
            
            $businesses = $staffMemberships->map(function($staff) use ($waiter) {
                $business = $staff->business;
                
                // Estadísticas de mesas
                $totalTables = Table::where('business_id', $business->id)->count();
                $assignedToMe = Table::where('business_id', $business->id)
                    ->where('active_waiter_id', $waiter->id)
                    ->count();
                $available = Table::where('business_id', $business->id)
                    ->whereNull('active_waiter_id')
                    ->count();
                $occupiedByOthers = $totalTables - $assignedToMe - $available;
                
                // Llamadas pendientes en este negocio
                $pendingCalls = \App\Models\WaiterCall::where('business_id', $business->id)
                    ->where('waiter_id', $waiter->id)
                    ->where('status', 'pending')
                    ->count();
                
                // Determinar si es el negocio activo
                $isActive = ($waiter->active_business_id && $waiter->active_business_id == $business->id) 
                    || (!$waiter->active_business_id && $waiter->business_id == $business->id);
                
                return [
                    'id' => $business->id,
                    'name' => $business->name,
                    'code' => $business->code,
                    'address' => $business->address,
                    'phone' => $business->phone,
                    'logo' => $business->logo,
                    'is_active' => $isActive,
                    'membership' => [
                        'joined_at' => $staff->created_at,
                        'status' => 'active',
                        'role' => 'waiter'
                    ],
                    'tables_stats' => [
                        'total' => $totalTables,
                        'assigned_to_me' => $assignedToMe,
                        'available' => $available,
                        'occupied_by_others' => $occupiedByOthers
                    ],
                    'pending_calls' => $pendingCalls,
                    'can_work' => true
                ];
            });
            
            return response()->json([
                'businesses' => $businesses,
                'total_count' => $businesses->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en getWaiterBusinesses: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener negocios',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene todas las mesas de un negocio específico
     */
    public function getBusinessTables(Request $request, $businessId): JsonResponse
    {
        try {
            $waiter = Auth::user();
            
            // Verificar que el mozo es staff del negocio
            $staffMembership = Staff::where('user_id', $waiter->id)
                ->where('business_id', $businessId)
                ->where('status', 'confirmed')
                ->first();
            
            if (!$staffMembership) {
                return response()->json([
                    'error' => 'No tienes acceso a este negocio'
                ], 403);
            }
            
            // Obtener todas las mesas del negocio
            $tables = Table::where('business_id', $businessId)
                ->with(['activeWaiter'])
                ->get();
            
            $tablesData = $tables->map(function($table) use ($waiter) {
                $isAssignedToMe = $table->active_waiter_id == $waiter->id;
                $pendingCalls = $table->pendingCalls;
                $pendingCount = $pendingCalls->count();
                
                // Última llamada pendiente
                $latestCall = $pendingCalls->first();
                $minutesAgo = $latestCall 
                    ? \Carbon\Carbon::parse($latestCall->called_at)->diffInMinutes(\Carbon\Carbon::now())
                    : null;
                
                // Información de silenciamiento
                $isSilenced = false;
                $remainingTime = null;
                $silenceReason = null;
                
                try {
                    if (Schema::hasTable('table_silences')) {
                        $activeSilence = $table->activeSilence()->first();
                        if ($activeSilence) {
                            $isSilenced = true;
                            $remainingTime = \Carbon\Carbon::parse($activeSilence->silenced_until)
                                ->diffInMinutes(\Carbon\Carbon::now());
                            $silenceReason = $activeSilence->reason;
                        }
                    }
                } catch (\Exception $e) {
                    // Tabla no existe
                }
                
                // Acciones disponibles
                $canActivate = !$table->active_waiter_id;
                $canDeactivate = $isAssignedToMe;
                $canSilence = $isAssignedToMe && !$isSilenced;
                $canUnsilence = $isAssignedToMe && $isSilenced;
                
                return [
                    'id' => $table->id,
                    'number' => $table->number,
                    'name' => $table->name,
                    'capacity' => $table->capacity,
                    'location' => $table->location,
                    'notifications_enabled' => $table->notifications_enabled,
                    'status' => [
                        'assignment' => $isAssignedToMe ? 'assigned_to_me' 
                            : ($table->active_waiter_id ? 'occupied' : 'available'),
                        'assigned_waiter' => $table->activeWaiter ? [
                            'id' => $table->activeWaiter->id,
                            'name' => $table->activeWaiter->name
                        ] : null,
                        'assigned_at' => $table->assigned_at
                    ],
                    'calls' => [
                        'pending_count' => $pendingCount,
                        'latest_call' => $latestCall ? [
                            'id' => $latestCall->id,
                            'called_at' => $latestCall->called_at,
                            'minutes_ago' => $minutesAgo,
                            'urgency' => $latestCall->urgency_level ?? 'normal'
                        ] : null
                    ],
                    'silence' => [
                        'is_silenced' => $isSilenced,
                        'remaining_minutes' => $remainingTime,
                        'reason' => $silenceReason
                    ],
                    'actions_available' => [
                        'can_activate' => $canActivate,
                        'can_deactivate' => $canDeactivate,
                        'can_silence' => $canSilence,
                        'can_unsilence' => $canUnsilence
                    ]
                ];
            });
            
            // Estadísticas
            $stats = [
                'total_tables' => $tables->count(),
                'available' => $tables->whereNull('active_waiter_id')->count(),
                'assigned_to_me' => $tables->where('active_waiter_id', $waiter->id)->count(),
                'occupied_by_others' => $tables->filter(function($t) use ($waiter) {
                    return $t->active_waiter_id && $t->active_waiter_id != $waiter->id;
                })->count(),
                'with_pending_calls' => $tables->filter(fn($t) => $t->pendingCalls->count() > 0)->count(),
                'silenced' => 0
            ];
            
            try {
                if (Schema::hasTable('table_silences')) {
                    $stats['silenced'] = $tables->filter(fn($t) => $t->activeSilence()->exists())->count();
                }
            } catch (\Exception $e) {
                // Ignorar
            }
            
            return response()->json([
                'business_id' => (int)$businessId,
                'tables' => $tablesData->values(),
                'statistics' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en getBusinessTables: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener mesas del negocio',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mozo se une a un negocio mediante código de invitación
     */
    public function joinBusiness(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'business_code' => 'required|string|max:50'
            ]);
            
            $waiter = Auth::user();
            $businessCode = strtoupper(trim($validated['business_code']));
            
            // Buscar negocio por código de invitación
            $business = Business::where('invitation_code', $businessCode)->first();
            
            if (!$business) {
                return response()->json([
                    'error' => 'Código de negocio inválido'
                ], 404);
            }
            
            // Verificar si ya es miembro
            $existingStaff = Staff::where('user_id', $waiter->id)
                ->where('business_id', $business->id)
                ->first();
            
            if ($existingStaff) {
                return response()->json([
                    'error' => 'Ya eres miembro de este negocio',
                    'status' => $existingStaff->status
                ], 409);
            }
            
            // Verificar si el email ya existe en el negocio
            $existingEmail = Staff::where('business_id', $business->id)
                ->where('email', $waiter->email)
                ->first();
            
            if ($existingEmail) {
                return response()->json([
                    'error' => 'Este email ya está registrado en el negocio'
                ], 409);
            }
            
            // Crear solicitud de staff (pendiente de aprobación)
            $staff = Staff::create([
                'user_id' => $waiter->id,
                'business_id' => $business->id,
                'name' => $waiter->name,
                'email' => $waiter->email,
                'position' => 'Mozo',
                'status' => 'pending',
                'hire_date' => null,
                'phone' => $waiter->waiterProfile->phone ?? null
            ]);
            
            // Notificar a los admins del negocio
            try {
                $notificationService = app(StaffNotificationService::class);
                $notificationService->notifyAdminsNewStaffRequest($business->id, $staff);
            } catch (\Exception $e) {
                Log::warning('Error al notificar admins de nueva solicitud de staff: ' . $e->getMessage());
            }
            
            Log::info("Mozo {$waiter->id} solicitó unirse al negocio {$business->id}");
            
            return response()->json([
                'message' => 'Solicitud enviada correctamente. Espera la aprobación del administrador.',
                'staff_request' => [
                    'id' => $staff->id,
                    'business' => [
                        'id' => $business->id,
                        'name' => $business->name
                    ],
                    'status' => 'pending',
                    'created_at' => $staff->created_at
                ]
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en joinBusiness: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al unirse al negocio',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Establece el negocio activo del mozo
     */
    public function setActiveBusiness(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'business_id' => 'required|integer'
            ]);
            
            $waiter = Auth::user();
            $businessId = $validated['business_id'];
            
            // Verificar que el mozo es staff confirmado del negocio
            $staffMembership = Staff::where('user_id', $waiter->id)
                ->where('business_id', $businessId)
                ->where('status', 'confirmed')
                ->first();
            
            if (!$staffMembership) {
                return response()->json([
                    'error' => 'No tienes acceso a este negocio'
                ], 403);
            }
            
            // Actualizar negocio activo
            // Intentar actualizar active_business_id primero
            $updated = false;
            
            try {
                if (Schema::hasColumn('users', 'active_business_id')) {
                    $waiter->active_business_id = $businessId;
                    $updated = true;
                }
            } catch (\Exception $e) {
                Log::debug('Columna active_business_id no existe');
            }
            
            try {
                if (Schema::hasColumn('users', 'business_id')) {
                    $waiter->business_id = $businessId;
                    $updated = true;
                }
            } catch (\Exception $e) {
                Log::debug('Columna business_id no existe');
            }
            
            // Fallback: actualizar business_id directamente
            if (!$updated) {
                $waiter->business_id = $businessId;
            }
            
            $waiter->save();
            
            Log::info("Mozo {$waiter->id} cambió negocio activo a {$businessId}");
            
            return response()->json([
                'message' => 'Negocio activo actualizado correctamente',
                'active_business' => [
                    'id' => $businessId,
                    'name' => $staffMembership->business->name,
                    'code' => $staffMembership->business->code
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en setActiveBusiness: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al establecer negocio activo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Primera configuración de negocio (onboarding)
     * Permite a un mozo unirse a un negocio usando business_id o join_code
     */
    public function onboardBusiness(Request $request): JsonResponse
    {
        $request->validate([
            'business_id' => 'sometimes|exists:businesses,id',
            'join_code' => 'sometimes|string|exists:businesses,join_code',
            'code' => 'sometimes|string|exists:businesses,join_code',
        ]);

        $user = $request->user();

        if (!$user->isWaiter()) {
            return response()->json([
                'message' => 'Solo los usuarios con rol de camarero pueden unirse a un negocio',
            ], 403);
        }

        if ($request->filled('business_id')) {
            $business = Business::findOrFail($request->business_id);
        } else {
            $joinCode = $request->join_code ?? $request->code;
            $business = Business::where('join_code', $joinCode)->first();
            if (!$business) {
                return response()->json([
                    'message' => 'Código de negocio no válido',
                ], 404);
            }
        }

        $user->active_business_id = $business->id;
        $user->save();

        return response()->json([
            'message' => 'Te has unido al negocio correctamente',
            'business' => $business,
        ]);
    }

    /**
     * Obtiene negocios donde el mozo estuvo activo hoy
     * Filtra por mesas asignadas o llamadas atendidas
     */
    public function getActiveTodayBusinesses(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        try {
            $todayStart = now()->startOfDay();
            $staffRecords = Staff::where('user_id', $waiter->id)
                ->where('status', 'confirmed')
                ->with('business')
                ->get();

            $businesses = $staffRecords->map(function ($staffRecord) use ($waiter, $todayStart) {
                $business = $staffRecord->business;
                if (!$business) { return null; }

                $assignedToMe = $business->tables()->where('active_waiter_id', $waiter->id)->count();
                $callsToday = WaiterCall::where('waiter_id', $waiter->id)
                    ->where('called_at', '>=', $todayStart)
                    ->whereHas('table', function ($q) use ($business) {
                        $q->where('business_id', $business->id);
                    })
                    ->count();

                if ($assignedToMe > 0 || $callsToday > 0) {
                    $activeId = (int)($waiter->active_business_id ?? $waiter->business_id);
                    $isActive = $activeId ? ((int)$business->id === $activeId) : ($assignedToMe > 0);
                    return [
                        'id' => $business->id,
                        'name' => $business->name,
                        'code' => $business->invitation_code,
                        'is_active' => $isActive,
                        'assigned_tables' => $assignedToMe,
                        'calls_today' => $callsToday,
                    ];
                }
                return null;
            })->filter()->values();

            return response()->json([
                'success' => true,
                'businesses' => $businesses,
                'count' => $businesses->count(),
                'date' => now()->toDateString(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error getting active today businesses', [
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo negocios activos hoy'
            ], 500);
        }
    }

    /**
     * Desvincularse de un negocio
     * Elimina Staff, cancela llamadas y desasigna mesas del mozo
     */
    public function leaveBusiness(Request $request): JsonResponse
    {
        $request->validate([
            'business_id' => 'required|integer'
        ]);

        $waiter = Auth::user();
        $businessId = (int) $request->business_id;

        try {
            $staff = Staff::where('user_id', $waiter->id)
                ->where('business_id', $businessId)
                ->first();

            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'No estás asociado a este negocio'
                ], 404);
            }

            // Desasignar mesas del mozo en ese negocio y cancelar llamadas pendientes
            $tables = Table::where('business_id', $businessId)
                ->where('active_waiter_id', $waiter->id)
                ->get();

            foreach ($tables as $table) {
                // Cancelar llamadas pendientes
                try {
                    $table->pendingCalls()->update(['status' => 'cancelled']);
                } catch (\Throwable $e) { /* noop */ }

                // Desasignar
                try {
                    if (method_exists($table, 'unassignWaiter')) {
                        $table->unassignWaiter();
                    } else {
                        $table->active_waiter_id = null;
                        $table->waiter_assigned_at = null;
                        $table->save();
                    }
                } catch (\Throwable $e) { /* noop */ }
            }

            // Cancelar llamadas pendientes del mozo en ese negocio
            WaiterCall::where('waiter_id', $waiter->id)
                ->where('status', 'pending')
                ->whereHas('table', function ($q) use ($businessId) {
                    $q->where('business_id', $businessId);
                })
                ->update(['status' => 'cancelled']);

            // Eliminar registro de staff
            $staff->delete();

            // Si era su negocio activo, elegir otro o limpiar
            if ((int) $waiter->business_id === $businessId) {
                $next = Staff::where('user_id', $waiter->id)
                    ->where('status', 'confirmed')
                    ->first();
                $waiter->business_id = $next ? $next->business_id : null;
                $waiter->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Te desvinculaste del negocio correctamente',
                'active_business_id' => $waiter->business_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error leaving business', [
                'waiter_id' => $waiter->id,
                'business_id' => $businessId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al desvincularse del negocio'
            ], 500);
        }
    }

    /**
     * Helper privado: Asegura que el mozo tenga business_id
     * Auto-asigna desde Staff si está vacío y crea user_active_role para persistencia
     */
    private function ensureBusinessId($waiter, bool $allowStaffCreation = true)
    {
        $businessId = $waiter->business_id;
        
        if (!$businessId) {
            $staffRecord = Staff::where('user_id', $waiter->id)
                ->where('status', 'confirmed')
                ->with('business')
                ->first();
            
            if ($staffRecord && $allowStaffCreation) {
                $businessId = $staffRecord->business_id;
                
                // Persistir negocio activo usando la columna disponible
                if (Schema::hasColumn('users', 'active_business_id')) {
                    $waiter->update(['active_business_id' => $businessId]);
                } elseif (Schema::hasColumn('users', 'business_id')) {
                    $waiter->update(['business_id' => $businessId]);
                } else {
                    Log::warning('No columns to store active business on users table');
                }
                $waiter->refresh();
                
                Log::info('Auto-fixed missing business_id', [
                    'waiter_id' => $waiter->id,
                    'assigned_business_id' => $businessId,
                    'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown'
                ]);
            }
        }
        
        // 🔧 FIX: Asegurar que exista el registro en user_active_roles para persistencia
        if ($businessId) {
            try {
                $existingRole = $waiter->activeRoles()
                    ->where('business_id', $businessId)
                    ->where('active_role', 'waiter')
                    ->first();
                
                if (!$existingRole) {
                    // Crear el registro para persistir la sesión
                    $waiter->activeRoles()->updateOrCreate(
                        [
                            'business_id' => $businessId,
                        ],
                        [
                            'active_role' => 'waiter',
                            'switched_at' => now()
                        ]
                    );
                    
                    Log::info('Auto-created user_active_role for waiter persistent session', [
                        'user_id' => $waiter->id,
                        'business_id' => $businessId,
                        'role' => 'waiter'
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to create user_active_role for waiter', [
                    'user_id' => $waiter->id,
                    'business_id' => $businessId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $businessId;
    }
}

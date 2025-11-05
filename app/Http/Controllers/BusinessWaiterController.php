<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Table;
use App\Models\Staff;
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
}

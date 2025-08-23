<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Table;
use App\Models\User;
use App\Models\WaiterCall;
use App\Models\TableSilence;
use App\Models\IpBlock;
use App\Models\Staff;
use App\Notifications\TableCalledNotification;
use App\Services\FirebaseService;
use App\Services\UnifiedFirebaseService;
use App\Services\WaiterNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class WaiterController extends Controller
{
    private $firebaseService;
    private $unifiedFirebaseService;

    public function __construct(FirebaseService $firebaseService, UnifiedFirebaseService $unifiedFirebaseService)
    {
        $this->firebaseService = $firebaseService;
        $this->unifiedFirebaseService = $unifiedFirebaseService;
    }

    public function onboardBusiness(Request $request)
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

    public function listTables(Request $request)
    {
        $user = $request->user();

        if (!$user->business_id) {
            return response()->json([
                'message' => 'No estás vinculado a ningún negocio',
                'tables' => [],
            ]);
        }

        $tables = Table::where('business_id', $user->business_id)->get();

        return response()->json([
            'tables' => $tables,
        ]);
    }

    public function toggleTableNotifications($tableId)
    {
        $user = Auth::user();
        
        $table = Table::where('id', $tableId)
            ->where('business_id', $user->business_id)
            ->firstOrFail();
            
        $table->notifications_enabled = !$table->notifications_enabled;
        $table->save();
        
        return response()->json([
            'message' => 'Estado de notificaciones actualizado',
            'table' => $table,
            'notifications_enabled' => $table->notifications_enabled
        ]);
    }

    public function globalNotifications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        
        Table::where('business_id', $user->business_id)
            ->update(['notifications_enabled' => $request->enabled]);
            
        return response()->json([
            'message' => $request->enabled 
                ? 'Notificaciones habilitadas para todas las mesas' 
                : 'Notificaciones deshabilitadas para todas las mesas',
            'notifications_enabled' => $request->enabled
        ]);
    }

    public function listNotifications(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->paginate(10);

        return response()->json([
            'notifications' => $notifications,
        ]);
    }

    public function respondNotification(Request $request)
    {
        $request->validate([
            'notification_id' => 'required|string',
            'action' => 'required|in:accept,deny,spam',
        ]);

        $user = $request->user();
        $notification = $user->notifications()->where('id', $request->notification_id)->first();

        if (!$notification) {
            return response()->json([
                'message' => 'Notificación no encontrada',
            ], 404);
        }

        $notification->markAsRead();

        if ($request->action === 'spam') {
        }

        return response()->json([
            'message' => 'Notificación procesada exitosamente',
        ]);
    }

    
    public function fetchWaiterTables()
    {
        $user = Auth::user();
        
        $tables = Table::where('business_id', $user->business_id)
            ->orderBy('number', 'asc')
            ->get();
            
        // Sistema legacy de assignments por profiles eliminado: is_assigned siempre false
        $tables->transform(function ($table) {
            $table->is_assigned = false;
            return $table;
        });

        return response()->json([
            'tables' => $tables,
            'assigned_count' => 0
        ]);
    }
    
    public function fetchWaiterNotifications()
    {
        $user = Auth::user();
        
        $unreadNotifications = $user->unreadNotifications;
        
        $readNotifications = $user->readNotifications()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
            
        return response()->json([
            'unread_notifications' => $unreadNotifications,
            'read_notifications' => $readNotifications,
            'unread_count' => $unreadNotifications->count()
        ]);
    }
    
    public function handleNotification(Request $request, $notificationId)
    {
        // 🚀 MEJORADO: Action opcional, por defecto 'mark_as_read'
        $validator = Validator::make($request->all(), [
            'action' => 'sometimes|string|in:mark_as_read,delete,read',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        
        $notification = $user->notifications()->where('id', $notificationId)->first();
        
        if (!$notification) {
            return response()->json([
                'message' => 'Notificación no encontrada'
            ], 404);
        }
        
        // Por defecto, marcar como leída si no se especifica action
        $action = $request->get('action', 'mark_as_read');
        
        // Normalizar 'read' a 'mark_as_read' para compatibilidad
        if ($action === 'read') {
            $action = 'mark_as_read';
        }
        
        if ($action === 'mark_as_read') {
            $notification->markAsRead();
            
            \Log::info('Notification marked as read', [
                'notification_id' => $notificationId,
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída',
                'notification' => $notification
            ]);
        } else if ($action === 'delete') {
            $notification->delete();
            
            \Log::info('Notification deleted', [
                'notification_id' => $notificationId,
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Notificación eliminada'
            ]);
        }
        
        // Fallback (no debería llegar aquí con la validación)
        return response()->json([
            'success' => false,
            'message' => 'Acción no válida'
        ], 400);
    }

    /**
     * Marcar notificación como leída (endpoint simple)
     */
    public function markNotificationAsRead(Request $request, $notificationId)
    {
        $user = Auth::user();
        
        $notification = $user->notifications()->where('id', $notificationId)->first();
        
        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada'
            ], 404);
        }
        
        $notification->markAsRead();
        
        \Log::info('Notification marked as read (simple endpoint)', [
            'notification_id' => $notificationId,
            'user_id' => $user->id,
            'user_name' => $user->name
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída',
            'notification' => [
                'id' => $notification->id,
                'read_at' => $notification->read_at,
                'data' => $notification->data
            ]
        ]);
    }

    /**
     * Marcar múltiples notificaciones como leídas
     */
    public function markMultipleNotificationsAsRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'string|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        $notificationIds = $request->notification_ids;
        
        $notifications = $user->notifications()
            ->whereIn('id', $notificationIds)
            ->whereNull('read_at')
            ->get();
        
        $markedCount = 0;
        foreach ($notifications as $notification) {
            $notification->markAsRead();
            $markedCount++;
        }
        
        \Log::info('Multiple notifications marked as read', [
            'notification_ids' => $notificationIds,
            'marked_count' => $markedCount,
            'user_id' => $user->id,
            'user_name' => $user->name
        ]);
        
        return response()->json([
            'success' => true,
            'message' => "Se marcaron {$markedCount} notificaciones como leídas",
            'marked_count' => $markedCount,
            'total_requested' => count($notificationIds)
        ]);
    }

    // ===== BUSINESS MANAGEMENT METHODS =====
    
    /**
     * Obtener todos los negocios donde el mozo puede trabajar
     */
    public function getWaiterBusinesses(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            // Obtener negocios donde este usuario es staff (waiter)
            $staffRecords = Staff::where('user_id', $waiter->id)
                ->where('status', 'confirmed')
                ->with('business')
                ->get();

            $businesses = $staffRecords->map(function ($staffRecord) use ($waiter) {
                $business = $staffRecord->business;
                    // Estadísticas básicas sin consultas complejas
                    $totalTables = $business->tables()->count();
                    $assignedToMe = $business->tables()->where('active_waiter_id', $waiter->id)->count();
                    $available = $business->tables()->whereNull('active_waiter_id')->count();

                    // Llamadas pendientes de este mozo en este negocio
                    $pendingCalls = WaiterCall::where('waiter_id', $waiter->id)
                        ->where('status', 'pending')
                        ->whereHas('table', function ($query) use ($business) {
                            $query->where('business_id', $business->id);
                        })
                        ->count();

                    return [
                        'id' => $business->id,
                        'name' => $business->name,
                        'code' => $business->invitation_code,
                        'address' => $business->address,
                        'phone' => $business->phone,
                        'logo' => $business->logo ? asset('storage/' . $business->logo) : null,
                        'is_active' => $business->id === $waiter->business_id,
                        'membership' => [
                            'joined_at' => null,
                            'status' => 'active',
                            'role' => 'waiter'
                        ],
                        'tables' => [
                            'total' => $totalTables,
                            'assigned_to_me' => $assignedToMe,
                            'available' => $available
                        ],
                        'pending_calls' => $pendingCalls
                    ];
                });

            return response()->json([
                'success' => true,
                'businesses' => $businesses,
                'count' => $businesses->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting waiter businesses', [
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo los negocios'
            ], 500);
        }
    }

    /**
     * Cambiar negocio activo
     */
    public function setActiveBusiness(Request $request): JsonResponse
    {
        $request->validate([
            'business_id' => 'required|integer'
        ]);

        $waiter = Auth::user();
        $businessId = $request->business_id;

        try {
            // Verificar que el mozo tenga acceso a este negocio (debe estar registrado como staff)
            $staffRecord = Staff::where('user_id', $waiter->id)
                ->where('business_id', $businessId)
                ->where('status', 'confirmed')
                ->with('business')
                ->first();
            
            if (!$staffRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este negocio'
                ], 403);
            }

            $business = $staffRecord->business;

            // Actualizar negocio activo
            $waiter->update(['business_id' => $businessId]);

            return response()->json([
                'success' => true,
                'message' => "Cambiado a {$business->name}",
                'active_business' => [
                    'id' => $business->id,
                    'name' => $business->name,
                    'code' => $business->invitation_code
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error setting active business', [
                'waiter_id' => $waiter->id,
                'business_id' => $businessId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error cambiando el negocio activo'
            ], 500);
        }
    }

    /**
     * Unirse a un negocio usando código de invitación
     */
    public function joinBusiness(Request $request)
    {
        $request->validate([
            'business_code' => 'required|string'
        ]);

        $waiter = Auth::user();
        $businessCode = strtoupper(trim($request->business_code));

        try {
            // Buscar el negocio por código
            $business = Business::where('invitation_code', $businessCode)
                ->first();

            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Código de negocio no válido'
                ], 404);
            }

            // Verificar si ya está registrado en este negocio como waiter
            $existingStaff = Staff::where('user_id', $waiter->id)
                ->where('business_id', $business->id)
                ->first();
            
            if ($existingStaff) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya estás registrado en este negocio',
                    'business' => [
                        'id' => $business->id,
                        'name' => $business->name,
                        'code' => $business->invitation_code
                    ]
                ], 409);
            }

            // Verificar si ya existe por email (constraint unique)
            $existingByEmail = Staff::where('email', $waiter->email)
                ->where('business_id', $business->id)
                ->first();
                
            if ($existingByEmail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un empleado con este email en el negocio'
                ], 409);
            }

            // Registrar al mozo en el negocio a través de la tabla staff
            $staffRecord = Staff::create([
                'user_id' => $waiter->id,
                'business_id' => $business->id,
                'name' => $waiter->name,
                'email' => $waiter->email,
                'position' => 'waiter',
                'status' => 'confirmed',
                'hire_date' => now(),
                'phone' => optional($waiter->waiterProfile)->phone,
            ]);

            // Si es su primer negocio, hacerlo activo
            if (!$waiter->business_id) {
                $waiter->update(['business_id' => $business->id]);
            }

            Log::info('Waiter joined new business', [
                'waiter_id' => $waiter->id,
                'waiter_name' => $waiter->name,
                'business_id' => $business->id,
                'business_name' => $business->name,
                'invitation_code' => $businessCode
            ]);

            return response()->json([
                'success' => true,
                'message' => "Te has unido exitosamente a {$business->name}",
                'business' => [
                    'id' => $business->id,
                    'name' => $business->name,
                    'code' => $business->invitation_code,
                    'address' => $business->address,
                    'phone' => $business->phone,
                    'logo' => $business->logo ? asset('storage/' . $business->logo) : null,
                    'is_active' => $business->id === $waiter->business_id
                ],
                'membership' => [
                    'joined_at' => now(),
                    'status' => 'active',
                    'role' => 'waiter'
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error joining business', [
                'waiter_id' => $waiter->id,
                'business_code' => $businessCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al unirse al negocio',
                'debug' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    // ===== TABLE MANAGEMENT METHODS =====

    /**
     * Obtener mesas disponibles de un negocio específico
     */
    public function getBusinessTables(Request $request, $businessId): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            // Verificar que el mozo tenga acceso a este negocio (debe estar registrado como staff)
            $staffRecord = Staff::where('user_id', $waiter->id)
                ->where('business_id', $businessId)
                ->where('status', 'confirmed')
                ->with('business')
                ->first();
            
            if (!$staffRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este negocio'
                ], 403);
            }

            $business = $staffRecord->business;

            // Obtener todas las mesas del negocio con información básica
            $tables = Table::where('business_id', $businessId)
                ->with(['activeWaiter'])
                ->orderBy('number', 'asc')
                ->get()
                ->map(function ($table) use ($waiter) {
                    $isAssignedToMe = $table->active_waiter_id === $waiter->id;
                    $pendingCallsCount = $table->waiterCalls()->where('status', 'pending')->count();
                    $latestCall = $table->waiterCalls()->where('status', 'pending')->latest()->first();
                    // Verificar si la mesa está silenciada
                    $activeSilence = null;
                    try {
                        if (Schema::hasTable('table_silences')) {
                            $activeSilence = $table->silences()->active()->first();
                        }
                    } catch (\Exception $e) {
                        // Tabla no existe, continuar sin silencio
                    }

                    return [
                        'id' => $table->id,
                        'number' => $table->number,
                        'name' => $table->name,
                        'capacity' => $table->capacity,
                        'location' => $table->location,
                        'notifications_enabled' => $table->notifications_enabled,
                        'status' => [
                            'assignment' => $table->active_waiter_id ? 
                                ($isAssignedToMe ? 'assigned_to_me' : 'occupied') : 'available',
                            'assigned_waiter' => $table->activeWaiter ? [
                                'id' => $table->activeWaiter->id,
                                'name' => $table->activeWaiter->name,
                                'is_me' => $isAssignedToMe
                            ] : null,
                            'assigned_at' => $table->waiter_assigned_at
                        ],
                        'calls' => [
                            'pending_count' => $pendingCallsCount,
                            'latest_call' => $latestCall ? [
                                'id' => $latestCall->id,
                                'called_at' => $latestCall->called_at,
                                'minutes_ago' => $latestCall->called_at->diffInMinutes(now()),
                                'message' => $latestCall->message
                            ] : null
                        ],
                        'silence' => [
                            'is_silenced' => $activeSilence ? true : false,
                            'remaining_time' => $activeSilence ? 
                                ($activeSilence->formatted_remaining_time ?? null) : null,
                            'reason' => $activeSilence ? $activeSilence->reason : null
                        ],
                        'actions_available' => [
                            'can_activate' => !$table->active_waiter_id,
                            'can_deactivate' => $isAssignedToMe,
                            'can_silence' => $isAssignedToMe && !$activeSilence,
                            'can_unsilence' => $isAssignedToMe && $activeSilence
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'business' => [
                    'id' => $business->id,
                    'name' => $business->name,
                    'address' => $business->address
                ],
                'tables' => $tables,
                'summary' => [
                    'total' => $tables->count(),
                    'available' => $tables->where('status.assignment', 'available')->count(),
                    'assigned_to_me' => $tables->where('status.assignment', 'assigned_to_me')->count(),
                    'occupied' => $tables->where('status.assignment', 'occupied')->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting business tables', [
                'business_id' => $businessId,
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo las mesas del negocio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener mesas asignadas al mozo
     */
    public function getAssignedTables(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            if (!$waiter->business_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un negocio activo seleccionado'
                ], 400);
            }

            $tables = Table::where('business_id', $waiter->business_id)
                ->where('active_waiter_id', $waiter->id)
                ->with(['waiterCalls' => function($query) {
                    $query->where('status', 'pending')->latest();
                }])
                ->get()
                ->map(function ($table) {
                    $pendingCalls = $table->waiterCalls->where('status', 'pending');
                    $latestCall = $pendingCalls->first();
                    
                    return [
                        'id' => $table->id,
                        'number' => $table->number,
                        'name' => $table->name,
                        'capacity' => $table->capacity,
                        'location' => $table->location,
                        'assigned_at' => $table->waiter_assigned_at,
                        'pending_calls' => $pendingCalls->count(),
                        'latest_call' => $latestCall ? [
                            'id' => $latestCall->id,
                            'message' => $latestCall->message,
                            'called_at' => $latestCall->called_at,
                            'minutes_ago' => $latestCall->called_at->diffInMinutes(now())
                        ] : null,
                        'notifications_enabled' => $table->notifications_enabled
                    ];
                });

            return response()->json([
                'success' => true,
                'assigned_tables' => $tables,
                'count' => $tables->count(),
                'available_to_assign' => Table::where('business_id', $waiter->business_id)
                    ->whereNull('active_waiter_id')
                    ->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting assigned tables', [
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo las mesas asignadas'
            ], 500);
        }
    }

    /**
     * Obtener mesas disponibles para asignar
     */
    public function getAvailableTables(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            if (!$waiter->business_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un negocio activo seleccionado'
                ], 400);
            }

            $availableTables = Table::where('business_id', $waiter->business_id)
                ->whereNull('active_waiter_id')
                ->orderBy('number')
                ->get(['id', 'number', 'name', 'capacity', 'location']);

            return response()->json([
                'success' => true,
                'available_tables' => $availableTables,
                'count' => $availableTables->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo las mesas disponibles'
            ], 500);
        }
    }

    /**
     * Activar mesa (asignar al mozo actual)
     */
    public function activateTable(Request $request, Table $table): JsonResponse
    {
        $waiter = Auth::user();

        try {
            if ($table->business_id !== $waiter->business_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa no pertenece a tu negocio activo'
                ], 403);
            }

            if ($table->active_waiter_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa ya está asignada a otro mozo'
                ], 422);
            }

            $table->update([
                'active_waiter_id' => $waiter->id,
                'waiter_assigned_at' => now()
            ]);

            Log::info('Table activated', [
                'table_id' => $table->id,
                'table_number' => $table->number,
                'waiter_id' => $waiter->id,
                'waiter_name' => $waiter->name,
                'business_id' => $table->business_id
            ]);

            return response()->json([
                'success' => true,
                'message' => "Mesa {$table->number} activada exitosamente",
                'table' => [
                    'id' => $table->id,
                    'number' => $table->number,
                    'name' => $table->name,
                    'assigned_at' => $table->waiter_assigned_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error activating table', [
                'table_id' => $table->id,
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error activando la mesa'
            ], 500);
        }
    }

    /**
     * Desactivar mesa (quitar asignación del mozo actual)
     */
    public function deactivateTable(Request $request, Table $table): JsonResponse
    {
        $waiter = Auth::user();

        try {
            if ($table->business_id !== $waiter->business_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa no pertenece a tu negocio activo'
                ], 403);
            }

            if ($table->active_waiter_id !== $waiter->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes desactivar una mesa que no tienes asignada'
                ], 422);
            }

            $table->update([
                'active_waiter_id' => null,
                'waiter_assigned_at' => null
            ]);

            Log::info('Table deactivated', [
                'table_id' => $table->id,
                'table_number' => $table->number,
                'waiter_id' => $waiter->id,
                'waiter_name' => $waiter->name,
                'business_id' => $table->business_id
            ]);

            return response()->json([
                'success' => true,
                'message' => "Mesa {$table->number} desactivada exitosamente"
            ]);

        } catch (\Exception $e) {
            Log::error('Error deactivating table', [
                'table_id' => $table->id,
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error desactivando la mesa'
            ], 500);
        }
    }

    // ===== CALL MANAGEMENT METHODS (Real-time) =====

    /**
     * Obtener llamadas pendientes
     */
    public function getPendingCalls(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            if (!$waiter->business_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un negocio activo seleccionado'
                ], 400);
            }

            $calls = WaiterCall::where('waiter_id', $waiter->id)
                ->where('status', 'pending')
                ->whereHas('table', function ($q) use ($waiter) {
                    $q->where('business_id', $waiter->business_id);
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
     * Obtener llamadas recientes
     */
    public function getRecentCalls(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            if (!$waiter->business_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un negocio activo seleccionado'
                ], 400);
            }

            $calls = WaiterCall::where('waiter_id', $waiter->id)
                ->whereHas('table', function ($q) use ($waiter) {
                    $q->where('business_id', $waiter->business_id);
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
     * Reconocer llamada
     */
    public function acknowledgeCall(Request $request, $callId): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            $call = WaiterCall::where('id', $callId)
                ->where('waiter_id', $waiter->id)
                ->where('status', 'pending')
                ->first();

            if (!$call) {
                return response()->json([
                    'success' => false,
                    'message' => 'Llamada no encontrada o ya procesada'
                ], 404);
            }

            $call->update([
                'status' => 'acknowledged',
                'acknowledged_at' => now()
            ]);

            // Notificar en Firebase que la llamada fue reconocida
            try {
                $this->unifiedFirebaseService->writeCallStatus($call->table_id, [
                    'status' => 'acknowledged',
                    'acknowledged_at' => now()->timestamp * 1000,
                    'acknowledged_by' => $waiter->name
                ]);
            } catch (\Exception $e) {
                Log::warning('Firebase notification failed for acknowledge', [
                    'call_id' => $call->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Llamada reconocida exitosamente',
                'call' => [
                    'id' => $call->id,
                    'status' => $call->status,
                    'acknowledged_at' => $call->acknowledged_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error acknowledging call', [
                'call_id' => $callId,
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error reconociendo la llamada'
            ], 500);
        }
    }

    /**
     * Completar llamada
     */
    public function completeCall(Request $request, $callId): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            $call = WaiterCall::where('id', $callId)
                ->where('waiter_id', $waiter->id)
                ->whereIn('status', ['pending', 'acknowledged'])
                ->first();

            if (!$call) {
                return response()->json([
                    'success' => false,
                    'message' => 'Llamada no encontrada o ya completada'
                ], 404);
            }

            $call->update([
                'status' => 'completed',
                'completed_at' => now(),
                'acknowledged_at' => $call->acknowledged_at ?: now()
            ]);

            // Notificar en Firebase que la llamada fue completada
            try {
                $this->unifiedFirebaseService->writeCallStatus($call->table_id, [
                    'status' => 'completed',
                    'completed_at' => now()->timestamp * 1000,
                    'completed_by' => $waiter->name
                ]);
            } catch (\Exception $e) {
                Log::warning('Firebase notification failed for complete', [
                    'call_id' => $call->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Llamada completada exitosamente',
                'call' => [
                    'id' => $call->id,
                    'status' => $call->status,
                    'completed_at' => $call->completed_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error completing call', [
                'call_id' => $callId,
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error completando la llamada'
            ], 500);
        }
    }

    /**
     * Crear nueva llamada (desde QR)
     */
    public function createCall(Request $request, Table $table): JsonResponse
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
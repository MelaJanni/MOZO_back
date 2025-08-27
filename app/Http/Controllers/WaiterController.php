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

    /**
     * Auto-corregir business_id faltante
     */
    private function ensureBusinessId($waiter)
    {
        if (!$waiter->business_id) {
            $staffRecord = Staff::where('user_id', $waiter->id)
                ->where('status', 'confirmed')
                ->with('business')
                ->first();
            
            if ($staffRecord) {
                $waiter->update(['business_id' => $staffRecord->business_id]);
                $waiter->refresh();
                
                Log::info('Auto-fixed missing business_id', [
                    'waiter_id' => $waiter->id,
                    'assigned_business_id' => $staffRecord->business_id,
                    'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown'
                ]);
                
                return $staffRecord->business_id;
            }
        }
        
        return $waiter->business_id;
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
    // AUTO-CORRECCIÓN: Asegurar business_id
    $businessId = $this->ensureBusinessId($user);

    if (!$businessId) {
            return response()->json([
                'message' => 'No estás vinculado a ningún negocio',
                'tables' => [],
            ]);
        }

    $tables = Table::where('business_id', $businessId)->get();

        return response()->json([
            'tables' => $tables,
        ]);
    }

    public function toggleTableNotifications($tableId)
    {
        $user = Auth::user();
        // AUTO-CORRECCIÓN: Asegurar business_id
        $businessId = $this->ensureBusinessId($user);
        if (!$businessId) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un negocio activo seleccionado. Debes unirte a un negocio primero.'
            ], 400);
        }

        $table = Table::where('id', $tableId)
            ->where('business_id', $businessId)
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
        // AUTO-CORRECCIÓN: Asegurar business_id
        $businessId = $this->ensureBusinessId($user);
        if (!$businessId) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un negocio activo seleccionado. Debes unirte a un negocio primero.'
            ], 400);
        }

        Table::where('business_id', $businessId)
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
        // AUTO-CORRECCIÓN: Asegurar business_id
        $businessId = $this->ensureBusinessId($user);
        if (!$businessId) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un negocio activo seleccionado. Debes unirte a un negocio primero.',
                'tables' => [],
                'assigned_count' => 0
            ], 400);
        }

        $tables = Table::where('business_id', $businessId)
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
            try {
                $notification = $user->notifications()
                    ->where('data->data->key', $notificationId)
                    ->orWhere('data->key', $notificationId)
                    ->orWhere('data->data->notification_key', $notificationId)
                    ->orWhere('data->notification_key', $notificationId)
                    ->latest()
                    ->first();
            } catch (\Throwable $e) {
                $candidates = $user->notifications()->latest()->limit(100)->get();
                $notification = $candidates->first(function ($n) use ($notificationId) {
                    $d = (array)($n->data ?? []);
                    $inner = (array)($d['data'] ?? []);
                    return (($inner['key'] ?? null) === $notificationId)
                        || (($d['key'] ?? null) === $notificationId)
                        || (($inner['notification_key'] ?? null) === $notificationId)
                        || (($d['notification_key'] ?? null) === $notificationId);
                });
            }
        }

        // Compat: staff_req_{id}_{ts} -> user_staff_{id}
        if (!$notification && preg_match('/^staff_req_(\d+)_\d+$/', (string)$notificationId, $m)) {
            $derived = 'user_staff_' . $m[1];
            try {
                $notification = $user->notifications()
                    ->where('data->data->key', $derived)
                    ->orWhere('data->key', $derived)
                    ->orWhere('data->data->notification_key', $derived)
                    ->orWhere('data->notification_key', $derived)
                    ->latest()
                    ->first();
            } catch (\Throwable $e) {
                $candidates = $user->notifications()->latest()->limit(100)->get();
                $notification = $candidates->first(function ($n) use ($derived) {
                    $d = (array)($n->data ?? []);
                    $inner = (array)($d['data'] ?? []);
                    return (($inner['key'] ?? null) === $derived)
                        || (($d['key'] ?? null) === $derived)
                        || (($inner['notification_key'] ?? null) === $derived)
                        || (($d['notification_key'] ?? null) === $derived);
                });
            }
        }
        
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

            // AUTO-CORRECCIÓN: Si no tiene business_id pero tiene registros staff, asignar el primero
            if (!$waiter->business_id && $staffRecords->isNotEmpty()) {
                $firstBusiness = $staffRecords->first()->business;
                $waiter->update(['business_id' => $firstBusiness->id]);
                $waiter->refresh(); // Recargar el modelo
                
                Log::info('Auto-fixed missing business_id in getWaiterBusinesses', [
                    'waiter_id' => $waiter->id,
                    'assigned_business_id' => $firstBusiness->id,
                    'business_name' => $firstBusiness->name
                ]);
            }

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
     * Negocios "activos hoy" del mozo
     * Criterio: tiene mesas asignadas actualmente en ese negocio o tuvo llamadas hoy en ese negocio.
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
                    return [
                        'id' => $business->id,
                        'name' => $business->name,
                        'code' => $business->invitation_code,
                        'is_active' => $business->id === $waiter->business_id,
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
            \Log::error('Error getting active today businesses', [
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
     * Desvincularse de un negocio (el mozo se quita de Staff y se desasigna de mesas)
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
            $tables = \App\Models\Table::where('business_id', $businessId)
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
            \Log::error('Error leaving business', [
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

            // Verificar si ya existe un registro en staff
            $existingStaff = Staff::where('user_id', $waiter->id)
                ->where('business_id', $business->id)
                ->first();

            if ($existingStaff) {
                if ($existingStaff->status === 'confirmed') {
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
                if ($existingStaff->status === 'pending') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ya tienes una solicitud pendiente en este negocio',
                        'staff_request' => [
                            'id' => $existingStaff->id,
                            'status' => $existingStaff->status,
                        ],
                        'business' => [
                            'id' => $business->id,
                            'name' => $business->name,
                            'code' => $business->invitation_code
                        ]
                    ], 409);
                }
                // Si fue rechazada, permitir reenviar como pendiente
                if ($existingStaff->status === 'rejected') {
                    $existingStaff->update([
                        'status' => 'pending',
                        'hire_date' => null,
                    ]);
                    try {
                        // Escribir en Firebase RTDB para notificar a admins (sin gate bound)
                        app(\App\Services\StaffNotificationService::class)
                            ->writeStaffRequest($existingStaff, 'created');
                    } catch (\Throwable $e) { /* noop */ }

                    return response()->json([
                        'success' => true,
                        'message' => 'Solicitud reenviada al administrador',
                        'staff_request' => [
                            'id' => $existingStaff->id,
                            'status' => $existingStaff->status,
                        ],
                        'business' => [
                            'id' => $business->id,
                            'name' => $business->name,
                            'code' => $business->invitation_code
                        ]
                    ], 201);
                }
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

            // Crear solicitud de staff en estado pendiente (no unir automáticamente)
            $staffRecord = Staff::create([
                'user_id' => $waiter->id,
                'business_id' => $business->id,
                'name' => $waiter->name,
                'email' => $waiter->email,
                'position' => 'Mozo',
                'status' => 'pending',
                'hire_date' => null,
                'phone' => optional($waiter->waiterProfile)->phone,
            ]);

            // Notificar a admins del negocio
            try {
                // Escribir en Firebase RTDB para notificar a admins (sin gate bound)
                app(\App\Services\StaffNotificationService::class)
                    ->writeStaffRequest($staffRecord, 'created');
            } catch (\Throwable $e) {
                \Log::warning('Failed to send staff request notification', [
                    'staff_id' => $staffRecord->id,
                    'error' => $e->getMessage(),
                ]);
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
                'message' => 'Solicitud enviada al administrador. Te notificaremos cuando sea aprobada.',
                'business' => [
                    'id' => $business->id,
                    'name' => $business->name,
                    'code' => $business->invitation_code,
                    'address' => $business->address,
                    'phone' => $business->phone,
                    'logo' => $business->logo ? asset('storage/' . $business->logo) : null,
                    'is_active' => false
                ],
                'staff_request' => [
                    'id' => $staffRecord->id,
                    'status' => $staffRecord->status,
                    'created_at' => now()->toIso8601String()
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
            // AUTO-CORRECCIÓN: Asegurar business_id
            $businessId = $this->ensureBusinessId($waiter);

            if (!$businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un negocio activo seleccionado'
                ], 400);
            }

            $tables = Table::where('business_id', $businessId)
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
                'available_to_assign' => Table::where('business_id', $businessId)
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
            // AUTO-CORRECCIÓN: Asegurar business_id
            $businessId = $this->ensureBusinessId($waiter);

            if (!$businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un negocio activo seleccionado'
                ], 400);
            }

            $availableTables = Table::where('business_id', $businessId)
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
        // AUTO-CORRECCIÓN: Asegurar business_id
        $businessId = $this->ensureBusinessId($waiter);

        try {
            if (!$businessId || $table->business_id !== $businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa no pertenece a tu negocio activo'
                ], 403);
            }

            // Verificar que el usuario sea staff confirmado del negocio
            $isConfirmedStaff = Staff::where('user_id', $waiter->id)
                ->where('business_id', $businessId)
                ->where('status', 'confirmed')
                ->exists();
            if (!$isConfirmedStaff) {
                return response()->json([
                    'success' => false,
                    'message' => 'No estás habilitado para administrar mesas en este negocio'
                ], 403);
            }

            if ($table->active_waiter_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa ya está asignada a otro mozo'
                ], 422);
            }
            $updateData = [
                'active_waiter_id' => $waiter->id,
            ];
            if (Schema::hasColumn('tables', 'waiter_assigned_at')) {
                $updateData['waiter_assigned_at'] = now();
            }
            $table->update($updateData);

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
        // AUTO-CORRECCIÓN: Asegurar business_id
        $businessId = $this->ensureBusinessId($waiter);

        try {
            if (!$businessId || $table->business_id !== $businessId) {
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
            $updateData = [
                'active_waiter_id' => null,
            ];
            if (Schema::hasColumn('tables', 'waiter_assigned_at')) {
                $updateData['waiter_assigned_at'] = null;
            }
            $table->update($updateData);

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
            // AUTO-CORRECCIÓN: Asegurar business_id
            $businessId = $this->ensureBusinessId($waiter);

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
     * Obtener llamadas recientes
     */
    public function getRecentCalls(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            // AUTO-CORRECCIÓN: Asegurar business_id
            $businessId = $this->ensureBusinessId($waiter);

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
     * Reconocer llamada
     */
    public function acknowledgeCall(Request $request, $callId): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            $call = WaiterCall::with('table')->find($callId);

            if (!$call) {
                return response()->json([
                    'success' => false,
                    'message' => 'Llamada no encontrada'
                ], 404);
            }

            $isAssignedWaiter = ((int)$call->waiter_id === (int)$waiter->id);
            $isCurrentTableWaiter = ((int)($call->table->active_waiter_id ?? 0) === (int)$waiter->id);
            if (!($isAssignedWaiter || $isCurrentTableWaiter)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para reconocer esta llamada'
                ], 403);
            }

            if ($call->status !== 'pending') {
                // Idempotencia y resincronización
                try {
                    $call->loadMissing(['table','waiter']);
                    if ($call->status === 'acknowledged') {
                        $this->unifiedFirebaseService->writeCall($call, 'acknowledged');
                        return response()->json([
                            'success' => true,
                            'message' => 'Llamada ya estaba reconocida (resincronizada)',
                            'call' => [
                                'id' => $call->id,
                                'status' => $call->status,
                                'acknowledged_at' => $call->acknowledged_at
                            ]
                        ]);
                    }
                    if ($call->status === 'completed') {
                        $this->unifiedFirebaseService->removeCall($call);
                    }
                } catch (\Throwable $t) {}
                return response()->json([
                    'success' => false,
                    'message' => 'Llamada ya procesada',
                    'status' => $call->status,
                    'acknowledged_at' => $call->acknowledged_at,
                    'completed_at' => $call->completed_at
                ], 409);
            }

            $call->update([
                'status' => 'acknowledged',
                'acknowledged_at' => now()
            ]);

            // Notificar en Firebase que la llamada fue reconocida
            try {
                $call->loadMissing(['table','waiter']);
                $this->unifiedFirebaseService->writeCall($call, 'acknowledged');
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
            $call = WaiterCall::with('table')->find($callId);

            if (!$call) {
                return response()->json([
                    'success' => false,
                    'message' => 'Llamada no encontrada'
                ], 404);
            }

            $isAssignedWaiter = ((int)$call->waiter_id === (int)$waiter->id);
            $isCurrentTableWaiter = ((int)($call->table->active_waiter_id ?? 0) === (int)$waiter->id);
            if (!($isAssignedWaiter || $isCurrentTableWaiter)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para completar esta llamada'
                ], 403);
            }

            if (!in_array($call->status, ['pending','acknowledged'])) {
                // Idempotencia y resincronización: si ya estaba completada, asegurar limpieza en Firebase
                try {
                    $call->loadMissing(['table','waiter']);
                    if ($call->status === 'completed') {
                        $this->unifiedFirebaseService->removeCall($call);
                        return response()->json([
                            'success' => true,
                            'message' => 'Llamada ya estaba completada (resincronizada)'
                        ]);
                    }
                    if ($call->status === 'acknowledged') {
                        // Permitir completar aunque esté acknowledged (debería entrar al flujo normal arriba)
                    }
                } catch (\Throwable $t) {}
                return response()->json([
                    'success' => false,
                    'message' => 'Llamada ya procesada',
                    'status' => $call->status,
                    'acknowledged_at' => $call->acknowledged_at,
                    'completed_at' => $call->completed_at
                ], 409);
            }

            $call->update([
                'status' => 'completed',
                'completed_at' => now(),
                'acknowledged_at' => $call->acknowledged_at ?: now()
            ]);

            // Notificar en Firebase que la llamada fue completada
            try {
                $call->loadMissing(['table','waiter']);
                $this->unifiedFirebaseService->writeCall($call, 'completed');
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
     * Re-sincronizar una llamada con Firebase (forzar estado actual)
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

            // Autorización similar a acknowledge/complete
            $isAssignedWaiter = ((int)$call->waiter_id === (int)$waiter->id);
            $isCurrentTableWaiter = ((int)($call->table->active_waiter_id ?? 0) === (int)$waiter->id);
            if (!($isAssignedWaiter || $isCurrentTableWaiter)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para resincronizar esta llamada'
                ], 403);
            }

            // Elegir acción según estado actual
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

    // ===== ADMINISTRATIVE METHODS =====

    /**
     * Obtener estado de mesas silenciadas
     */
    public function getSilencedTables(Request $request): JsonResponse
    {
        $user = Auth::user();

        // AUTO-CORRECCIÓN: Asegurar business_id
        $businessId = $this->ensureBusinessId($user);
        
        // Verificar si el usuario tiene negocio activo
        if (!$businessId) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un negocio activo seleccionado. Debes unirte a un negocio primero.'
            ], 400);
        }

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
     * Listar IPs bloqueadas
     */
    public function getBlockedIps(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            // Si la tabla ip_blocks no existe (producción parcial), devolver lista vacía
            if (!Schema::hasTable('ip_blocks')) {
                return response()->json([
                    'success' => true,
                    'blocked_ips' => [],
                    'count' => 0,
                    'active_count' => 0,
                    'message' => 'Funcionalidad de bloqueo de IPs no instalada'
                ]);
            }
            // AUTO-CORRECCIÓN: Asegurar business_id
            $businessId = $this->ensureBusinessId($waiter);
            
            if (!$businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un negocio activo seleccionado. Debes unirte a un negocio primero.'
                ], 400);
            }

            $query = IpBlock::with(['blockedBy'])
                ->where('business_id', $businessId);

            // Filtros opcionales
            if ($request->has('active_only') && $request->boolean('active_only')) {
                $query->active();
            }

            if ($request->has('ip')) {
                $query->where('ip_address', $request->ip);
            }

            $blockedIps = $query->orderBy('blocked_at', 'desc')
                ->get()
                ->map(function ($ipBlock) {
                    return [
                        'id' => $ipBlock->id,
                        'ip_address' => $ipBlock->ip_address,
                        'reason' => $ipBlock->reason,
                        'blocked_by' => $ipBlock->blockedBy ? $ipBlock->blockedBy->name : 'Sistema',
                        'blocked_at' => $ipBlock->blocked_at,
                        'unblocked_at' => $ipBlock->unblocked_at,
                        'is_active' => !$ipBlock->unblocked_at,
                        'duration_blocked' => $ipBlock->blocked_at->diffForHumans()
                    ];
                });

            return response()->json([
                'success' => true,
                'blocked_ips' => $blockedIps,
                'count' => $blockedIps->count(),
                'active_count' => $blockedIps->where('is_active', true)->count()
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
     * Activar múltiples mesas
     */
    public function activateMultipleTables(Request $request): JsonResponse
    {
        $request->validate([
            'table_ids' => 'required|array|min:1',
            'table_ids.*' => 'integer|exists:tables,id'
        ]);

        $waiter = Auth::user();
        // AUTO-CORRECCIÓN: Asegurar business_id
        $businessId = $this->ensureBusinessId($waiter);
        $tableIds = $request->table_ids;
        $results = [];
        $errors = [];

        try {
            if (!$businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un negocio activo seleccionado'
                ], 400);
            }

            // Verificar que el usuario sea staff confirmado del negocio
            $isConfirmedStaff = Staff::where('user_id', $waiter->id)
                ->where('business_id', $businessId)
                ->where('status', 'confirmed')
                ->exists();
            if (!$isConfirmedStaff) {
                return response()->json([
                    'success' => false,
                    'message' => 'No estás habilitado para administrar mesas en este negocio'
                ], 403);
            }

            // Verificar que todas las mesas pertenezcan al negocio del mozo
            $tables = Table::whereIn('id', $tableIds)
                ->where('business_id', $businessId)
                ->get();

            if ($tables->count() !== count($tableIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Algunas mesas no existen o no tienes acceso a ellas'
                ], 400);
            }

            foreach ($tables as $table) {
                if ($table->active_waiter_id) {
                    $errors[] = [
                        'table_id' => $table->id,
                        'table_number' => $table->number,
                        'error' => 'Ya está asignada a otro mozo'
                    ];
                    continue;
                }

                $updateData = [
                    'active_waiter_id' => $waiter->id,
                ];
                if (Schema::hasColumn('tables', 'waiter_assigned_at')) {
                    $updateData['waiter_assigned_at'] = now();
                }
                $table->update($updateData);

                $results[] = [
                    'table_id' => $table->id,
                    'table_number' => $table->number,
                    'status' => 'activated',
                    'assigned_at' => $table->waiter_assigned_at
                ];
            }

            Log::info('Multiple tables activated', [
                'waiter_id' => $waiter->id,
                'waiter_name' => $waiter->name,
                'activated_count' => count($results),
                'errors_count' => count($errors),
                'business_id' => $businessId
            ]);

            return response()->json([
                'success' => true,
                'message' => count($results) . ' mesa(s) activada(s) exitosamente',
                'activated_tables' => $results,
                'errors' => $errors,
                'summary' => [
                    'total_requested' => count($tableIds),
                    'activated' => count($results),
                    'errors' => count($errors)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error activating multiple tables', [
                'waiter_id' => $waiter->id,
                'table_ids' => $tableIds,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error activando las mesas múltiples'
            ], 500);
        }
    }

    /**
     * Desactivar múltiples mesas
     */
    public function deactivateMultipleTables(Request $request): JsonResponse
    {
        $request->validate([
            'table_ids' => 'required|array|min:1',
            'table_ids.*' => 'integer|exists:tables,id'
        ]);

        $waiter = Auth::user();
        // AUTO-CORRECCIÓN: Asegurar business_id
        $businessId = $this->ensureBusinessId($waiter);
        $tableIds = $request->table_ids;
        $results = [];
        $errors = [];

        try {
            if (!$businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un negocio activo seleccionado'
                ], 400);
            }

            // Verificar que todas las mesas pertenezcan al negocio del mozo y estén asignadas a él
            $tables = Table::whereIn('id', $tableIds)
                ->where('business_id', $businessId)
                ->where('active_waiter_id', $waiter->id)
                ->get();

            if ($tables->count() !== count($tableIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Algunas mesas no existen, no tienes acceso o no están asignadas a ti'
                ], 400);
            }

            foreach ($tables as $table) {
                $updateData = [
                    'active_waiter_id' => null,
                ];
                if (Schema::hasColumn('tables', 'waiter_assigned_at')) {
                    $updateData['waiter_assigned_at'] = null;
                }
                $table->update($updateData);

                $results[] = [
                    'table_id' => $table->id,
                    'table_number' => $table->number,
                    'status' => 'deactivated'
                ];
            }

            Log::info('Multiple tables deactivated', [
                'waiter_id' => $waiter->id,
                'waiter_name' => $waiter->name,
                'deactivated_count' => count($results),
                'business_id' => $businessId
            ]);

            return response()->json([
                'success' => true,
                'message' => count($results) . ' mesa(s) desactivada(s) exitosamente',
                'deactivated_tables' => $results,
                'summary' => [
                    'total_requested' => count($tableIds),
                    'deactivated' => count($results)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error deactivating multiple tables', [
                'waiter_id' => $waiter->id,
                'table_ids' => $tableIds,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error desactivando las mesas múltiples'
            ], 500);
        }
    }

    /**
     * Obtener dashboard del waiter
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        try {
            // AUTO-CORRECCIÓN: Asegurar business_id
            $businessId = $this->ensureBusinessId($waiter);

            if (!$businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un negocio activo seleccionado'
                ], 400);
            }

            // Estadísticas básicas del mozo
            $assignedTables = Table::where('business_id', $businessId)
                ->where('active_waiter_id', $waiter->id)
                ->count();

            $availableTables = Table::where('business_id', $businessId)
                ->whereNull('active_waiter_id')
                ->count();

            $pendingCalls = WaiterCall::where('waiter_id', $waiter->id)
                ->where('status', 'pending')
                ->whereHas('table', function ($q) use ($businessId) {
                    $q->where('business_id', $businessId);
                })
                ->count();

            // Llamadas recientes (últimas 24 horas)
            $recentCalls = WaiterCall::where('waiter_id', $waiter->id)
                ->where('called_at', '>=', now()->subDay())
                ->whereHas('table', function ($q) use ($businessId) {
                    $q->where('business_id', $businessId);
                })
                ->count();

            // Bandera de desvinculación: si no es staff confirmado en este negocio pero estaba antes
            $isConfirmedStaff = Staff::where('user_id', $waiter->id)
                ->where('business_id', $businessId)
                ->where('status', 'confirmed')
                ->exists();
            $showUnlinkedBanner = !$isConfirmedStaff;

            return response()->json([
                'success' => true,
                'waiter' => [
                    'id' => $waiter->id,
                    'name' => $waiter->name,
                    'active_business_id' => $businessId,
                ],
                'stats' => [
                    'assigned_tables' => $assignedTables,
                    'available_tables' => $availableTables,
                    'pending_calls' => $pendingCalls,
                    'calls_today' => $recentCalls,
                ],
                'available_to_assign' => $availableTables,
                'ui_flags' => [
                    'unlinked_banner' => $showUnlinkedBanner,
                    'unlinked_message' => $showUnlinkedBanner ? 'Fuiste desvinculado de este negocio. Contactá a un administrador si creés que es un error.' : null,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting waiter dashboard', [
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo el dashboard'
            ], 500);
        }
    }

    /**
     * Diagnóstico de usuario - verificar estado de business_id
     */
    public function diagnoseUser(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        // Buscar registros de staff para este usuario
        $staffRecords = Staff::where('user_id', $waiter->id)
            ->with('business')
            ->get();

        // Si tiene registros staff pero no business_id, fijar el primero
        if ($staffRecords->isNotEmpty() && !$waiter->business_id) {
            $firstBusiness = $staffRecords->first()->business;
            $waiter->update(['business_id' => $firstBusiness->id]);
            
            Log::info('Auto-fixed missing business_id', [
                'waiter_id' => $waiter->id,
                'business_id' => $firstBusiness->id,
                'business_name' => $firstBusiness->name
            ]);
        }

        return response()->json([
            'user_id' => $waiter->id,
            'user_name' => $waiter->name,
            'current_business_id' => $waiter->business_id,
            'staff_records' => $staffRecords->map(function($staff) {
                return [
                    'business_id' => $staff->business_id,
                    'business_name' => $staff->business->name,
                    'status' => $staff->status,
                    'position' => $staff->position
                ];
            }),
            'staff_count' => $staffRecords->count(),
            'needs_business_assignment' => $staffRecords->isNotEmpty() && !$waiter->business_id,
            'fixed_automatically' => $staffRecords->isNotEmpty() && !$waiter->business_id
        ]);
    }
} 
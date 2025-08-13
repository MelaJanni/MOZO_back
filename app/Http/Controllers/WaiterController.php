<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Profile;
use App\Models\Table;
use App\Models\User;
use App\Notifications\TableCalledNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WaiterController extends Controller
{
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

    public function listProfiles(Request $request)
    {
        $user = $request->user();
        
        $profiles = Profile::where('user_id', $user->id)
            ->with('tables')
            ->get();
        
        return response()->json([
            'profiles' => $profiles,
        ]);
    }

    public function createProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'tables' => 'sometimes|array',
            'tables.*' => 'exists:tables,id',
        ]);
        
        $user = $request->user();
        
        $profile = Profile::create([
            'user_id' => $user->id,
            'name' => $request->name,
        ]);
        
        if ($request->has('tables')) {
            $businessTables = Table::where('business_id', $user->business_id)
                ->whereIn('id', $request->tables)
                ->pluck('id');
            
            $profile->tables()->attach($businessTables);
        }
        
        return response()->json([
            'message' => 'Perfil creado exitosamente',
            'profile' => $profile->load('tables'),
        ], 201);
    }

    public function deleteProfile($id, Request $request)
    {
        $user = $request->user();
        
        $profile = Profile::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        $profile->tables()->detach();
        
        $profile->delete();
        
        return response()->json([
            'message' => 'Perfil eliminado exitosamente',
        ]);
    }

    public function updateProfile($id, Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'table_ids' => 'sometimes|array',
            'table_ids.*' => 'integer|exists:tables,id',
            'notes' => 'sometimes|string|max:500'
        ]);

        $user = $request->user();
        
        $profile = Profile::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Actualizar campos básicos
        if ($request->has('name')) {
            $profile->name = $request->name;
        }
        
        if ($request->has('notes')) {
            $profile->notes = $request->notes;
        }

        $profile->save();

        // Actualizar mesas si se proporcionan
        if ($request->has('table_ids')) {
            // Verificar que las mesas pertenezcan al negocio del usuario
            $businessTables = Table::where('business_id', $user->business_id)
                ->whereIn('id', $request->table_ids)
                ->pluck('id');

            // Sincronizar las mesas (reemplazar las existentes)
            $profile->tables()->sync($businessTables);
        }

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente',
            'profile' => $profile->load('tables')
        ]);
    }

    public function activateProfile($id, Request $request)
    {
        $user = $request->user();
        
        $profile = Profile::where('id', $id)
            ->where('user_id', $user->id)
            ->with('tables')
            ->firstOrFail();

        if ($profile->tables->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Este perfil no tiene mesas asignadas'
            ], 400);
        }

        $results = [];
        $successful = 0;
        $errors = 0;
        $alreadyAssigned = 0;

        DB::beginTransaction();

        try {
            foreach ($profile->tables as $table) {
                // Verificar si la mesa ya está asignada
                if ($table->active_waiter_id) {
                    if ($table->active_waiter_id === $user->id) {
                        $results[] = [
                            'table_id' => $table->id,
                            'table_number' => $table->number,
                            'success' => true,
                            'message' => 'Ya estás asignado a esta mesa',
                            'status' => 'already_assigned'
                        ];
                        $alreadyAssigned++;
                    } else {
                        $results[] = [
                            'table_id' => $table->id,
                            'table_number' => $table->number,
                            'success' => false,
                            'message' => 'Mesa ocupada por: ' . $table->activeWaiter->name,
                            'status' => 'occupied'
                        ];
                        $errors++;
                    }
                } else {
                    // Asignar mozo a la mesa
                    $table->assignWaiter($user);
                    $results[] = [
                        'table_id' => $table->id,
                        'table_number' => $table->number,
                        'success' => true,
                        'message' => 'Mesa activada correctamente',
                        'status' => 'activated'
                    ];
                    $successful++;
                }
            }

            DB::commit();

            // Marcar el perfil como último utilizado
            $profile->update(['last_used_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => "Perfil '{$profile->name}' activado. {$successful} mesas activadas, {$alreadyAssigned} ya asignadas, {$errors} ocupadas.",
                'profile' => [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'activated_at' => now()
                ],
                'summary' => [
                    'total_tables' => $profile->tables->count(),
                    'successful' => $successful,
                    'already_assigned' => $alreadyAssigned,
                    'errors' => $errors
                ],
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error activating profile', [
                'profile_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error activando el perfil'
            ], 500);
        }
    }
    
    public function fetchWaiterTables()
    {
        $user = Auth::user();
        
        $tables = Table::where('business_id', $user->business_id)
            ->orderBy('number', 'asc')
            ->get();
            
        $profileTables = DB::table('profile_table')
            ->join('profiles', 'profiles.id', '=', 'profile_table.profile_id')
            ->where('profiles.user_id', $user->id)
            ->pluck('table_id')
            ->toArray();
            
        $tables->transform(function ($table) use ($profileTables) {
            $table->is_assigned = in_array($table->id, $profileTables);
            return $table;
        });
        
        return response()->json([
            'tables' => $tables,
            'assigned_count' => count($profileTables)
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
} 
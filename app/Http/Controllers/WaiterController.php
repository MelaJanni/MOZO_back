<?php

namespace App\Http\Controllers;

use App\Models\Business;
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
} 
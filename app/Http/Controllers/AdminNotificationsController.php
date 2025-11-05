<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminNotificationsController extends Controller
{
    /**
     * Enviar notificación de prueba a todos los usuarios del negocio
     * 
     * Endpoint: POST /api/admin/send-test-notification
     */
    public function sendTestNotification(Request $request)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string|max:500',
        ]);
        
        $users = User::where('business_id', $request->business_id)->get();

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No hay usuarios en este negocio para enviar la notificación de prueba'
            ], 404);
        }

        $title = $request->title ?? 'Notificación de Prueba';
        $body = $request->body ?? 'Esta es una notificación de prueba del sistema';

        $notificationCount = 0;

        foreach ($users as $targetUser) {
            try {
                $targetUser->notify(new \App\Notifications\TestNotification($title, $body));
                $notificationCount++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return response()->json([
            'message' => "Notificación de prueba enviada exitosamente a {$notificationCount} usuarios",
            'users_notified' => $notificationCount,
            'total_users' => $users->count(),
        ]);
    }

    /**
     * Enviar notificación a un usuario específico
     * 
     * Endpoint: POST /api/admin/send-notification-to-user
     */
    public function sendNotificationToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:500',
            'data' => 'sometimes|array',
        ]);

        $targetUser = User::find($request->user_id);

        if (!$targetUser) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        if ($targetUser->business_id !== $request->business_id) {
            return response()->json(['message' => 'No tienes permisos para enviar notificaciones a este usuario'], 403);
        }

        try {
            $targetUser->notify(new \App\Notifications\UserSpecificNotification(
                $request->title,
                $request->body,
                $request->data ?? []
            ));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al enviar la notificación',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Notificación enviada exitosamente al usuario ' . $targetUser->name,
            'sent_to' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
            ]
        ]);
    }
}

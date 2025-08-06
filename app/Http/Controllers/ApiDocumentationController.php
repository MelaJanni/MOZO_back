<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiDocumentationController extends Controller
{
    /**
     * Lista todas las APIs disponibles
     */
    public function listAllApis(Request $request): JsonResponse
    {
        $apis = [
            'authentication' => [
                'POST /api/register' => 'Registrar usuario',
                'POST /api/login' => 'Iniciar sesión',
                'POST /api/logout' => 'Cerrar sesión (requiere auth)',
                'POST /api/forgot-password' => 'Recuperar contraseña',
                'POST /api/reset-password' => 'Restablecer contraseña',
            ],
            'notifications' => [
                'POST /api/device-token' => 'Registrar token FCM (requiere auth)',
                'GET /api/user/notifications' => 'Obtener notificaciones del usuario (requiere auth)',
                'POST /api/user/notifications/{id}/read' => 'Marcar notificación como leída (requiere auth)',
            ],
            'waiter_calls' => [
                'POST /api/tables/{table}/call-waiter' => 'Mesa llama al mozo (público)',
                'GET /api/waiter/calls/pending' => 'Obtener llamadas pendientes (requiere auth mozo)',
                'POST /api/waiter/calls/{call}/acknowledge' => 'Confirmar llamada (requiere auth mozo)',
                'POST /api/waiter/calls/{call}/complete' => 'Completar llamada (requiere auth mozo)',
                'GET /api/waiter/calls/history' => 'Historial de llamadas (requiere auth)',
            ],
            'table_management' => [
                'GET /api/waiter/tables/assigned' => 'Obtener mesas asignadas (requiere auth mozo)',
                'GET /api/waiter/tables/available' => 'Obtener mesas disponibles (requiere auth mozo)',
                'POST /api/waiter/tables/{table}/activate' => 'Activar mesa (requiere auth mozo)',
                'DELETE /api/waiter/tables/{table}/activate' => 'Desactivar mesa (requiere auth mozo)',
                'POST /api/waiter/tables/activate/multiple' => 'Activar múltiples mesas (requiere auth mozo)',
                'POST /api/waiter/tables/deactivate/multiple' => 'Desactivar múltiples mesas (requiere auth mozo)',
            ],
            'silence_management' => [
                'POST /api/waiter/tables/{table}/silence' => 'Silenciar mesa (requiere auth mozo)',
                'DELETE /api/waiter/tables/{table}/silence' => 'Quitar silencio (requiere auth mozo)',
                'POST /api/waiter/tables/silence/multiple' => 'Silenciar múltiples mesas (requiere auth mozo)',
                'POST /api/waiter/tables/unsilence/multiple' => 'Quitar silencio múltiple (requiere auth mozo)',
                'GET /api/waiter/tables/silenced' => 'Obtener mesas silenciadas (requiere auth)',
            ],
            'public_qr' => [
                'GET /api/qr/{businessSlug}/{tableHash}' => 'Resolver código QR (público)',
                'GET /api/menu/{menuId}/download' => 'Descargar menú PDF (público)',
                'GET /api/table/{tableId}/status' => 'Estado actual de mesa (público)',
            ],
            'admin' => [
                'GET /api/admin/calls/history' => 'Historial de llamadas admin (requiere auth admin)',
                'GET /api/admin/tables/silenced' => 'Mesas silenciadas admin (requiere auth admin)',
                'POST /api/admin/notifications/send-to-all' => 'Enviar notificación a todos (requiere auth admin)',
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'APIs disponibles del sistema de notificaciones MOZO',
            'apis' => $apis,
            'documentation' => [
                'waiter_notifications' => 'Ver waiter-notification-apis.txt para documentación completa',
                'public_qr' => 'Ver public-qr-apis.txt para documentación de QR públicos'
            ]
        ]);
    }
}
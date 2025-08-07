<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiDocumentationController extends Controller
{
    public function listAllApis(Request $request): JsonResponse
    {
        $apis = [
            'public_qr' => [
                'GET /api/qr/{businessSlug}/{tableHash}' => 'Resolver código QR (público)',
                'GET /api/menu/{menuId}/download' => 'Descargar menú PDF (público)',
                'GET /api/table/{tableId}/status' => 'Estado actual de mesa (público)',
            ],
            'waiter_calls' => [
                'POST /api/tables/{table}/call-waiter' => 'Mesa llama al mozo (público)',
                'GET /api/waiter/calls/pending' => 'Obtener llamadas pendientes (requiere auth mozo)',
                'POST /api/waiter/calls/{call}/acknowledge' => 'Confirmar llamada (requiere auth mozo)',
                'POST /api/waiter/calls/{call}/complete' => 'Completar llamada (requiere auth mozo)',
            ],
            'table_management' => [
                'GET /api/waiter/tables/assigned' => 'Obtener mesas asignadas (requiere auth mozo)',
                'GET /api/waiter/tables/available' => 'Obtener mesas disponibles (requiere auth mozo)',
                'POST /api/waiter/tables/{table}/activate' => 'Activar mesa (requiere auth mozo)',
                'DELETE /api/waiter/tables/{table}/activate' => 'Desactivar mesa (requiere auth mozo)',
                'POST /api/waiter/tables/activate/multiple' => 'Activar múltiples mesas (requiere auth mozo)',
                'POST /api/waiter/tables/deactivate/multiple' => 'Desactivar múltiples mesas (requiere auth mozo)',
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'APIs disponibles del sistema MOZO',
            'apis' => $apis,
            'documentation' => [
                'waiter_notifications' => 'Ver waiter-notification-apis.txt para documentación completa',
                'public_qr' => 'Ver public-qr-apis.txt para documentación de QR públicos'
            ]
        ]);
    }
}
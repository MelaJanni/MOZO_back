<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FirebaseConfigController extends Controller
{
    /**
     * Get Firebase configuration for frontend
     */
    public function getConfig(): JsonResponse
    {
        $config = [
            'apiKey' => config('services.firebase.api_key') ?: config('services.firebase.server_key'),
            'authDomain' => config('services.firebase.auth_domain') ?: config('services.firebase.project_id') . '.firebaseapp.com',
            'projectId' => config('services.firebase.project_id'),
            'storageBucket' => config('services.firebase.storage_bucket') ?: config('services.firebase.project_id') . '.appspot.com',
            'messagingSenderId' => config('services.firebase.messaging_sender_id'),
            'appId' => config('services.firebase.app_id'),
        ];

        // 🔧 DIAGNOSTICS: Verificar qué configuraciones están disponibles
        $diagnostics = [
            'has_api_key' => !empty($config['apiKey']),
            'has_auth_domain' => !empty($config['authDomain']),
            'has_project_id' => !empty($config['projectId']),
            'has_storage_bucket' => !empty($config['storageBucket']),
            'has_messaging_sender_id' => !empty($config['messagingSenderId']),
            'has_app_id' => !empty($config['appId']),
            'service_account_configured' => !empty(config('services.firebase.service_account_path')) && 
                                           file_exists(config('services.firebase.service_account_path')),
        ];

        return response()->json([
            'success' => true,
            'firebase_config' => $config,
            'realtime_endpoints' => [
                'table_calls' => '/tables/{table_id}/waiter_calls',
                'table_status' => '/tables/{table_id}/status/current'
            ],
            'diagnostics' => $diagnostics,
            'ready_for_realtime' => $diagnostics['has_project_id'] && $diagnostics['has_api_key'],
            'backend_ready' => $diagnostics['service_account_configured'],
            'timestamp' => now()
        ]);
    }

    /**
     * Get Firebase configuration specifically for QR tables
     */
    public function getQrTableConfig(string $tableId): JsonResponse
    {
        // Verificar que la tabla existe y obtener información básica
        $table = \App\Models\Table::find($tableId);
        
        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Mesa no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'table' => [
                'id' => $table->id,
                'number' => $table->number,
                'name' => $table->name,
                'business_id' => $table->business_id,
                'notifications_enabled' => $table->notifications_enabled,
                'has_active_waiter' => !is_null($table->active_waiter_id)
            ],
            'firebase_config' => [
                'apiKey' => config('services.firebase.api_key') ?: config('services.firebase.server_key'),
                'authDomain' => config('services.firebase.auth_domain') ?: config('services.firebase.project_id') . '.firebaseapp.com',
                'projectId' => config('services.firebase.project_id'),
                'storageBucket' => config('services.firebase.storage_bucket') ?: config('services.firebase.project_id') . '.appspot.com',
                'messagingSenderId' => config('services.firebase.messaging_sender_id'),
                'appId' => config('services.firebase.app_id'),
            ],
            'firestore_paths' => [
                'table_calls' => "tables/{$table->id}/waiter_calls",
                'table_status' => "tables/{$table->id}/status/current"
            ]
        ]);
    }
}
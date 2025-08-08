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
        return response()->json([
            'success' => true,
            'firebase_config' => [
                'apiKey' => config('services.firebase.api_key'),
                'authDomain' => config('services.firebase.auth_domain'),
                'projectId' => config('services.firebase.project_id'),
                'storageBucket' => config('services.firebase.storage_bucket'),
                'messagingSenderId' => config('services.firebase.messaging_sender_id'),
                'appId' => config('services.firebase.app_id'),
            ],
            'realtime_endpoints' => [
                'table_calls' => '/tables/{table_id}/waiter_calls',
                'table_status' => '/tables/{table_id}/status/current'
            ]
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
                'apiKey' => config('services.firebase.api_key'),
                'authDomain' => config('services.firebase.auth_domain'),
                'projectId' => config('services.firebase.project_id'),
                'storageBucket' => config('services.firebase.storage_bucket'),
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
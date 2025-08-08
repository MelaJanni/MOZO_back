<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class FirebaseRealtimeService
{
    private $client;
    private $projectId;
    private $accessToken;

    public function __construct()
    {
        $this->client = new Client();
        $this->projectId = config('services.firebase.project_id');
        $this->accessToken = $this->getAccessToken();
    }

    /**
     * Get OAuth 2.0 access token for Firestore
     */
    private function getAccessToken()
    {
        $serviceAccountPath = config('services.firebase.service_account_path');
        
        if (!file_exists($serviceAccountPath)) {
            throw new \Exception("Firebase service account file not found");
        }

        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
        
        // Create JWT for Firestore scope
        $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
        $now = time();
        $payload = json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = '';
        openssl_sign($base64Header . '.' . $base64Payload, $signature, $serviceAccount['private_key'], 'SHA256');
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;

        try {
            $response = $this->client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]
            ]);

            $tokenData = json_decode($response->getBody(), true);
            return $tokenData['access_token'];
        } catch (RequestException $e) {
            Log::error('Failed to get Firestore access token: ' . $e->getMessage());
            throw new \Exception('Failed to authenticate with Firestore');
        }
    }

    /**
     * Write waiter call data to Firestore for real-time updates
     */
    public function writeWaiterCall($call, $eventType = 'created')
    {
        try {
            // Estructura del documento para tiempo real
            $document = [
                'fields' => [
                    'id' => ['integerValue' => (string)$call->id],
                    'table_id' => ['integerValue' => (string)$call->table_id],
                    'table_number' => ['integerValue' => (string)$call->table->number],
                    'waiter_id' => ['integerValue' => (string)$call->waiter_id],
                    'waiter_name' => ['stringValue' => $call->waiter->name ?? 'Mozo'],
                    'status' => ['stringValue' => $call->status],
                    'message' => ['stringValue' => $call->message],
                    'event_type' => ['stringValue' => $eventType],
                    'called_at' => ['timestampValue' => $call->called_at->toISOString()],
                    'acknowledged_at' => ['timestampValue' => $call->acknowledged_at?->toISOString()],
                    'completed_at' => ['timestampValue' => $call->completed_at?->toISOString()],
                    'timestamp' => ['timestampValue' => now()->toISOString()]
                ]
            ];

            // Escribir en múltiples documentos para diferentes listeners
            $this->writeDocument("tables/{$call->table_id}/waiter_calls", $call->id, $document);
            $this->writeDocument("waiters/{$call->waiter_id}/calls", $call->id, $document);
            $this->writeDocument("businesses/{$call->table->business_id}/waiter_calls", $call->id, $document);

            Log::info("Firestore waiter call written successfully", [
                'call_id' => $call->id,
                'event_type' => $eventType,
                'table_id' => $call->table_id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to write waiter call to Firestore", [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Write table status change to Firestore
     */
    public function writeTableStatus($table, $statusType, $statusData = [])
    {
        try {
            $document = [
                'fields' => [
                    'table_id' => ['integerValue' => (string)$table->id],
                    'table_number' => ['integerValue' => (string)$table->number],
                    'table_name' => ['stringValue' => $table->name],
                    'status_type' => ['stringValue' => $statusType],
                    'status_data' => ['stringValue' => json_encode($statusData)],
                    'notifications_enabled' => ['booleanValue' => $table->notifications_enabled],
                    'active_waiter_id' => ['integerValue' => (string)($table->active_waiter_id ?? 0)],
                    'active_waiter_name' => ['stringValue' => $table->activeWaiter->name ?? ''],
                    'is_silenced' => ['booleanValue' => $table->isSilenced()],
                    'timestamp' => ['timestampValue' => now()->toISOString()]
                ]
            ];

            // Escribir en documentos específicos para table status
            $this->writeDocument("tables/{$table->id}/status", 'current', $document);
            $this->writeDocument("businesses/{$table->business_id}/table_status", $table->id, $document);

            Log::info("Firestore table status written successfully", [
                'table_id' => $table->id,
                'status_type' => $statusType
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to write table status to Firestore", [
                'table_id' => $table->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Write general notification to Firestore
     */
    public function writeNotification($userId, $notification)
    {
        try {
            $document = [
                'fields' => [
                    'user_id' => ['integerValue' => (string)$userId],
                    'title' => ['stringValue' => $notification['title'] ?? ''],
                    'body' => ['stringValue' => $notification['body'] ?? ''],
                    'data' => ['stringValue' => json_encode($notification['data'] ?? [])],
                    'timestamp' => ['timestampValue' => now()->toISOString()]
                ]
            ];

            $this->writeDocument("users/{$userId}/notifications", uniqid(), $document);

            Log::info("Firestore notification written successfully", [
                'user_id' => $userId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to write notification to Firestore", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Write document to Firestore using REST API
     */
    private function writeDocument($collection, $documentId, $document)
    {
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}/{$documentId}";

        $response = $this->client->patch($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => $document,
            'query' => ['updateMask.fieldPaths' => '*']
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Delete document from Firestore
     */
    public function deleteDocument($collection, $documentId)
    {
        try {
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$collection}/{$documentId}";

            $this->client->delete($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                ]
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to delete document from Firestore", [
                'collection' => $collection,
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Write waiter call completion - removes from pending
     */
    public function completeWaiterCall($call)
    {
        // Escribir estado completado
        $this->writeWaiterCall($call, 'completed');
        
        // Eliminar de documentos "pendientes" después de 30 segundos
        // (en producción usarías una job queue)
        Log::info("Waiter call completed, should cleanup pending documents", [
            'call_id' => $call->id
        ]);

        return true;
    }
}
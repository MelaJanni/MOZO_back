<?php
/**
 * 🔥 FIREBASE WRITE SIMPLE - PARA INTEGRAR EN CUALQUIER ENDPOINT
 * Copy-paste este código en cualquier endpoint que funcione
 */

function writeToFirebaseRealtimeSimple($callData, $waiterId) {
    try {
        $databaseUrl = "https://mozoqr-7d32c-default-rtdb.firebaseio.com";
        $url = "{$databaseUrl}/waiters/{$waiterId}/calls/{$callData['id']}.json";
        
        // Preparar datos en formato exacto que espera el frontend
        $firebaseData = [
            'id' => (string)$callData['id'],
            'table_number' => (int)$callData['table_number'],
            'table_id' => isset($callData['table_id']) ? (int)$callData['table_id'] : null,
            'message' => (string)$callData['message'],
            'urgency' => isset($callData['urgency']) ? (string)$callData['urgency'] : 'normal',
            'status' => 'pending',
            'timestamp' => time() * 1000, // DEBE ser milliseconds
            'called_at' => time() * 1000   // DEBE ser milliseconds
        ];
        
        // HTTP PUT directo sin autenticación
        $context = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($firebaseData),
                'timeout' => 5
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response !== false) {
            error_log("✅ Firebase write SUCCESS: {$callData['id']} → waiter {$waiterId}");
            return true;
        } else {
            error_log("❌ Firebase write FAILED: {$callData['id']}");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("❌ Firebase write ERROR: " . $e->getMessage());
        return false;
    }
}

/**
 * 🔥 EJEMPLO DE USO EN TU ENDPOINT EXISTENTE
 */
function ejemploIntegracion() {
    // Después de crear WaiterCall en tu BD:
    
    // $call = WaiterCall::create([...]);
    
    // Preparar datos para Firebase
    $firebaseCallData = [
        'id' => $call->id,                    // ID de la llamada
        'table_number' => $call->table->number, // Número de mesa
        'table_id' => $call->table_id,        // ID interno
        'message' => $call->message,          // Mensaje del mozo
        'urgency' => $call->metadata['urgency'] ?? 'normal'
    ];
    
    // Escribir a Firebase
    writeToFirebaseRealtimeSimple($firebaseCallData, $call->waiter_id);
    
    // ✅ Frontend detectará automáticamente la nueva llamada
}

/**
 * 🔄 ACTUALIZAR STATUS DE LLAMADA
 */
function updateFirebaseCallStatus($callId, $waiterId, $status) {
    $databaseUrl = "https://mozoqr-7d32c-default-rtdb.firebaseio.com";
    
    if ($status === 'completed') {
        // Eliminar llamada completada
        $url = "{$databaseUrl}/waiters/{$waiterId}/calls/{$callId}.json";
        $context = stream_context_create([
            'http' => ['method' => 'DELETE', 'timeout' => 5]
        ]);
        file_get_contents($url, false, $context);
    } else {
        // Actualizar status
        $url = "{$databaseUrl}/waiters/{$waiterId}/calls/{$callId}/status.json";
        $context = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($status),
                'timeout' => 5
            ]
        ]);
        file_get_contents($url, false, $context);
    }
}

/**
 * 🧪 FUNCIÓN DE TEST
 */
function testFirebaseWrite() {
    $testData = [
        'id' => 'test_backend_' . time(),
        'table_number' => 99,
        'message' => 'Test desde backend PHP',
        'urgency' => 'high'
    ];
    
    $result = writeToFirebaseRealtimeSimple($testData, 2); // waiter ID 2
    
    return [
        'test_attempted' => true,
        'success' => $result,
        'test_data' => $testData,
        'firebase_url' => "https://mozoqr-7d32c-default-rtdb.firebaseio.com/waiters/2/calls/{$testData['id']}.json"
    ];
}

/**
 * 🎯 INTEGRACIÓN RÁPIDA EN ENDPOINT EXISTENTE
 * 
 * Agregar estas 3 líneas en tu endpoint de llamada existente:
 */
/*

// Después de: $call = WaiterCall::create([...]);
$firebaseData = ['id' => $call->id, 'table_number' => $call->table->number, 'message' => $call->message];
writeToFirebaseRealtimeSimple($firebaseData, $call->waiter_id);
// ✅ Frontend detectará la llamada instantáneamente

*/
?>
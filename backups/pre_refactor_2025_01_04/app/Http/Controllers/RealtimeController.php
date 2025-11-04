<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaiterCall;
use Illuminate\Support\Facades\Log;

class RealtimeController extends Controller
{
    /**
     * Server-Sent Events para tiempo real de notificaciones de mesa
     */
    public function tableCallStream($tableId)
    {
        Log::info("SSE connection requested for table: " . $tableId);
        
        return response()->stream(function () use ($tableId) {
            Log::info("Starting SSE stream for table: " . $tableId);
            
            // Headers SSE
            set_time_limit(0); // No time limit para SSE
            ignore_user_abort(false); // Stop if client disconnects
            
            // Mensaje inicial de conexión
            echo "data: " . json_encode([
                'type' => 'connected',
                'table_id' => (int)$tableId,
                'message' => 'Real-time stream connected',
                'timestamp' => now()->toISOString()
            ]) . "\n\n";
            
            if (ob_get_level()) ob_end_flush();
            flush();
            
            $lastStatus = null;
            $maxIterations = 120; // 4 minutos máximo
            $iterations = 0;
            
            while ($iterations < $maxIterations && connection_status() == CONNECTION_NORMAL) {
                $iterations++;
                
                try {
                    // Buscar la llamada más reciente de esta mesa
                    $latestCall = WaiterCall::with(['waiter'])
                        ->where('table_id', $tableId)
                        ->orderBy('called_at', 'desc')
                        ->first();
                    
                    $currentStatus = null;
                    if ($latestCall) {
                        $currentStatus = [
                            'call_id' => $latestCall->id,
                            'status' => $latestCall->status,
                            'waiter_name' => $latestCall->waiter->name ?? 'Mozo',
                            'called_at' => $latestCall->called_at->toISOString(),
                            'acknowledged_at' => $latestCall->acknowledged_at?->toISOString(),
                            'completed_at' => $latestCall->completed_at?->toISOString(),
                        ];
                    }
                    
                    // Solo enviar si cambió el estado
                    if ($currentStatus !== $lastStatus) {
                        echo "data: " . json_encode([
                            'type' => 'call_update',
                            'table_id' => (int)$tableId,
                            'call' => $currentStatus,
                            'timestamp' => now()->toISOString()
                        ]) . "\n\n";
                        flush();
                        
                        $lastStatus = $currentStatus;
                        
                        // Si el mozo confirmó, mantener la conexión 3 segundos más y cerrar
                        if ($latestCall && $latestCall->status === 'acknowledged') {
                            sleep(3);
                            echo "data: " . json_encode([
                                'type' => 'connection_close', 
                                'reason' => 'call_acknowledged'
                            ]) . "\n\n";
                            flush();
                            break;
                        }
                    }
                    
                    // Heartbeat cada 10 iteraciones para mantener viva la conexión
                    if ($iterations % 10 === 0) {
                        echo "data: " . json_encode([
                            'type' => 'heartbeat',
                            'timestamp' => now()->toISOString()
                        ]) . "\n\n";
                        flush();
                    }
                    
                } catch (\Exception $e) {
                    Log::error("SSE error for table $tableId: " . $e->getMessage());
                    echo "data: " . json_encode([
                        'type' => 'error',
                        'message' => 'Error checking call status',
                        'timestamp' => now()->toISOString()
                    ]) . "\n\n";
                    flush();
                }
                
                sleep(2); // Verificar cada 2 segundos
            }
            
            // Mensaje final
            echo "data: " . json_encode([
                'type' => 'connection_close', 
                'reason' => connection_status() != CONNECTION_NORMAL ? 'client_disconnect' : 'timeout'
            ]) . "\n\n";
            flush();
            
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Para Nginx
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Cache-Control'
        ]);
    }
    
    /**
     * Endpoint simple para probar conectividad 
     */
    public function testStream()
    {
        return response()->stream(function () {
            echo "data: " . json_encode([
                'type' => 'test',
                'message' => 'SSE test working',
                'timestamp' => now()->toISOString()
            ]) . "\n\n";
            flush();
            
            for ($i = 1; $i <= 5; $i++) {
                sleep(1);
                echo "data: " . json_encode([
                    'type' => 'counter',
                    'count' => $i,
                    'timestamp' => now()->toISOString()
                ]) . "\n\n";
                flush();
            }
            
            echo "data: " . json_encode([
                'type' => 'test_complete',
                'message' => 'SSE test completed successfully',
                'timestamp' => now()->toISOString()
            ]) . "\n\n";
            flush();
            
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
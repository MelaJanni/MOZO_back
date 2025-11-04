<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\WaiterCall;
use Illuminate\Support\Facades\Log;

class NotificationStreamController extends Controller
{
    /**
     * Server-Sent Events stream para notificaciones en tiempo real
     * Alternativa a Firebase cuando falla
     */
    public function stream(Request $request)
    {
        $waiterId = $request->user()->id;
        
        // Headers para SSE
        return response()->stream(function () use ($waiterId) {
            // Set headers for SSE
            echo "data: " . json_encode(['type' => 'connected', 'message' => 'Stream connected']) . "\n\n";
            ob_flush();
            flush();
            
            $lastCheck = now();
            
            while (true) {
                // Check for new notifications every 2 seconds
                $newCalls = WaiterCall::where('waiter_id', $waiterId)
                    ->where('status', 'pending')
                    ->where('created_at', '>', $lastCheck)
                    ->with(['table'])
                    ->get();
                
                foreach ($newCalls as $call) {
                    $data = [
                        'type' => 'waiter_call',
                        'id' => $call->id,
                        'table_number' => $call->table->number,
                        'message' => $call->message,
                        'called_at' => $call->called_at->toISOString(),
                        'urgency' => $call->metadata['urgency'] ?? 'normal'
                    ];
                    
                    echo "data: " . json_encode($data) . "\n\n";
                    ob_flush();
                    flush();
                }
                
                $lastCheck = now();
                sleep(2); // Check every 2 seconds
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ]);
    }
    
    /**
     * Polling endpoint más eficiente para notificaciones
     */
    public function poll(Request $request): JsonResponse
    {
        $waiterId = $request->user()->id;
        $lastCheckTimestamp = $request->input('last_check', now()->subMinutes(1)->timestamp);
        $lastCheck = \Carbon\Carbon::createFromTimestamp($lastCheckTimestamp);
        
        // Obtener nuevas llamadas desde la última verificación
        $newCalls = WaiterCall::where('waiter_id', $waiterId)
            ->where('status', 'pending')
            ->where('created_at', '>', $lastCheck)
            ->with(['table.business'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'table_id' => $call->table->id,
                    'table_number' => $call->table->number,
                    'business_name' => $call->table->business->name ?? 'Restaurant',
                    'message' => $call->message,
                    'called_at' => $call->called_at->toISOString(),
                    'urgency' => $call->metadata['urgency'] ?? 'normal',
                    'minutes_ago' => $call->called_at->diffInMinutes(now())
                ];
            });
        
        return response()->json([
            'success' => true,
            'new_calls' => $newCalls,
            'total_pending' => WaiterCall::where('waiter_id', $waiterId)
                ->where('status', 'pending')->count(),
            'last_check' => now()->timestamp,
            'poll_interval' => $newCalls->count() > 0 ? 1000 : 3000 // 1s si hay llamadas, 3s si no
        ]);
    }
}
<?php

namespace App\Jobs;

use App\Models\WaiterCall;
use App\Services\FirebaseService;
use App\Services\FirebaseRealtimeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWaiterCallNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $call;
    
    public $timeout = 30;
    public $tries = 3;
    public $maxExceptions = 2;

    public function __construct(WaiterCall $call)
    {
        $this->call = $call;
        $this->onQueue('high-priority');
    }

    public function handle(FirebaseService $firebaseService, FirebaseRealtimeService $firebaseRealtimeService)
    {
        try {
            // Cargar relaciones necesarias
            $this->call->load(['table', 'waiter']);
            
            // 1. 🔥 FIREBASE REAL-TIME: Escritura INMEDIATA
            $firebaseRealtimeService->writeWaiterCall($this->call, 'created');
            
            // 2. 🚀 FCM PUSH NOTIFICATION con prioridad alta
            $title = "🔔 Mesa {$this->call->table->number}";
            $body = $this->call->message;
            $data = [
                'type' => 'waiter_call',
                'call_id' => (string)$this->call->id,
                'table_id' => (string)$this->call->table->id,
                'table_number' => (string)$this->call->table->number,
                'urgency' => $this->call->metadata['urgency'] ?? 'normal',
                'action' => 'acknowledge_call',
                'timestamp' => now()->timestamp
            ];

            // Siempre usar prioridad alta para llamadas de mozo
            $firebaseService->sendToUser($this->call->waiter_id, $title, $body, $data, 'high');

            Log::info('Waiter call notification processed successfully', [
                'call_id' => $this->call->id,
                'waiter_id' => $this->call->waiter_id,
                'table_id' => $this->call->table->id,
                'processing_time' => microtime(true) - LARAVEL_START
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process waiter call notification', [
                'call_id' => $this->call->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-lanzar la excepción para que el job sea reintentado
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Waiter call notification job failed permanently', [
            'call_id' => $this->call->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
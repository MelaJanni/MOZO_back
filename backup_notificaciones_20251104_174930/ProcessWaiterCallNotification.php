<?php

namespace App\Jobs;

use App\Models\WaiterCall;
use App\Services\FirebaseService;
use App\Services\UnifiedFirebaseService;
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

    public function handle(FirebaseService $firebaseService, UnifiedFirebaseService $unifiedFirebaseService)
    {
        try {
            // Cargar relaciones necesarias
            $this->call->load(['table', 'waiter']);
            
            // SOLO para llamadas nuevas (pending) - NO para updates
            if ($this->call->status !== 'pending') {
                Log::info('Skipping push notification for non-pending call', [
                    'call_id' => $this->call->id,
                    'status' => $this->call->status
                ]);
                return;
            }
            
            // 1. 🔥 UNIFIED STRUCTURE + FCM (internamente en el servicio)
            $unifiedFirebaseService->writeCall($this->call, 'created');

            Log::info('NEW waiter call processed (unified)', [
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
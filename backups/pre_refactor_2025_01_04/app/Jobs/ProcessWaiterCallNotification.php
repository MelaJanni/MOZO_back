<?php

namespace App\Jobs;

use App\Models\WaiterCall;
use App\Services\WaiterCallNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessWaiterCallNotification - Job para procesar notificaciones de llamadas de mesa
 *
 * V2: Usa WaiterCallNotificationService en lugar de UnifiedFirebaseService
 * Simplificado y más limpio
 */
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

    /**
     * Ejecutar el job
     *
     * @param WaiterCallNotificationService $waiterCallService
     * @return void
     */
    public function handle(WaiterCallNotificationService $waiterCallService)
    {
        try {
            // Cargar relaciones necesarias
            $this->call->load(['table', 'waiter']);

            // Solo procesar llamadas nuevas (pending)
            if ($this->call->status !== 'pending') {
                Log::info('Skipping notification for non-pending call', [
                    'call_id' => $this->call->id,
                    'status' => $this->call->status
                ]);
                return;
            }

            // Procesar nueva llamada usando el servicio especializado
            $success = $waiterCallService->processNewCall($this->call);

            if ($success) {
                Log::info('Waiter call notification processed successfully', [
                    'call_id' => $this->call->id,
                    'waiter_id' => $this->call->waiter_id,
                    'table_number' => $this->call->table?->number
                ]);
            } else {
                Log::warning('Waiter call notification processing returned false', [
                    'call_id' => $this->call->id
                ]);
            }

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

    /**
     * El job falló después de todos los reintentos
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Waiter call notification job failed permanently', [
            'call_id' => $this->call->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
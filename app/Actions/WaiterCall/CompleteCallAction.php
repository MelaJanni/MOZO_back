<?php

namespace App\Actions\WaiterCall;

use App\Models\WaiterCall;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Action para completar un llamado
 * 
 * Responsabilidades:
 * - Validar que el llamado está en progreso
 * - Actualizar estado a 'completed'
 * - Registrar timestamp de completado
 * - Calcular métricas (tiempo de respuesta)
 * - Actualizar Firebase
 */
class CompleteCallAction
{
    /**
     * Ejecuta la completación del llamado
     *
     * @param WaiterCall $call
     * @param int $waiterId ID del mozo que completa
     * @return array ['success' => bool, 'call' => WaiterCall, 'message' => string, 'metrics' => array]
     */
    public function execute(WaiterCall $call, int $waiterId): array
    {
        // TODO: Migrar lógica desde WaiterCallController.ORIGINAL::completeCall() línea 276-324
        //
        // Estructura:
        // 1. Validar que call->status == 'acknowledged' o 'pending'
        // 2. Validar que waiterId coincide con call->waiter_id
        // 3. Actualizar call->status = 'completed'
        // 4. Actualizar call->completed_at = now()
        // 5. Calcular métricas:
        //    - response_time = completed_at - called_at
        //    - acknowledge_time = acknowledged_at - called_at
        // 6. Guardar cambios
        // 7. Actualizar Firebase (writeImmediateFirebase)
        // 8. Retornar resultado con métricas
        
        if (!in_array($call->status, ['pending', 'acknowledged'])) {
            return [
                'success' => false,
                'call' => $call,
                'message' => 'El llamado ya fue completado o cancelado',
                'metrics' => []
            ];
        }

        // Implementación pendiente
        return [
            'success' => false,
            'call' => $call,
            'message' => 'Pendiente de implementación',
            'metrics' => []
        ];
    }

    /**
     * Calcula métricas de tiempo del llamado
     */
    private function calculateMetrics(WaiterCall $call): array
    {
        // TODO: Calcular response_time, acknowledge_time, efficiency_score
        return [
            'response_time_seconds' => 0,
            'acknowledge_time_seconds' => 0,
            'efficiency_score' => 0
        ];
    }
}

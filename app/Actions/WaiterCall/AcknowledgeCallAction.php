<?php

namespace App\Actions\WaiterCall;

use App\Models\WaiterCall;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Action para que un mozo acepte un llamado
 * 
 * Responsabilidades:
 * - Validar que el llamado existe y está pendiente
 * - Actualizar estado a 'acknowledged'
 * - Registrar timestamp de aceptación
 * - Actualizar Firebase
 */
class AcknowledgeCallAction
{
    /**
     * Ejecuta la aceptación del llamado
     *
     * @param WaiterCall $call
     * @param int $waiterId ID del mozo que acepta
     * @return array ['success' => bool, 'call' => WaiterCall, 'message' => string]
     */
    public function execute(WaiterCall $call, int $waiterId): array
    {
        // TODO: Migrar lógica desde WaiterCallController.ORIGINAL::acknowledgeCall() línea 230-275
        //
        // Estructura:
        // 1. Validar que call->status == 'pending'
        // 2. Validar que waiterId coincide con call->waiter_id
        // 3. Actualizar call->status = 'acknowledged'
        // 4. Actualizar call->acknowledged_at = now()
        // 5. Guardar cambios
        // 6. Actualizar Firebase (writeImmediateFirebase)
        // 7. Retornar resultado
        
        if ($call->status !== 'pending') {
            return [
                'success' => false,
                'call' => $call,
                'message' => 'El llamado ya no está pendiente'
            ];
        }

        // Implementación pendiente
        return [
            'success' => false,
            'call' => $call,
            'message' => 'Pendiente de implementación'
        ];
    }
}

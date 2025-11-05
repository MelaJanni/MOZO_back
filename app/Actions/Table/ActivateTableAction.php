<?php

namespace App\Actions\Table;

use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Action para activar una mesa y asignar un mozo
 * 
 * Responsabilidades:
 * - Activar la mesa (notifications_enabled)
 * - Asignar mozo activo
 * - Validar permisos del mozo
 * - Log de cambios
 */
class ActivateTableAction
{
    /**
     * Ejecuta la activación de la mesa
     *
     * @param Table $table
     * @param User $waiter Mozo a asignar
     * @return array ['success' => bool, 'table' => Table, 'message' => string]
     */
    public function execute(Table $table, User $waiter): array
    {
        // TODO: Migrar lógica desde WaiterCallController.ORIGINAL::activateTable() línea 596-666
        //
        // Estructura:
        // 1. Validar que el mozo pertenece al business de la mesa
        // 2. Actualizar table->active_waiter_id = waiter->id
        // 3. Actualizar table->notifications_enabled = true
        // 4. Guardar cambios
        // 5. Log de activación
        // 6. Retornar resultado
        
        try {
            // Implementación pendiente
            
            return [
                'success' => false,
                'table' => $table,
                'message' => 'Pendiente de implementación'
            ];
        } catch (\Exception $e) {
            Log::error('ActivateTableAction failed', [
                'table_id' => $table->id,
                'waiter_id' => $waiter->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'table' => $table,
                'message' => 'Error al activar mesa: ' . $e->getMessage()
            ];
        }
    }
}

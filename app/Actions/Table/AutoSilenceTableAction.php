<?php

namespace App\Actions\Table;

use App\Models\Table;
use App\Models\TableSilence;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Action para silenciar automáticamente una mesa por spam
 * 
 * Responsabilidades:
 * - Detectar comportamiento de spam (múltiples llamados rápidos)
 * - Crear TableSilence con duración apropiada
 * - Log de acción anti-spam
 */
class AutoSilenceTableAction
{
    /**
     * Ejecuta el auto-silencio de la mesa
     *
     * @param Table $table
     * @param int $callCount Número de llamados en periodo corto
     * @return bool Success status
     */
    public function execute(Table $table, int $callCount): bool
    {
        // TODO: Migrar lógica desde WaiterCallController.ORIGINAL::autoSilenceTable() línea 577-595
        //
        // Estructura:
        // 1. Calcular duración del silencio basado en callCount
        //    - 3-5 llamados: 5 minutos
        //    - 6-10 llamados: 15 minutos
        //    - 10+ llamados: 30 minutos
        // 2. Crear TableSilence con silenced_until
        // 3. Log de acción con motivo "auto_spam_detection"
        // 4. Retornar success
        
        try {
            $duration = $this->calculateSilenceDuration($callCount);
            
            // Implementación pendiente
            
            Log::info('AUTO_SILENCE_TABLE', [
                'table_id' => $table->id,
                'table_number' => $table->number,
                'call_count' => $callCount,
                'duration_minutes' => $duration,
                'reason' => 'spam_detection'
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('AutoSilenceTableAction failed', [
                'table_id' => $table->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Calcula duración del silencio basado en severidad del spam
     */
    private function calculateSilenceDuration(int $callCount): int
    {
        if ($callCount >= 10) {
            return 30; // 30 minutos para spam severo
        } elseif ($callCount >= 6) {
            return 15; // 15 minutos para spam moderado
        } else {
            return 5;  // 5 minutos para spam leve
        }
    }
}

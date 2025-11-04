<?php

namespace App\Console\Commands;

use App\Services\TokenManager;
use Illuminate\Console\Command;

/**
 * CleanExpiredTokens - Comando para limpiar tokens FCM expirados
 *
 * Se ejecuta diariamente vía Laravel Scheduler
 * Elimina tokens expirados y tokens antiguos sin fecha de expiración
 *
 * V2: Usa TokenManager en lugar de acceso directo al modelo
 */
class CleanExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Eliminar tokens FCM expirados y antiguos de la base de datos';

    private TokenManager $tokenManager;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(TokenManager $tokenManager)
    {
        parent::__construct();
        $this->tokenManager = $tokenManager;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🧹 Iniciando limpieza de tokens expirados...');

        try {
            // Usar TokenManager para limpieza
            $deleted = $this->tokenManager->cleanExpiredTokens();

            if ($deleted > 0) {
                $this->info("✅ Eliminados {$deleted} token(s) expirado(s)");
            } else {
                $this->info('✅ No hay tokens expirados para eliminar');
            }

            // Mostrar estadísticas usando TokenManager
            $stats = $this->tokenManager->getTokenStats();

            $this->newLine();
            $this->info('📊 Estadísticas de tokens:');
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Total de tokens', $stats['total'] ?? 0],
                    ['Tokens activos', $stats['active'] ?? 0],
                    ['Tokens expirados', $stats['expired'] ?? 0],
                ]
            );

            // Mostrar tokens por plataforma
            if (isset($stats['by_platform']) && !empty($stats['by_platform'])) {
                $this->newLine();
                $this->info('📱 Tokens por plataforma:');
                $platformData = [];
                foreach ($stats['by_platform'] as $platform => $count) {
                    $platformData[] = [ucfirst($platform), $count];
                }
                $this->table(['Plataforma', 'Cantidad'], $platformData);
            }

            $this->newLine();
            $this->info('✅ Limpieza completada exitosamente');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error al limpiar tokens: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\WaiterProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicateWaiterProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-duplicate-waiter-profiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean duplicate waiter profiles keeping the oldest one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Buscando perfiles duplicados...');

        // Encontrar user_ids duplicados
        $duplicates = DB::table('waiter_profiles')
            ->select('user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No se encontraron perfiles duplicados.');
            return;
        }

        $this->info("Se encontraron {$duplicates->count()} usuarios con perfiles duplicados.");

        $deletedCount = 0;

        foreach ($duplicates as $duplicate) {
            $this->info("Limpiando duplicados para user_id: {$duplicate->user_id}");

            // Obtener todos los perfiles para este usuario, ordenados por fecha de creación
            $profiles = WaiterProfile::where('user_id', $duplicate->user_id)
                ->orderBy('created_at', 'asc')
                ->get();

            // Mantener el primero (más antiguo) y eliminar el resto
            $profiles->shift(); // Remover el primer elemento del array (mantenerlo)

            foreach ($profiles as $profile) {
                $this->line("  Eliminando perfil ID: {$profile->id}");
                $profile->delete();
                $deletedCount++;
            }
        }

        $this->info("✅ Limpieza completada. Se eliminaron {$deletedCount} perfiles duplicados.");
    }
}

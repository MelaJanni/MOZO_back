<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WaiterProfile;
use Illuminate\Console\Command;

class FixMissingWaiterProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:missing-waiter-profiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea WaiterProfiles faltantes para usuarios que no tienen uno';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Buscando usuarios sin WaiterProfile...');

        // Buscar usuarios que no son super admins y no tienen WaiterProfile
        $usersWithoutProfile = User::where('is_system_super_admin', false)
            ->whereDoesntHave('waiterProfile')
            ->get();

        if ($usersWithoutProfile->isEmpty()) {
            $this->info('✅ Todos los usuarios tienen WaiterProfile');
            return 0;
        }

        $this->info("Encontrados {$usersWithoutProfile->count()} usuarios sin WaiterProfile");
        $this->newLine();

        $bar = $this->output->createProgressBar($usersWithoutProfile->count());
        $bar->start();

        $created = 0;
        $errors = 0;

        foreach ($usersWithoutProfile as $user) {
            try {
                WaiterProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'display_name' => $user->name,
                        'is_available' => true,
                        'is_available_for_hire' => true,
                    ]
                );
                $created++;
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error creando perfil para usuario {$user->id} ({$user->email}): {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Creados: {$created}");
        if ($errors > 0) {
            $this->error("❌ Errores: {$errors}");
        }

        return 0;
    }
}

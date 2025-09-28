<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WaiterProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseDuplicateProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:diagnose-duplicate-profiles {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose duplicate profile issues and show database state';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info('=== DIAGNÓSTICO DE PERFILES DUPLICADOS ===');
        $this->info('Fecha: ' . now());
        $this->newLine();

        // 1. Estado general de la base de datos
        $this->info('1. ESTADO GENERAL:');
        $totalUsers = User::count();
        $totalProfiles = WaiterProfile::count();
        $this->line("   Total usuarios: {$totalUsers}");
        $this->line("   Total waiter_profiles: {$totalProfiles}");
        $this->newLine();

        // 2. Usuarios sin perfil
        $usersWithoutProfile = User::whereDoesntHave('waiterProfile')
            ->where('is_system_super_admin', false)
            ->get();

        $this->info('2. USUARIOS SIN PERFIL:');
        if ($usersWithoutProfile->isEmpty()) {
            $this->line('   ✅ Todos los usuarios tienen perfil');
        } else {
            $this->line("   ❌ {$usersWithoutProfile->count()} usuarios sin perfil:");
            foreach ($usersWithoutProfile as $user) {
                $this->line("      - ID: {$user->id}, Email: {$user->email}");
            }
        }
        $this->newLine();

        // 3. Perfiles duplicados
        $this->info('3. PERFILES DUPLICADOS:');
        $duplicates = DB::table('waiter_profiles')
            ->select('user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->line('   ✅ No hay perfiles duplicados');
        } else {
            $this->line("   ❌ {$duplicates->count()} usuarios con perfiles duplicados:");
            foreach ($duplicates as $dup) {
                $profiles = WaiterProfile::where('user_id', $dup->user_id)->get();
                $user = User::find($dup->user_id);
                $this->line("      - User ID: {$dup->user_id} ({$user->email ?? 'N/A'}) - {$dup->count} perfiles:");
                foreach ($profiles as $profile) {
                    $this->line("        * Profile ID: {$profile->id}, Created: {$profile->created_at}");
                }
            }
        }
        $this->newLine();

        // 4. Análisis específico por email
        if ($email) {
            $this->info("4. ANÁLISIS ESPECÍFICO PARA: {$email}");
            $user = User::where('email', $email)->first();

            if (!$user) {
                $this->line('   ❌ Usuario no encontrado');
            } else {
                $this->line("   ✅ Usuario encontrado - ID: {$user->id}");
                $this->line("      Nombre: {$user->name}");
                $this->line("      Creado: {$user->created_at}");
                $this->line("      Super Admin: " . ($user->is_system_super_admin ? 'Sí' : 'No'));

                $profiles = WaiterProfile::where('user_id', $user->id)->get();
                $this->line("      Perfiles asociados: {$profiles->count()}");

                foreach ($profiles as $profile) {
                    $this->line("        - Profile ID: {$profile->id}");
                    $this->line("          Display Name: {$profile->display_name}");
                    $this->line("          Created: {$profile->created_at}");
                }
            }
            $this->newLine();
        }

        // 5. Verificar el código actual del UserObserver
        $this->info('5. VERIFICACIÓN DEL CÓDIGO:');
        $observerPath = app_path('Observers/UserObserver.php');
        if (file_exists($observerPath)) {
            $content = file_get_contents($observerPath);
            if (strpos($content, 'firstOrCreate') !== false) {
                $this->line('   ✅ UserObserver contiene firstOrCreate');
            } else {
                $this->line('   ❌ UserObserver NO contiene firstOrCreate');
            }

            if (strpos($content, 'exists()') !== false) {
                $this->line('   ✅ UserObserver contiene exists() check');
            } else {
                $this->line('   ❌ UserObserver NO contiene exists() check');
            }
        } else {
            $this->line('   ❌ UserObserver no encontrado');
        }
        $this->newLine();

        // 6. Último test de creación
        $this->info('6. TEST DE CREACIÓN:');
        $testEmail = 'test-' . time() . '@test.com';
        $this->line("   Intentando crear usuario de prueba: {$testEmail}");

        try {
            $testUser = User::create([
                'name' => 'Test User',
                'email' => $testEmail,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);

            $this->line("   ✅ Usuario creado - ID: {$testUser->id}");

            // Verificar si se creó el perfil
            sleep(1); // Dar tiempo al observer
            $profile = WaiterProfile::where('user_id', $testUser->id)->first();

            if ($profile) {
                $this->line("   ✅ Perfil creado automáticamente - ID: {$profile->id}");
            } else {
                $this->line("   ❌ Perfil NO se creó automáticamente");
            }

            // Limpiar
            $testUser->delete();
            $this->line("   ✅ Usuario de prueba eliminado");

        } catch (\Exception $e) {
            $this->line("   ❌ Error en test: " . $e->getMessage());
        }

        $this->newLine();
        $this->info('=== FIN DEL DIAGNÓSTICO ===');
    }
}

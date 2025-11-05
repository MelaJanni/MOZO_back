<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WaiterProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GoogleLoginWaiterProfileTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Re-habilitar observers para estos tests específicos
     * Estos tests están probando que el Observer funciona correctamente
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Restablecer event dispatcher para User (habilita observers)
        User::setEventDispatcher($this->app['events']);
    }

    /**
     * Test que el WaiterProfile se crea automáticamente al crear un usuario
     */
    public function test_waiter_profile_is_created_automatically_for_new_user(): void
    {
        // Crear usuario dentro de una transacción (simula loginWithGoogle)
        DB::transaction(function () {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'google_id' => '123456789',
                'google_avatar' => 'https://example.com/avatar.jpg',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]);

            // El UserObserver debería crear el WaiterProfile después del commit
            // pero dentro de la transacción aún no existe
            $this->assertInstanceOf(User::class, $user);
        });

        // Después del commit, el WaiterProfile debe existir
        $user = User::where('email', 'test@example.com')->first();
        
        $this->assertNotNull($user);
        $this->assertNotNull($user->waiterProfile);
        $this->assertEquals($user->name, $user->waiterProfile->display_name);
        $this->assertTrue($user->waiterProfile->is_available);
        $this->assertTrue((bool)$user->waiterProfile->is_available_for_hire); // Cast a bool por si acaso
    }

    /**
     * Test que no se crean WaiterProfiles duplicados
     */
    public function test_no_duplicate_waiter_profiles_are_created(): void
    {
        $user = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'google_id' => '987654321',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        // Esperar a que se procesen los afterCommit
        $this->artisan('queue:work', ['--once' => true]);

        // Intentar crear otro perfil manualmente (esto no debería crear duplicado)
        WaiterProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['display_name' => 'Another Name']
        );

        // Verificar que solo existe 1 perfil
        $profileCount = WaiterProfile::where('user_id', $user->id)->count();
        $this->assertEquals(1, $profileCount);
    }

    /**
     * Test que el WaiterProfile NO se crea para super admins
     */
    public function test_waiter_profile_not_created_for_super_admin(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'is_system_super_admin' => true,
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        // Esperar a que se procesen los afterCommit
        $this->artisan('queue:work', ['--once' => true]);

        // Verificar que NO se creó WaiterProfile
        $this->assertNull($admin->waiterProfile);
    }

    /**
     * Test del comando fix:missing-waiter-profiles
     */
    public function test_fix_missing_waiter_profiles_command(): void
    {
        // Crear usuario y eliminar su WaiterProfile manualmente
        $user = User::create([
            'name' => 'User Without Profile',
            'email' => 'noprofile@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        // Esperar a que se cree el perfil
        $this->artisan('queue:work', ['--once' => true]);

        // Eliminar el perfil para simular el problema
        WaiterProfile::where('user_id', $user->id)->delete();

        // Verificar que no tiene perfil
        $this->assertNull($user->fresh()->waiterProfile);

        // Ejecutar comando de fix
        $this->artisan('fix:missing-waiter-profiles')
            ->expectsOutput('Buscando usuarios sin WaiterProfile...')
            ->assertExitCode(0);

        // Verificar que ahora tiene perfil
        $user->refresh();
        $this->assertNotNull($user->waiterProfile);
    }
}

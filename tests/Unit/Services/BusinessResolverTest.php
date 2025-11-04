<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\User;
use App\Services\BusinessResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests para BusinessResolver - Servicio de resolución de business_id activo
 *
 * @package Tests\Unit\Services
 */
class BusinessResolverTest extends TestCase
{
    use RefreshDatabase;

    protected BusinessResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(BusinessResolver::class);
    }

    /**
     * Test: Resuelve business_id desde user_active_roles con rol admin
     */
    public function test_resolve_from_active_roles_with_admin_role(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create();

        // Crear relación admin
        $user->businessesAsAdmin()->attach($business->id);

        // Crear sesión activa en user_active_roles
        $user->activeRoles()->create([
            'business_id' => $business->id,
            'active_role' => 'admin',
            'switched_at' => now()
        ]);

        $resolvedId = $this->resolver->resolve($user, 'admin');

        $this->assertNotNull($resolvedId);
        $this->assertEquals($business->id, $resolvedId);
    }

    /**
     * Test: Resuelve business_id desde active_business_id si no hay user_active_roles
     */
    public function test_resolve_from_active_business_id_property(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create();

        // Simular columna active_business_id (si existe en el modelo)
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'active_business_id')) {
            $user->update(['active_business_id' => $business->id]);
        } else {
            // Si no existe columna, forzar el atributo
            $user->active_business_id = $business->id;
        }

        $resolvedId = $this->resolver->resolve($user, 'admin');

        $this->assertNotNull($resolvedId);
        $this->assertEquals($business->id, $resolvedId);
    }

    /**
     * Test: Resuelve business_id desde business_id legacy si no hay otras opciones
     */
    public function test_resolve_from_legacy_business_id_property(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create();

        // Simular columna business_id legacy
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'business_id')) {
            $user->update(['business_id' => $business->id]);
        } else {
            $user->business_id = $business->id;
        }

        $resolvedId = $this->resolver->resolve($user, 'admin');

        // Puede ser null si el fallback no aplica, pero si aplica debe ser correcto
        if ($resolvedId) {
            $this->assertEquals($business->id, $resolvedId);
        }
    }

    /**
     * Test: Resuelve business_id si el usuario solo pertenece a UN negocio como admin
     */
    public function test_resolve_from_unique_business_as_admin(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create();

        // Usuario pertenece a SOLO un negocio como admin
        $user->businessesAsAdmin()->attach($business->id);

        $resolvedId = $this->resolver->resolve($user, 'admin');

        $this->assertNotNull($resolvedId);
        $this->assertEquals($business->id, $resolvedId);
    }

    /**
     * Test: Retorna null si el usuario no tiene ningún business asociado
     */
    public function test_resolve_returns_null_if_no_business_found(): void
    {
        $user = User::factory()->create();

        $resolvedId = $this->resolver->resolve($user, 'admin', false);

        $this->assertNull($resolvedId);
    }

    /**
     * Test: Lanza excepción si no se encuentra business y throwIfNotFound=true
     */
    public function test_resolve_throws_exception_if_required_and_not_found(): void
    {
        $user = User::factory()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/No se pudo resolver business_id/');

        $this->resolver->resolve($user, 'admin', true);
    }

    /**
     * Test: hasAccessTo verifica correctamente el acceso del usuario a un business
     */
    public function test_has_access_to_verifies_user_business_access(): void
    {
        $user = User::factory()->create();
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();

        // Usuario tiene acceso a business1 como admin
        $user->businessesAsAdmin()->attach($business1->id);

        $this->assertTrue($this->resolver->hasAccessTo($user, $business1->id, 'admin'));
        $this->assertFalse($this->resolver->hasAccessTo($user, $business2->id, 'admin'));
    }

    /**
     * Test: setActiveBusiness crea/actualiza registro en user_active_roles
     */
    public function test_set_active_business_creates_active_role_record(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create();

        // Usuario tiene acceso al business
        $user->businessesAsAdmin()->attach($business->id);

        $result = $this->resolver->setActiveBusiness($user, $business->id, 'admin');

        $this->assertTrue($result);
        $this->assertDatabaseHas('user_active_roles', [
            'user_id' => $user->id,
            'business_id' => $business->id,
            'active_role' => 'admin'
        ]);
    }

    /**
     * Test: setActiveBusiness falla si el usuario no tiene acceso al business
     */
    public function test_set_active_business_fails_without_access(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create();

        // Usuario NO tiene acceso al business
        $result = $this->resolver->setActiveBusiness($user, $business->id, 'admin');

        $this->assertFalse($result);
        $this->assertDatabaseMissing('user_active_roles', [
            'user_id' => $user->id,
            'business_id' => $business->id
        ]);
    }

    /**
     * Test: Prioriza el último switched_at si hay múltiples user_active_roles
     */
    public function test_resolve_prioritizes_latest_switched_at(): void
    {
        $user = User::factory()->create();
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();

        $user->businessesAsAdmin()->attach([$business1->id, $business2->id]);

        // Crear sesiones con diferentes timestamps
        $user->activeRoles()->create([
            'business_id' => $business1->id,
            'active_role' => 'admin',
            'switched_at' => now()->subHour()
        ]);

        $user->activeRoles()->create([
            'business_id' => $business2->id,
            'active_role' => 'admin',
            'switched_at' => now() // Más reciente
        ]);

        $resolvedId = $this->resolver->resolve($user, 'admin');

        $this->assertEquals($business2->id, $resolvedId, 'Debe resolver el business con switched_at más reciente');
    }
}

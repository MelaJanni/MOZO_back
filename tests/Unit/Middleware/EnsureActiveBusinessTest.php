<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureActiveBusiness;
use App\Models\Business;
use App\Models\User;
use App\Services\BusinessResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery;
use Tests\TestCase;

/**
 * Tests para EnsureActiveBusiness Middleware
 *
 * @package Tests\Unit\Middleware
 */
class EnsureActiveBusinessTest extends TestCase
{
    use RefreshDatabase;

    protected EnsureActiveBusiness $middleware;
    protected BusinessResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(BusinessResolver::class);
        $this->middleware = new EnsureActiveBusiness($this->resolver);
    }

    /**
     * Test: Inyecta business_id en el request cuando se resuelve correctamente
     */
    public function test_injects_business_id_into_request(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create();

        // Usuario tiene acceso al business
        $user->businessesAsAdmin()->attach($business->id);
        $user->activeRoles()->create([
            'business_id' => $business->id,
            'active_role' => 'admin',
            'switched_at' => now()
        ]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $response = $this->middleware->handle(
            $request,
            function ($req) use ($business) {
                // Verificar que business_id está inyectado
                $this->assertEquals($business->id, $req->business_id);
                $this->assertEquals($business->id, $req->attributes->get('business_id'));
                $this->assertEquals('admin', $req->attributes->get('business_role'));
                return new Response('OK', 200);
            },
            'admin'
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test: Retorna 403 si no se encuentra business_id y es requerido
     */
    public function test_returns_403_when_business_not_found_and_required(): void
    {
        $user = User::factory()->create(); // Usuario sin business

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $response = $this->middleware->handle(
            $request,
            function () {
                return new Response('Should not reach here', 200);
            },
            'admin',
            'true' // require=true
        );

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('NO_ACTIVE_BUSINESS', $data['code']);
        $this->assertTrue($data['requires_business_setup']);
    }

    /**
     * Test: Continúa sin inyectar business_id si no es requerido (require=false)
     */
    public function test_continues_without_business_id_when_not_required(): void
    {
        $user = User::factory()->create(); // Usuario sin business

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $response = $this->middleware->handle(
            $request,
            function ($req) {
                $this->assertNull($req->business_id);
                return new Response('OK', 200);
            },
            null,
            'false' // require=false
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test: Continúa sin business_id si no hay usuario autenticado
     */
    public function test_continues_without_authenticated_user(): void
    {
        $request = Request::create('/test', 'GET');
        // Sin usuario autenticado

        $response = $this->middleware->handle(
            $request,
            function ($req) {
                $this->assertNull($req->business_id);
                return new Response('OK', 200);
            },
            'admin'
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test: Respeta el rol especificado en el middleware
     */
    public function test_respects_role_parameter(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->create();

        // Usuario es WAITER (no admin)
        $user->businessesAsWaiter()->attach($business->id);
        $user->activeRoles()->create([
            'business_id' => $business->id,
            'active_role' => 'waiter',
            'switched_at' => now()
        ]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        // Middleware con rol 'waiter'
        $response = $this->middleware->handle(
            $request,
            function ($req) use ($business) {
                $this->assertEquals($business->id, $req->business_id);
                $this->assertEquals('waiter', $req->attributes->get('business_role'));
                return new Response('OK', 200);
            },
            'waiter'
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test: Maneja RuntimeException del resolver correctamente
     */
    public function test_handles_resolver_runtime_exception(): void
    {
        $user = User::factory()->create();

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);

        // Mock del resolver para forzar excepción
        $mockResolver = Mockery::mock(BusinessResolver::class);
        $mockResolver->shouldReceive('resolve')
            ->andThrow(new \RuntimeException('Test exception'));

        $middleware = new EnsureActiveBusiness($mockResolver);

        $response = $middleware->handle(
            $request,
            function () {
                return new Response('Should not reach here', 200);
            },
            'admin',
            'true'
        );

        $this->assertEquals(500, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('BUSINESS_RESOLUTION_ERROR', $data['code']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

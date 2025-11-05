<?php

namespace Tests\Feature\Smoke;

use App\Models\Business;
use App\Models\StaffRequest;
use App\Models\User;
use App\Models\WaiterProfile;
use App\Services\BusinessResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke Tests: Staff Management Endpoints
 * 
 * Verifica funcionalidad de gestión de personal (solicitudes, aprobaciones, rechazos)
 * 
 * ⚠️ NO MODIFICAR - Baseline de comportamiento
 */
class StaffEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $waiter;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear negocio
        $this->business = Business::factory()->create([
            'name' => 'Test Restaurant',
            'invitation_code' => 'INVITE123'
        ]);

        // Crear admin
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com'
        ]);

        $this->admin->adminProfile()->create([
            'business_id' => $this->business->id,
            'phone' => '1234567890',
            'address' => 'Admin Address'
        ]);

        // Crear mozo que solicitará unirse
        $this->waiter = User::factory()->create([
            'email' => 'waiter@test.com'
        ]);

        // Configurar business_id activo para evitar 403 del middleware
        $this->admin->activeRoles()->create([
            'business_id' => $this->business->id,
            'active_role' => 'admin',
            'switched_at' => now()
        ]);
        
        $this->waiter->activeRoles()->create([
            'business_id' => $this->business->id,
            'active_role' => 'waiter',
            'switched_at' => now()
        ]);
    }

    /** @test */
    public function test_create_staff_request_sends_notification()
    {
        $response = $this->actingAs($this->waiter)
            ->postJson('/api/waiter/join-business', [
                'invitation_code' => $this->business->invitation_code
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $this->assertDatabaseHas('staff_requests', [
            'user_id' => $this->waiter->id,
            'business_id' => $this->business->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function test_approve_staff_updates_status()
    {
        $request = StaffRequest::create([
            'user_id' => $this->waiter->id,
            'business_id' => $this->business->id,
            'name' => $this->waiter->name,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/staff/request/{$request->id}", [
                'action' => 'approve'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $request->refresh();
        $this->assertEquals('approved', $request->status);

        // Verificar que se creó el perfil de mozo
        $this->assertDatabaseHas('waiter_profiles', [
            'user_id' => $this->waiter->id,
            'business_id' => $this->business->id
        ]);
    }

    /** @test */
    public function test_reject_staff_sends_notification()
    {
        $request = StaffRequest::create([
            'user_id' => $this->waiter->id,
            'business_id' => $this->business->id,
            'name' => $this->waiter->name,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/staff/request/{$request->id}", [
                'action' => 'reject',
                'reason' => 'Not hiring at the moment'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $request->refresh();
        $this->assertEquals('rejected', $request->status);

        // No debe existir perfil de mozo
        $this->assertDatabaseMissing('waiter_profiles', [
            'user_id' => $this->waiter->id,
            'business_id' => $this->business->id
        ]);
    }

    /** @test */
    public function test_my_requests_filters_by_status()
    {
        // Crear solicitudes con diferentes estados
        StaffRequest::create([
            'user_id' => $this->waiter->id,
            'business_id' => $this->business->id,
            'name' => $this->waiter->name,
            'status' => 'pending'
        ]);

        StaffRequest::create([
            'user_id' => $this->waiter->id,
            'business_id' => Business::factory()->create()->id,
            'name' => $this->waiter->name,
            'status' => 'approved'
        ]);

        StaffRequest::create([
            'user_id' => $this->waiter->id,
            'business_id' => Business::factory()->create()->id,
            'name' => $this->waiter->name,
            'status' => 'rejected'
        ]);

        // Obtener solicitudes pendientes
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/staff/requests?status=pending');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'requests' => [
                    '*' => [
                        'id',
                        'user_id',
                        'business_id',
                        'status'
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_get_business_staff_returns_all()
    {
        // Crear 3 mozos activos
        for ($i = 0; $i < 3; $i++) {
            $waiter = User::factory()->create();
            WaiterProfile::create([
                'user_id' => $waiter->id,
                'business_id' => $this->business->id,
                'active_business_id' => $this->business->id,
                'phone' => "987654321{$i}",
                'status' => 'active'
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/staff');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'staff' => [
                    '*' => [
                        'user_id',
                        'business_id',
                        'status'
                    ]
                ]
            ]);

        $this->assertCount(3, $response->json('staff'));
    }

    /** @test */
    public function test_remove_staff_cleans_firebase()
    {
        // Crear mozo
        $waiter = User::factory()->create();
        $profile = WaiterProfile::create([
            'user_id' => $waiter->id,
            'business_id' => $this->business->id,
            'active_business_id' => $this->business->id,
            'phone' => '9876543210',
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/staff/{$waiter->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        // Verificar que el perfil fue marcado como inactivo o eliminado
        $profile->refresh();
        $this->assertTrue(in_array($profile->status, ['inactive', 'removed']));
    }

    /** @test */
    public function test_invalid_invitation_code_rejects_request()
    {
        $response = $this->actingAs($this->waiter)
            ->postJson('/api/waiter/join-business', [
                'invitation_code' => 'INVALID_CODE'
            ]);

        $response->assertStatus(404);

        $this->assertDatabaseMissing('staff', [
            'user_id' => $this->waiter->id
        ]);
    }
}

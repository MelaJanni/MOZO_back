<?php

namespace Tests\Feature\Smoke;

use App\Models\Business;
use App\Models\Table;
use App\Models\User;
use App\Models\WaiterProfile;
use App\Services\BusinessResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke Tests: Admin Endpoints
 * 
 * Verifica funcionalidad crítica de administración (negocios, configuración, etc)
 * 
 * ⚠️ NO MODIFICAR - Baseline de comportamiento
 */
class AdminEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear negocio
        $this->business = Business::factory()->create([
            'name' => 'Test Restaurant',
            'invitation_code' => 'ADMIN123'
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

        // Configurar business_id activo para evitar 403 del middleware
        $this->admin->activeRoles()->create([
            'business_id' => $this->business->id,
            'active_role' => 'admin',
            'switched_at' => now()
        ]);
    }

    /** @test */
    public function test_create_business_returns_valid_structure()
    {
        $this->markTestSkipped('Endpoint /admin/business/create requiere refactor - debe estar fuera del middleware business:admin');
        
        $newAdmin = User::factory()->create([
            'email' => 'newadmin@test.com'
        ]);

        $response = $this->actingAs($newAdmin)
            ->postJson('/api/admin/business/create', [
                'name' => 'New Restaurant',
                'address' => '123 Main St',
                'phone' => '5555555555',
                'cuisine_type' => 'Italian'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'business' => [
                    'id',
                    'name',
                    'invitation_code'
                ]
            ]);

        $this->assertDatabaseHas('businesses', [
            'name' => 'New Restaurant'
        ]);
    }

    /** @test */
    public function test_get_business_info_returns_complete_data()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/business');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'business' => [
                    'id',
                    'name',
                    'invitation_code'
                ]
            ]);

        $this->assertEquals($this->business->id, $response->json('business.id'));
    }

    /** @test */
    public function test_update_business_settings()
    {
        $response = $this->actingAs($this->admin)
            ->putJson('/api/admin/settings', [
                'auto_acknowledge' => true,
                'notification_sound' => 'bell',
                'max_pending_calls' => 10
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $this->business->refresh();
        $settings = $this->business->settings ?? [];
        $this->assertTrue($settings['auto_acknowledge'] ?? false);
    }

    /** @test */
    public function test_regenerate_invitation_code()
    {
        $oldCode = $this->business->invitation_code;

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/business/regenerate-invitation-code');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'invitation_code'
            ]);

        $this->business->refresh();
        $this->assertNotEquals($oldCode, $this->business->invitation_code);
    }

    /** @test */
    public function test_get_tables_list()
    {
        // Crear mesas
        Table::factory()->count(5)->create([
            'business_id' => $this->business->id
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/tables');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'tables' => [
                    '*' => [
                        'id',
                        'number',
                        'business_id'
                    ]
                ]
            ]);

        $this->assertCount(5, $response->json('tables'));
    }

    /** @test */
    public function test_delete_business_removes_related_data()
    {
        // Crear datos relacionados
        $table = Table::factory()->create([
            'business_id' => $this->business->id
        ]);

        $waiter = User::factory()->create();
        WaiterProfile::create([
            'user_id' => $waiter->id,
            'business_id' => $this->business->id,
            'active_business_id' => $this->business->id,
            'phone' => '9876543210',
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/business/{$this->business->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        // Verificar eliminación (soft delete o hard delete según implementación)
        $this->assertDatabaseMissing('businesses', [
            'id' => $this->business->id,
            'deleted_at' => null
        ]);
    }

    /** @test */
    public function test_admin_cannot_access_other_business_data()
    {
        // Crear otro negocio
        $otherBusiness = Business::factory()->create([
            'name' => 'Other Restaurant'
        ]);

        $otherAdmin = User::factory()->create();
        $otherAdmin->adminProfile()->create([
            'business_id' => $otherBusiness->id,
            'phone' => '1111111111',
            'address' => 'Other Address'
        ]);

        // Intentar acceder al staff del otro negocio
        $waiter = User::factory()->create();
        WaiterProfile::create([
            'user_id' => $waiter->id,
            'business_id' => $otherBusiness->id,
            'active_business_id' => $otherBusiness->id,
            'phone' => '2222222222',
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/staff');

        $response->assertStatus(200);

        // No debe incluir al staff del otro negocio
        $staff = $response->json('staff');
        $otherBusinessStaff = collect($staff)->filter(function ($s) use ($otherBusiness) {
            return $s['business_id'] === $otherBusiness->id;
        });

        $this->assertCount(0, $otherBusinessStaff);
    }
}

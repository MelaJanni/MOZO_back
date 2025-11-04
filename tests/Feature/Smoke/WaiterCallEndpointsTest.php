<?php

namespace Tests\Feature\Smoke;

use App\Models\Business;
use App\Models\Table;
use App\Models\User;
use App\Models\WaiterCall;
use App\Models\WaiterProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke Tests: WaiterCall Endpoints
 * 
 * Estos tests verifican que los endpoints críticos de llamadas de mozo
 * funcionen correctamente ANTES de cualquier refactorización.
 * 
 * ⚠️ NO MODIFICAR - Estos tests establecen el baseline de comportamiento
 */
class WaiterCallEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $waiter;
    protected User $admin;
    protected Business $business;
    protected Table $table;
    protected WaiterProfile $waiterProfile;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear negocio
        $this->business = Business::factory()->create([
            'name' => 'Test Restaurant',
            'invitation_code' => 'TEST123'
        ]);

        // Crear admin
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com'
        ]);

        // Asignar rol admin
        $this->admin->adminProfile()->create([
            'business_id' => $this->business->id,
            'phone' => '1234567890',
            'address' => 'Test Address'
        ]);

        // Crear mozo
        $this->waiter = User::factory()->create([
            'email' => 'waiter@test.com'
        ]);

        // Crear perfil de mozo
        $this->waiterProfile = WaiterProfile::create([
            'user_id' => $this->waiter->id,
            'business_id' => $this->business->id,
            'active_business_id' => $this->business->id,
            'phone' => '9876543210',
            'status' => 'active'
        ]);

        // Crear mesa
        $this->table = Table::factory()->create([
            'business_id' => $this->business->id,
            'number' => 1,
            'active_waiter_id' => $this->waiter->id,
            'status' => 'available'
        ]);
    }

    /** @test */
    public function test_call_waiter_creates_notification()
    {
        $response = $this->postJson("/api/qr/table/{$this->table->id}/call", [
            'message' => 'Necesito la cuenta'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'call' => [
                    'id',
                    'table_id',
                    'waiter_id',
                    'status',
                    'message'
                ]
            ]);

        $this->assertDatabaseHas('waiter_calls', [
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function test_acknowledge_call_updates_status()
    {
        $call = WaiterCall::create([
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'status' => 'pending',
            'message' => 'Test call',
            'called_at' => now()
        ]);

        $response = $this->actingAs($this->waiter)
            ->postJson("/api/waiter/calls/{$call->id}/acknowledge");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $call->refresh();
        $this->assertEquals('acknowledged', $call->status);
        $this->assertNotNull($call->acknowledged_at);
    }

    /** @test */
    public function test_complete_call_updates_status()
    {
        $call = WaiterCall::create([
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'status' => 'acknowledged',
            'message' => 'Test call',
            'called_at' => now(),
            'acknowledged_at' => now()
        ]);

        $response = $this->actingAs($this->waiter)
            ->postJson("/api/waiter/calls/{$call->id}/complete");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $call->refresh();
        $this->assertEquals('completed', $call->status);
        $this->assertNotNull($call->completed_at);
    }

    /** @test */
    public function test_pending_calls_returns_correct_format()
    {
        // Crear 2 llamadas pendientes
        WaiterCall::create([
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'status' => 'pending',
            'message' => 'Call 1',
            'called_at' => now()->subMinutes(5)
        ]);

        WaiterCall::create([
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
            'status' => 'pending',
            'message' => 'Call 2',
            'called_at' => now()
        ]);

        $response = $this->actingAs($this->waiter)
            ->getJson('/api/waiter/calls/pending');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'calls' => [
                    '*' => [
                        'id',
                        'table_id',
                        'status',
                        'message',
                        'called_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_call_history_pagination()
    {
        // Crear 15 llamadas históricas
        for ($i = 0; $i < 15; $i++) {
            WaiterCall::create([
                'table_id' => $this->table->id,
                'waiter_id' => $this->waiter->id,
                'status' => 'completed',
                'message' => "Call {$i}",
                'called_at' => now()->subHours($i),
                'completed_at' => now()->subHours($i)->addMinutes(5)
            ]);
        }

        $response = $this->actingAs($this->waiter)
            ->getJson('/api/waiter/calls/history?page=1&per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'calls' => [
                    'data',
                    'current_page',
                    'total',
                    'per_page'
                ]
            ]);

        $this->assertCount(10, $response->json('calls.data'));
    }

    /** @test */
    public function test_blocked_ip_cannot_call_waiter()
    {
        // Simular bloqueo de IP
        \DB::table('blocked_ips')->insert([
            'ip_address' => '127.0.0.1',
            'business_id' => $this->business->id,
            'blocked_by' => $this->waiter->id,
            'blocked_at' => now(),
            'reason' => 'Test block'
        ]);

        $response = $this->postJson("/api/qr/table/{$this->table->id}/call", [
            'message' => 'Test call from blocked IP'
        ]);

        // Debería rechazar la llamada
        $response->assertStatus(403);
    }
}

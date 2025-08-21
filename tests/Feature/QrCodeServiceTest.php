<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Table;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_qr_for_table_idempotent()
    {
        $business = Business::factory()->create();
        $table = Table::create([
            'name' => 'Mesa 1',
            'number' => 1,
            'business_id' => $business->id,
            'notifications_enabled' => true,
        ]);
        $service = app(QrCodeService::class);

        $first = $service->generateForTable($table);
        $second = $service->generateForTable($table->fresh());

        $this->assertEquals($first->id, $second->id, 'Debe reutilizar el mismo registro QR');
        $this->assertEquals($first->code, $second->code);
        $this->assertEquals($first->url, $second->url);
    }

    public function test_admin_qr_generate_and_preview_endpoints()
    {
    $user = User::factory()->create();
    $business = Business::factory()->create();
    $business->addAdmin($user); // establece pivot con is_primary
    // Controlador QR usa user->business_id (legacy). Simulamos asignación temporal.
    $user->business_id = $business->id; // atributo dinámico (no se persiste porque no existe la columna)
        $table = Table::create([
            'name' => 'Mesa 5',
            'number' => 5,
            'business_id' => $business->id,
            'notifications_enabled' => true,
        ]);

        $this->actingAs($user);
        $resp = $this->postJson('/api/admin/qr/generate/'.$table->id);
        $resp->assertStatus(200)->assertJsonStructure(['message','qr_code'=>['id','code','url']]);

        $preview = $this->get('/api/admin/qr/preview/'.$table->id);
        $preview->assertStatus(200);
        $this->assertStringContainsString('<svg', $preview->getContent());
    }
}

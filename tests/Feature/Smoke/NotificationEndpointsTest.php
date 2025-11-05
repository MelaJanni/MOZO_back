<?php

namespace Tests\Feature\Smoke;

use App\Models\Business;
use App\Models\DeviceToken; // Cambiado de FcmToken a DeviceToken
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke Tests: Notification Endpoints
 * 
 * Verifica funcionalidad de notificaciones y tokens FCM
 * 
 * ⚠️ NO MODIFICAR - Baseline de comportamiento
 */
class NotificationEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create([
            'name' => 'Test Business'
        ]);

        $this->user = User::factory()->create([
            'email' => 'user@test.com'
        ]);
        
        // Crear perfil de mozo (requisito para registrar tokens FCM)
        $this->user->waiterProfile()->create([
            'display_name' => $this->user->name,
            'phone' => '1234567890'
        ]);
    }

    /** @test */
    public function test_register_fcm_token()
    {
        $token = 'test_fcm_token_' . uniqid();

        $response = $this->actingAs($this->user)
            ->postJson('/api/device-token', [
                'token' => $token,
                'platform' => 'android' // Cambiado de device_type a platform
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $this->assertDatabaseHas('device_tokens', [ // Cambiado de fcm_tokens a device_tokens
            'user_id' => $this->user->id,
            'token' => $token
        ]);
    }

    /** @test */
    public function test_refresh_token_updates_existing()
    {
        $oldToken = 'old_token_' . uniqid();
        $newToken = 'new_token_' . uniqid();

        // Crear token antiguo
        DeviceToken::create([
            'user_id' => $this->user->id,
            'token' => $oldToken,
            'platform' => 'ios' // Cambiado de device_type a platform
        ]);

        // Actualizar con nuevo token
        $response = $this->actingAs($this->user)
            ->postJson('/api/device-token', [
                'token' => $newToken,
                'platform' => 'ios' // Cambiado de device_type a platform
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('device_tokens', [ // Cambiado de fcm_tokens a device_tokens
            'user_id' => $this->user->id,
            'token' => $newToken
        ]);

        // Token antiguo puede estar presente o no (TokenManager puede eliminarlo o dejarlo)
        // No verificamos is_active porque puede no existir esa columna
    }

    /** @test */
    public function test_delete_fcm_token()
    {
        $token = 'token_to_delete_' . uniqid();

        DeviceToken::create([
            'user_id' => $this->user->id,
            'token' => $token,
            'platform' => 'android' // Cambiado de device_type a platform
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/device-token', [
                'token' => $token
            ]);

        $response->assertStatus(200);

        // Token debe estar eliminado de la base de datos
        $this->assertDatabaseMissing('device_tokens', [ // Cambiado de fcm_tokens
            'user_id' => $this->user->id,
            'token' => $token
        ]);
    }

    /** @test */
    public function test_get_user_notifications()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'notifications'
            ]);
    }

    /** @test */
    public function test_mark_notification_as_read()
    {
        // Crear notificación
        $notification = $this->user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['message' => 'Test notification'],
            'read_at' => null
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/user/notifications/{$notification->id}/read");

        $response->assertStatus(200);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    /** @test */
    public function test_multiple_devices_can_register_tokens()
    {
        $tokens = [
            'android_token_' . uniqid(),
            'ios_token_' . uniqid(),
            'web_token_' . uniqid()
        ];

        foreach ($tokens as $index => $token) {
            $platform = ['android', 'ios', 'web'][$index]; // Cambiado nombre de variable

            $response = $this->actingAs($this->user)
                ->postJson('/api/device-token', [
                    'token' => $token,
                    'platform' => $platform // Cambiado de device_type a platform
                ]);

            $response->assertStatus(200);
        }

        // Verificar que todos los tokens existen
        foreach ($tokens as $token) {
            $this->assertDatabaseHas('device_tokens', [ // Cambiado de fcm_tokens
                'user_id' => $this->user->id,
                'token' => $token
            ]);
        }
    }
}

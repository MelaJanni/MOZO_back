<?php

namespace Tests\Feature\Smoke;

use App\Models\Business;
use App\Models\FcmToken;
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
    }

    /** @test */
    public function test_register_fcm_token()
    {
        $token = 'test_fcm_token_' . uniqid();

        $response = $this->actingAs($this->user)
            ->postJson('/api/device-token', [
                'token' => $token,
                'device_type' => 'android'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $this->user->id,
            'token' => $token,
            'device_type' => 'android'
        ]);
    }

    /** @test */
    public function test_refresh_token_updates_existing()
    {
        $oldToken = 'old_token_' . uniqid();
        $newToken = 'new_token_' . uniqid();

        // Crear token antiguo
        FcmToken::create([
            'user_id' => $this->user->id,
            'token' => $oldToken,
            'device_type' => 'ios'
        ]);

        // Actualizar con nuevo token
        $response = $this->actingAs($this->user)
            ->postJson('/api/device-token', [
                'token' => $newToken,
                'device_type' => 'ios'
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $this->user->id,
            'token' => $newToken,
            'device_type' => 'ios'
        ]);

        // Token antiguo debe estar inactivo o eliminado
        $oldTokenRecord = FcmToken::where('token', $oldToken)->first();
        $this->assertTrue(
            !$oldTokenRecord || 
            $oldTokenRecord->is_active === false
        );
    }

    /** @test */
    public function test_delete_fcm_token()
    {
        $token = 'token_to_delete_' . uniqid();

        FcmToken::create([
            'user_id' => $this->user->id,
            'token' => $token,
            'device_type' => 'android'
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/device-token', [
                'token' => $token
            ]);

        $response->assertStatus(200);

        // Token debe estar marcado como inactivo o eliminado
        $tokenRecord = FcmToken::where('token', $token)->first();
        $this->assertTrue(
            !$tokenRecord || 
            $tokenRecord->is_active === false
        );
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
            $deviceType = ['android', 'ios', 'web'][$index];

            $response = $this->actingAs($this->user)
                ->postJson('/api/device-token', [
                    'token' => $token,
                    'device_type' => $deviceType
                ]);

            $response->assertStatus(200);
        }

        // Verificar que todos los tokens existen
        foreach ($tokens as $token) {
            $this->assertDatabaseHas('fcm_tokens', [
                'user_id' => $this->user->id,
                'token' => $token
            ]);
        }
    }
}

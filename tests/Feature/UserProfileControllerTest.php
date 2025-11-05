<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WaiterProfile;
use App\Models\AdminProfile;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->business = Business::factory()->create();
    }

    /** @test */
    public function it_returns_waiter_profile_with_membership_data(): void
    {
        // Crear un plan y suscripción
        $plan = Plan::factory()->create([
            'name' => 'Premium Plan',
            'billing_period' => 'monthly',
            'prices' => ['ARS' => 2999, 'USD' => 29.99],
            'default_currency' => 'USD',
            'features' => ['feature1', 'feature2'],
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_end' => now()->addDays(15),
            'auto_renew' => true,
        ]);

        // Crear perfil de mozo
        $waiterProfile = WaiterProfile::factory()->create([
            'user_id' => $this->user->id,
            'display_name' => 'Test Waiter',
            'bio' => 'Test bio',
            'phone' => '123456789',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user-profile/active');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'type',
                    'user' => ['id', 'name', 'email', 'google_id', 'google_avatar'],
                    'profile_data',
                    'membership' => [
                        'has_active_membership',
                        'membership_expired',
                        'days_remaining',
                        'is_lifetime_paid',
                        'auto_renew',
                        'expires_at',
                        'time_remaining',
                        'current_plan',
                        'status'
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'type' => 'waiter',
                    'user' => [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                        'email' => $this->user->email,
                    ],
                    'membership' => [
                        'has_active_membership' => true,
                        'membership_expired' => false,
                        'is_lifetime_paid' => false,
                        'auto_renew' => true,
                        'status' => 'active'
                    ]
                ]
            ]);

        // Verificar que los datos del plan están incluidos
        $this->assertEquals('Premium Plan', $response->json('data.membership.current_plan.name'));
        $this->assertArrayHasKey('prices', $response->json('data.membership.current_plan'));
    }

    /** @test */
    public function it_returns_admin_profile_with_membership_data(): void
    {
        // Crear perfil de admin
        $adminProfile = AdminProfile::factory()->create([
            'user_id' => $this->user->id,
            'display_name' => 'Test Admin',
            'position' => 'Manager',
            'corporate_email' => 'admin@test.com',
        ]);

        // Hacer que el usuario sea admin del negocio
        $this->business->addAdmin($this->user, 'owner');

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user-profile/active?business_id=' . $this->business->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'type' => 'admin',
                    'user' => [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                        'email' => $this->user->email,
                    ],
                    'membership' => [
                        'has_active_membership' => false,
                        'membership_expired' => true,
                        'is_lifetime_paid' => false,
                        'auto_renew' => false,
                        'status' => 'free'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_returns_lifetime_paid_membership_data(): void
    {
        // Configurar usuario como lifetime paid
        $this->user->update(['is_lifetime_paid' => true]);

        $waiterProfile = WaiterProfile::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user-profile/active');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'membership' => [
                        'has_active_membership' => true,
                        'membership_expired' => false,
                        'is_lifetime_paid' => true,
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_returns_expired_membership_data(): void
    {
        // Crear suscripción expirada
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()->expired()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'current_period_end' => now()->subDays(5), // Expirada hace 5 días
            'auto_renew' => false,
        ]);

        $waiterProfile = WaiterProfile::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user-profile/active');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'membership' => [
                        'has_active_membership' => false,
                        'membership_expired' => true,
                        'auto_renew' => false,
                        'status' => 'free'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_handles_user_without_profile(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user-profile/active');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => null,
                'message' => 'No hay perfil configurado'
            ]);
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        $response = $this->getJson('/api/user-profile/active');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_respects_business_id_parameter(): void
    {
        // Crear dos perfiles para el usuario
        $waiterProfile = WaiterProfile::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $adminProfile = AdminProfile::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Hacer que el usuario sea admin de un negocio específico
        $this->business->addAdmin($this->user, 'owner');

        Sanctum::actingAs($this->user);

        // Sin business_id debería devolver perfil por defecto (waiter)
        $response = $this->getJson('/api/user-profile/active');
        $response->assertStatus(200)
            ->assertJson(['data' => ['type' => 'waiter']]);

        // Con business_id debería devolver perfil de admin
        $response = $this->getJson('/api/user-profile/active?business_id=' . $this->business->id);
        $response->assertStatus(200)
            ->assertJson(['data' => ['type' => 'admin']]);
    }

    /** @test */
    public function it_includes_plan_features_and_limits(): void
    {
        $plan = Plan::factory()->create([
            'features' => [
                'included' => ['chat', 'analytics', 'premium_support'],
                'max_businesses' => 5,
                'max_tables' => 50
            ],
        ]);

        $subscription = Subscription::factory()->active()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'current_period_end' => now()->addDays(30),
        ]);

        $waiterProfile = WaiterProfile::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user-profile/active');

        $response->assertStatus(200);

        $membershipData = $response->json('data.membership');
        $currentPlan = $response->json('data.membership.current_plan');

        $this->assertNotNull($currentPlan);
        $this->assertArrayHasKey('features', $currentPlan);
        $this->assertIsArray($currentPlan['features']);
    }

    /** @test */
    public function it_handles_subscription_without_plan(): void
    {
        // Crear suscripción sin plan (caso edge)
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => null,
            'current_period_end' => now()->addDays(30),
        ]);

        $waiterProfile = WaiterProfile::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user-profile/active');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'membership' => [
                        'current_plan' => null,
                    ]
                ]
            ]);
    }
}
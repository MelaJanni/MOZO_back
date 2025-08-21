<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use App\Models\UserActiveRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveRoleDoesNotAffectPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_active_role_does_not_grant_missing_pivot(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create();

        // Solo se agrega como waiter
        $business->addWaiter($user);
        $this->assertTrue($user->isWaiter($business->id));
        $this->assertFalse($user->isAdmin($business->id));

        // Forzar active_role='admin' sin pivot admin
        UserActiveRole::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'active_role' => 'admin',
        ]);

        // Releer estado
        $this->assertEquals('admin', $user->getActiveRole($business->id));
        // Permisos reales NO cambian
        $this->assertFalse($user->isAdmin($business->id));
        $this->assertTrue($user->isWaiter($business->id));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_accepts_one_admin_only(): void
    {
        $business = Business::factory()->create();
        $admin1 = User::factory()->create();
        $admin2 = User::factory()->create();

        $business->addAdmin($admin1, 'owner');
        $this->assertTrue($business->isAdministratedBy($admin1));

        // Intentar agregar segundo admin sin reemplazo
        $result = $business->addAdmin($admin2, 'manager', [], false);
        $this->assertFalse($result, 'No debe permitir segundo admin sin reemplazo');
        $this->assertTrue($business->isAdministratedBy($admin1));

        // Reemplazar admin existente
        $resultReplace = $business->addAdmin($admin2, 'manager', [], true);
        $this->assertTrue($resultReplace);
        $this->assertTrue($business->isAdministratedBy($admin2));
        $this->assertFalse($business->isAdministratedBy($admin1));
    }
}

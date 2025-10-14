<?php

namespace Tests\Unit;

use App\Models\Business;
use App\Models\Staff;
use App\Models\User;
use App\Services\StaffWaiterSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffWaiterSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private StaffWaiterSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(StaffWaiterSyncService::class);
    }

    public function test_sync_creates_active_pivot_for_confirmed_staff(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create();

        $staff = Staff::factory()->for($business)->create([
            'user_id' => $user->id,
            'status' => 'confirmed',
            'employment_type' => 'tiempo completo',
            'current_schedule' => '9-18h',
            'hire_date' => now()->subDays(10),
        ]);

        $this->service->sync($staff->fresh());

        $this->assertDatabaseHas('business_waiters', [
            'business_id' => $business->id,
            'user_id' => $user->id,
            'employment_status' => 'active',
        ]);
    }

    public function test_sync_does_not_create_pivot_when_staff_not_confirmed(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create();

        $staff = Staff::factory()->for($business)->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->service->sync($staff->fresh());

        $this->assertDatabaseMissing('business_waiters', [
            'business_id' => $business->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_sync_updates_existing_pivot_to_inactive_when_status_changes(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create();

        $staff = Staff::factory()->for($business)->create([
            'user_id' => $user->id,
            'status' => 'confirmed',
        ]);

        $this->service->sync($staff->fresh());

        $this->assertDatabaseHas('business_waiters', [
            'business_id' => $business->id,
            'user_id' => $user->id,
            'employment_status' => 'active',
        ]);

        $staff->status = 'rejected';
        $staff->save();

        $this->service->sync($staff->fresh());

        $this->assertDatabaseHas('business_waiters', [
            'business_id' => $business->id,
            'user_id' => $user->id,
            'employment_status' => 'inactive',
        ]);
    }
}

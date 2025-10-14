<?php

namespace App\Observers;

use App\Models\Staff;
use App\Services\StaffWaiterSyncService;

class StaffObserver
{
    public function __construct(private readonly StaffWaiterSyncService $syncService)
    {
    }

    public function saved(Staff $staff): void
    {
        $this->syncService->sync($staff);
    }

    public function deleted(Staff $staff): void
    {
        $this->syncService->markInactive($staff);
    }
}

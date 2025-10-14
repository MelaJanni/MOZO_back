<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StaffWaiterSyncService
{
    public function sync(Staff $staff): void
    {
        if (!$this->canSync($staff)) {
            return;
        }

        $business = $staff->business ?? Business::find($staff->business_id);
        if (!$business) {
            return;
        }

        $basePayload = [
            'employment_type' => $this->mapEmploymentType($staff->employment_type),
            'hourly_rate' => $staff->salary,
            'work_schedule' => $this->normalizeSchedule($staff->current_schedule),
        ];

        if ($staff->status === 'confirmed') {
            $payload = $basePayload + [
                'employment_status' => 'active',
                'hired_at' => $staff->hire_date ?? now(),
            ];
            $business->waiters()->syncWithoutDetaching([
                $staff->user_id => $payload,
            ]);
            return;
        }

        $payload = $basePayload + ['employment_status' => 'inactive'];
        $business->waiters()->updateExistingPivot($staff->user_id, $payload, false);
    }

    public function markInactive(Staff $staff): void
    {
        if (!$this->canSync($staff)) {
            return;
        }

        DB::table('business_waiters')
            ->where('business_id', $staff->business_id)
            ->where('user_id', $staff->user_id)
            ->update([
                'employment_status' => 'inactive',
                'updated_at' => now(),
            ]);
    }

    private function canSync(Staff $staff): bool
    {
        if (!Schema::hasTable('business_waiters')) {
            return false;
        }

        if (!$staff->business_id || !$staff->user_id) {
            return false;
        }

        return true;
    }

    private function mapEmploymentType(?string $employmentType): string
    {
        return match (Str::lower((string) $employmentType)) {
            'tiempo completo', 'full_time', 'fulltime' => 'tiempo completo',
            'tiempo parcial', 'part_time', 'parttime' => 'tiempo parcial',
            'solo fines de semana', 'weekend', 'weekends', 'fin de semana' => 'solo fines de semana',
            'por horas', 'hourly', 'casual' => 'por horas',
            default => 'tiempo completo',
        };
    }

    private function normalizeSchedule(null|string|array $schedule): ?string
    {
        if (is_array($schedule)) {
            return json_encode($schedule);
        }

        $trimmed = trim((string) $schedule);

        return $trimmed === '' ? null : json_encode(['notes' => $trimmed]);
    }
}

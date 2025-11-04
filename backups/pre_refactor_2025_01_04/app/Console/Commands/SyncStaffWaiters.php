<?php

namespace App\Console\Commands;

use App\Models\Staff;
use App\Services\StaffWaiterSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncStaffWaiters extends Command
{
    protected $signature = 'mozo:sync-staff-waiters {--business= : ID de negocio para filtrar}';
    protected $description = 'Sincroniza registros de staff confirmados con el pivote business_waiters';

    public function __construct(private readonly StaffWaiterSyncService $syncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!Schema::hasTable('business_waiters')) {
            $this->error('La tabla business_waiters no existe. Ejecutá las migraciones primero.');
            return self::FAILURE;
        }

        $businessId = $this->option('business');
        $query = Staff::query()
            ->whereNotNull('user_id')
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId));

        $total = 0;
        $processed = 0;

        $query->chunkById(100, function ($staffChunk) use (&$total, &$processed) {
            foreach ($staffChunk as $staff) {
                $total++;
                $this->syncService->sync($staff);
                $processed++;
            }
        });

        $this->info("Registros procesados: {$processed}");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\WebhookLog;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupBillingData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'billing:cleanup
                           {--days=90 : Keep data for this many days}
                           {--dry-run : Run without making changes}
                           {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old billing data including webhook logs, failed payments, and expired data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("Billing Data Cleanup");
        $this->info("Keeping data for the last {$days} days");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $cutoffDate = Carbon::now()->subDays($days);
        $this->info("Cutoff date: {$cutoffDate->format('Y-m-d H:i:s')}");

        if (!$force && !$dryRun) {
            if (!$this->confirm('Are you sure you want to proceed with the cleanup?')) {
                $this->info('Cleanup cancelled.');
                return;
            }
        }

        $totalCleaned = 0;

        // Limpiar webhook logs antiguos
        $totalCleaned += $this->cleanupWebhookLogs($cutoffDate, $dryRun);

        // Limpiar pagos fallidos antiguos
        $totalCleaned += $this->cleanupFailedPayments($cutoffDate, $dryRun);

        // Limpiar suscripciones canceladas antiguas
        $totalCleaned += $this->cleanupCancelledSubscriptions($cutoffDate, $dryRun);

        // Limpiar cupones expirados
        $totalCleaned += $this->cleanupExpiredCoupons($cutoffDate, $dryRun);

        $this->info("\nCleanup completed!");
        $this->table(
            ['Action', 'Status'],
            [
                ['Total records cleaned', $totalCleaned],
                ['Mode', $dryRun ? 'DRY RUN' : 'EXECUTED'],
                ['Completed at', now()->format('Y-m-d H:i:s')]
            ]
        );

        Log::info('Billing data cleanup completed', [
            'total_cleaned' => $totalCleaned,
            'cutoff_date' => $cutoffDate,
            'dry_run' => $dryRun
        ]);
    }

    protected function cleanupWebhookLogs(Carbon $cutoffDate, bool $dryRun): int
    {
        $this->info("\nCleaning webhook logs older than {$cutoffDate->format('Y-m-d')}...");

        $query = WebhookLog::where('created_at', '<', $cutoffDate)
                          ->where('status', '!=', 'failed'); // Mantener logs fallidos para debug

        $count = $query->count();
        $this->line("Found {$count} webhook logs to clean");

        if (!$dryRun && $count > 0) {
            $deleted = $query->delete();
            $this->info("✓ Deleted {$deleted} webhook logs");
            return $deleted;
        }

        return $dryRun ? $count : 0;
    }

    protected function cleanupFailedPayments(Carbon $cutoffDate, bool $dryRun): int
    {
        $this->info("\nCleaning failed payments older than {$cutoffDate->format('Y-m-d')}...");

        $query = Payment::where('created_at', '<', $cutoffDate)
                       ->where('status', 'failed');

        $count = $query->count();
        $this->line("Found {$count} failed payments to clean");

        if (!$dryRun && $count > 0) {
            $deleted = $query->delete();
            $this->info("✓ Deleted {$deleted} failed payments");
            return $deleted;
        }

        return $dryRun ? $count : 0;
    }

    protected function cleanupCancelledSubscriptions(Carbon $cutoffDate, bool $dryRun): int
    {
        $this->info("\nCleaning cancelled subscriptions older than {$cutoffDate->format('Y-m-d')}...");

        // Solo limpiar suscripciones canceladas que llevan mucho tiempo canceladas
        $query = Subscription::where('updated_at', '<', $cutoffDate)
                            ->where('status', 'cancelled');

        $count = $query->count();
        $this->line("Found {$count} old cancelled subscriptions");

        if (!$dryRun && $count > 0) {
            // En lugar de eliminar, podríamos archivar o comprimir la data
            foreach ($query->get() as $subscription) {
                // Limpiar metadata no esencial pero mantener el registro base
                $subscription->update([
                    'metadata' => [
                        'archived' => true,
                        'archived_at' => now()->toIso8601String(),
                        'original_metadata_count' => count($subscription->metadata ?? [])
                    ]
                ]);
            }
            $this->info("✓ Archived metadata for {$count} cancelled subscriptions");
            return $count;
        }

        return $dryRun ? $count : 0;
    }

    protected function cleanupExpiredCoupons(Carbon $cutoffDate, bool $dryRun): int
    {
        $this->info("\nCleaning expired coupons older than {$cutoffDate->format('Y-m-d')}...");

        $query = \App\Models\Coupon::where('created_at', '<', $cutoffDate)
                                  ->where(function($q) {
                                      $q->where('expires_at', '<', now())
                                        ->orWhere('is_active', false);
                                  })
                                  ->where('usage_count', 0); // Solo cupones no utilizados

        $count = $query->count();
        $this->line("Found {$count} unused expired coupons to clean");

        if (!$dryRun && $count > 0) {
            $deleted = $query->delete();
            $this->info("✓ Deleted {$deleted} unused expired coupons");
            return $deleted;
        }

        return $dryRun ? $count : 0;
    }
}

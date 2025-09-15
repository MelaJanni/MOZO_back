<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentProviderManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessSubscriptionRenewals extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscriptions:process-renewals {--dry-run : Run without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Process subscription renewals and handle failed payments';

    protected $paymentManager;

    public function __construct(PaymentProviderManager $paymentManager)
    {
        parent::__construct();
        $this->paymentManager = $paymentManager;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $this->info('Processing subscription renewals...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Obtener suscripciones que expiran en los próximos días
        $expiringSubscriptions = $this->getExpiringSubscriptions();
        $this->info("Found {$expiringSubscriptions->count()} subscriptions to process");

        $processed = 0;
        $failed = 0;
        $renewed = 0;

        foreach ($expiringSubscriptions as $subscription) {
            try {
                $this->line("Processing subscription {$subscription->id} for user {$subscription->user->email}");

                if ($dryRun) {
                    $this->info("  [DRY RUN] Would process subscription {$subscription->id}");
                    $processed++;
                    continue;
                }

                $result = $this->processSubscriptionRenewal($subscription);

                if ($result['success']) {
                    $renewed++;
                    $this->info("  ✓ Subscription {$subscription->id} renewed successfully");
                } else {
                    $failed++;
                    $this->error("  ✗ Failed to renew subscription {$subscription->id}: {$result['error']}");
                }

                $processed++;

            } catch (\Exception $e) {
                $failed++;
                $this->error("  ✗ Error processing subscription {$subscription->id}: {$e->getMessage()}");
                Log::error('Subscription renewal error', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Procesar suscripciones vencidas para dunning
        $this->processDunning($dryRun);

        $this->info("\nRenewal processing completed:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $processed],
                ['Renewed', $renewed],
                ['Failed', $failed]
            ]
        );
    }

    protected function getExpiringSubscriptions()
    {
        $now = Carbon::now();
        $threshold = $now->copy()->addDays(7); // Procesar las que expiran en 7 días

        return Subscription::where('status', 'active')
            ->where('current_period_end', '<=', $threshold)
            ->with(['user', 'plan'])
            ->get();
    }

    protected function processSubscriptionRenewal(Subscription $subscription)
    {
        try {
            // Si la suscripción tiene método de pago automático, intentar renovar
            if ($subscription->auto_renew && $subscription->payment_method) {
                return $this->attemptAutoRenewal($subscription);
            }

            // Si no tiene auto-renovación, marcar para seguimiento manual
            $subscription->update([
                'status' => 'pending_renewal',
                'metadata' => array_merge($subscription->metadata ?? [], [
                    'renewal_reminder_sent' => now()->toIso8601String()
                ])
            ]);

            // Enviar notificación al usuario
            $this->sendRenewalReminder($subscription);

            return ['success' => true, 'action' => 'reminder_sent'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function attemptAutoRenewal(Subscription $subscription)
    {
        try {
            $provider = $this->paymentManager->getProvider($subscription->payment_method);

            // Intentar cobrar la renovación
            $result = $provider->chargeSubscription([
                'subscription_id' => $subscription->id,
                'amount' => $subscription->plan->price,
                'currency' => 'ARS',
                'description' => "Renovación de {$subscription->plan->name}"
            ]);

            if ($result['success']) {
                // Extender el período de la suscripción
                $newPeriodEnd = Carbon::parse($subscription->current_period_end)
                    ->addDays($subscription->plan->billing_interval_days);

                $subscription->update([
                    'current_period_start' => $subscription->current_period_end,
                    'current_period_end' => $newPeriodEnd,
                    'status' => 'active',
                    'metadata' => array_merge($subscription->metadata ?? [], [
                        'last_renewal' => now()->toIso8601String(),
                        'auto_renewed' => true
                    ])
                ]);

                return ['success' => true, 'action' => 'auto_renewed'];
            } else {
                // Falló el cobro automático, iniciar dunning
                $this->initiateeDunning($subscription, $result['error']);
                return ['success' => false, 'error' => 'Auto-renewal failed: ' . $result['error']];
            }

        } catch (\Exception $e) {
            $this->initiateeDunning($subscription, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function sendRenewalReminder(Subscription $subscription)
    {
        // Aquí se implementaría el envío de notificaciones
        // Por ahora solo logeamos
        Log::info('Renewal reminder sent', [
            'subscription_id' => $subscription->id,
            'user_email' => $subscription->user->email,
            'expiry_date' => $subscription->current_period_end
        ]);
    }

    protected function initiateeDunning(Subscription $subscription, string $error)
    {
        $subscription->update([
            'status' => 'past_due',
            'metadata' => array_merge($subscription->metadata ?? [], [
                'dunning_started' => now()->toIso8601String(),
                'dunning_reason' => $error,
                'dunning_attempts' => 0
            ])
        ]);

        Log::warning('Dunning initiated for subscription', [
            'subscription_id' => $subscription->id,
            'user_email' => $subscription->user->email,
            'reason' => $error
        ]);
    }

    protected function processDunning(bool $dryRun)
    {
        $this->info("\nProcessing dunning management...");

        $pastDueSubscriptions = Subscription::where('status', 'past_due')
            ->with(['user', 'plan'])
            ->get();

        $this->info("Found {$pastDueSubscriptions->count()} past due subscriptions");

        foreach ($pastDueSubscriptions as $subscription) {
            $metadata = $subscription->metadata ?? [];
            $attempts = $metadata['dunning_attempts'] ?? 0;
            $maxAttempts = 3;

            if ($attempts >= $maxAttempts) {
                if (!$dryRun) {
                    $subscription->update(['status' => 'cancelled']);
                    $this->error("  ✗ Subscription {$subscription->id} cancelled after {$maxAttempts} failed attempts");
                }
            } else {
                if (!$dryRun) {
                    // Incrementar intentos y programar próximo intento
                    $subscription->update([
                        'metadata' => array_merge($metadata, [
                            'dunning_attempts' => $attempts + 1,
                            'last_dunning_attempt' => now()->toIso8601String()
                        ])
                    ]);
                }
                $this->info("  → Subscription {$subscription->id} dunning attempt " . ($attempts + 1) . "/{$maxAttempts}");
            }
        }
    }
}

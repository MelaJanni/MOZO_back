<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class ProcessExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:process-expired {--grace-days=7}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process expired subscriptions and handle grace periods for deactivated plans';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $graceDays = (int) $this->option('grace-days');

        $this->info("Processing expired subscriptions with {$graceDays} grace days...");

        // 1. Encontrar suscripciones que deben entrar en período de gracia
        $subscriptionsForGrace = Subscription::query()
            ->whereIn('status', ['active', 'trialing'])
            ->whereHas('plan', function ($query) {
                $query->where('is_active', false); // Plan desactivado
            })
            ->where('current_period_end', '<=', now())
            ->whereNull('grace_ends_at')
            ->get();

        $this->info("Found {$subscriptionsForGrace->count()} subscriptions to enter grace period");

        foreach ($subscriptionsForGrace as $subscription) {
            $subscription->enterGracePeriod($graceDays);

            $this->line("Subscription {$subscription->id} entered grace period (expires: {$subscription->grace_ends_at})");

            // Notificar al usuario
            $this->sendGracePeriodNotification($subscription);
        }

        // 2. Encontrar suscripciones cuyo período de gracia ha expirado
        $expiredGraceSubscriptions = Subscription::query()
            ->where('status', 'grace_period')
            ->where('grace_ends_at', '<=', now())
            ->get();

        $this->info("Found {$expiredGraceSubscriptions->count()} grace periods that have expired");

        foreach ($expiredGraceSubscriptions as $subscription) {
            $subscription->update([
                'status' => 'suspended',
                'requires_plan_selection' => true,
            ]);

            $this->line("Subscription {$subscription->id} suspended - requires new plan selection");

            // Notificar suspensión
            $this->sendSuspensionNotification($subscription);
        }

        $this->info('✅ Processing complete');

        return Command::SUCCESS;
    }

    private function sendGracePeriodNotification(Subscription $subscription)
    {
        $user = $subscription->user;
        $planName = $subscription->plan->name;
        $daysRemaining = $subscription->getGraceDaysRemaining();

        // TODO: Implementar notificación real (email, dashboard, etc.)
        $this->line("📧 Grace period notification sent to {$user->email} - {$daysRemaining} days remaining");
    }

    private function sendSuspensionNotification(Subscription $subscription)
    {
        $user = $subscription->user;

        // TODO: Implementar notificación real (email, dashboard, etc.)
        $this->line("⚠️ Suspension notification sent to {$user->email} - plan selection required");
    }
}

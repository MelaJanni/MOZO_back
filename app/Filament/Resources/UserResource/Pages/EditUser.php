<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Plan;
use App\Models\AdminProfile;
use App\Models\WaiterProfile;
use App\Models\Subscription;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Eliminar'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        // Prefill Membership only (simplified)
        $activeSub = $record->activeSubscription();
        if ($activeSub) {
            $data['membership'] = [
                'plan_id' => $activeSub->plan_id,
                'auto_renew' => (bool) $activeSub->auto_renew,
            ];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Solo extraer membership (simplificado)
        $this->membershipData = $data['membership'] ?? [];
        unset($data['membership']);

        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Versión ultra-simple para encontrar el problema
        try {
            \Log::channel('livewire')->info('EditUser: start update', [
                'id' => $record->getKey(),
                'data_keys' => array_keys($data),
            ]);

            // No enviar flags si la columna no existe en este entorno
            foreach (['is_system_super_admin','is_lifetime_paid'] as $flag) {
                if (!Schema::hasColumn('users', $flag)) {
                    unset($data[$flag]);
                }
            }

            $changed = $data;
            $record->update($data);

            // Solo guardar membresía (simplificado)
            $this->updateSubscription($record, $this->membershipData ?? []);

            \Log::channel('livewire')->info('EditUser: updated', [
                'id' => $record->getKey(),
                'dirty' => array_keys($changed),
            ]);

            \Log::info('handleRecordUpdate exitoso');
            return $record;

        } catch (\Exception $e) {
            \Log::channel('livewire')->error('EditUser: failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    protected function updateSubscription(Model $record, array $membership): void
    {
        $planId = $membership['plan_id'] ?? null;
        $autoRenew = (bool)($membership['auto_renew'] ?? false);

        // Si no se seleccionó plan, cancelar suscripción activa
        if (empty($planId)) {
            $active = $record->activeSubscription();
            if ($active) {
                $active->update(['status' => 'canceled', 'auto_renew' => false]);
                \Log::channel('livewire')->info('EditUser: subscription canceled', ['id' => $record->getKey(), 'sub_id' => $active->id]);
            }
            return;
        }

        $plan = Plan::find($planId);
        if (!$plan) {
            \Log::channel('livewire')->warning('EditUser: plan not found', ['id' => $record->getKey(), 'plan_id' => $planId]);
            return;
        }

        $now = now();
        $periodEnd = match ($plan->interval) {
            'monthly' => $now->copy()->addDays(30),
            'yearly' => $now->copy()->addDays(365),
            default => $now->copy()->addDays(30),
        };

        // Cerrar suscripciones activas previas
        $record->subscriptions()
            ->whereIn('status', ['active','in_trial','past_due'])
            ->update(['status' => 'canceled', 'auto_renew' => false]);

        // Crear nueva suscripción manual
        $sub = Subscription::create([
            'user_id' => $record->getKey(),
            'plan_id' => $plan->id,
            'provider' => 'manual',
            'status' => 'active',
            'auto_renew' => $autoRenew,
            'current_period_end' => $periodEnd,
            'trial_ends_at' => $plan->trial_days ? $now->copy()->addDays((int)$plan->trial_days) : null,
            'metadata' => ['assigned_by' => 'admin_panel', 'at' => $now->toDateTimeString()],
        ]);

        \Log::channel('livewire')->info('EditUser: subscription updated', ['id' => $record->getKey(), 'sub_id' => $sub->id, 'plan_id' => $plan->id, 'auto_renew' => $autoRenew]);
    }

}
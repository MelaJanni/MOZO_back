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

        // Prefill Admin Profile
        $admin = $record->adminProfile;
        if ($admin) {
            $data['adminProfile'] = array_intersect_key($admin->toArray(), array_flip([
                'display_name','business_name','position','corporate_email','corporate_phone','office_extension','business_description','business_website','social_media','permissions','notify_new_orders','notify_staff_requests','notify_reviews','notify_payments','avatar',
            ]));
        }

        // Prefill Waiter Profile
        $waiter = $record->waiterProfile;
        if ($waiter) {
            $data['waiterProfile'] = array_intersect_key($waiter->toArray(), array_flip([
                'display_name','bio','phone','birth_date','height','weight','gender','experience_years','employment_type','current_schedule','current_location','latitude','longitude','availability_hours','skills','is_available','avatar',
            ]));
        }

        // Prefill Membership
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
        // Extraer estructuras anidadas y remover del payload principal
        $this->adminProfileData = $data['adminProfile'] ?? [];
        $this->waiterProfileData = $data['waiterProfile'] ?? [];
        $this->membershipData = $data['membership'] ?? [];

        unset($data['adminProfile'], $data['waiterProfile'], $data['membership']);

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

            // Guardar/Actualizar perfiles
            $this->saveAdminProfile($record, $this->adminProfileData ?? []);
            $this->saveWaiterProfile($record, $this->waiterProfileData ?? []);

            // Guardar/Actualizar membresía
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

    protected function saveAdminProfile(Model $record, array $data): void
    {
        if (empty(array_filter($data, fn($v) => $v !== null && $v !== ''))) {
            return;
        }
        $payload = array_intersect_key($data, array_flip([
            'display_name','business_name','position','corporate_email','corporate_phone','office_extension','business_description','business_website','social_media','permissions','notify_new_orders','notify_staff_requests','notify_reviews','notify_payments','avatar',
        ]));
        $record->adminProfile()->updateOrCreate([], $payload);
        \Log::channel('livewire')->info('EditUser: adminProfile saved', ['id' => $record->getKey(), 'keys' => array_keys($payload)]);
    }

    protected function saveWaiterProfile(Model $record, array $data): void
    {
        if (empty(array_filter($data, fn($v) => $v !== null && $v !== ''))) {
            return;
        }
        $payload = array_intersect_key($data, array_flip([
            'display_name','bio','phone','birth_date','height','weight','gender','experience_years','employment_type','current_schedule','current_location','latitude','longitude','availability_hours','skills','is_available','avatar',
        ]));
        // Normalización básica (asegurar EN para enums si vienen en ES)
        $mapEmployment = [
            'empleado' => 'employee', 'freelancer' => 'freelancer', 'contratista' => 'contractor',
            'tiempo completo' => 'employee', 'tiempo_parcial' => 'contractor',
        ];
        $mapSchedule = [
            'mañana' => 'morning', 'tarde' => 'afternoon', 'noche' => 'night', 'mixto' => 'mixed',
        ];
        if (isset($payload['employment_type'])) {
            $e = strtolower((string)$payload['employment_type']);
            $payload['employment_type'] = $mapEmployment[$e] ?? $payload['employment_type'];
        }
        if (isset($payload['current_schedule'])) {
            $s = strtolower((string)$payload['current_schedule']);
            $payload['current_schedule'] = $mapSchedule[$s] ?? $payload['current_schedule'];
        }

        $record->waiterProfile()->updateOrCreate([], $payload);
        \Log::channel('livewire')->info('EditUser: waiterProfile saved', ['id' => $record->getKey(), 'keys' => array_keys($payload)]);
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
            'monthly' => $now->copy()->addMonth(),
            'yearly', 'annual' => $now->copy()->addYear(),
            default => $now->copy()->addMonth(),
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
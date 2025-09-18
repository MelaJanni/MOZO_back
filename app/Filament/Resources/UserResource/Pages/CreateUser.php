<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Plan;
use App\Models\AdminProfile;
use App\Models\WaiterProfile;
use App\Models\Subscription;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extraer estructuras anidadas y remover del payload principal
        $this->adminProfileData = $data['adminProfile'] ?? [];
        $this->waiterProfileData = $data['waiterProfile'] ?? [];
        $this->membershipData = $data['membership'] ?? [];

        unset($data['adminProfile'], $data['waiterProfile'], $data['membership']);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Crear el usuario
        // Construir datos del usuario respetando columnas existentes
        $userData = $data;
        foreach (['is_system_super_admin','is_lifetime_paid'] as $flag) {
            if (!Schema::hasColumn('users', $flag)) {
                unset($userData[$flag]);
            }
        }
        $user = static::getModel()::create($userData);

        // Crear perfiles si tienen datos
        $adminProfileData = $this->adminProfileData ?? [];
        if (!empty($adminProfileData) && array_filter($adminProfileData)) {
            $payload = array_intersect_key($adminProfileData, array_flip([
                'display_name','business_name','position','corporate_email','corporate_phone','office_extension','business_description','business_website','social_media','permissions','notify_new_orders','notify_staff_requests','notify_reviews','notify_payments','avatar',
            ]));
            $payload = array_filter($payload, function ($v, $k) {
                return Schema::hasColumn('admin_profiles', $k);
            }, ARRAY_FILTER_USE_BOTH);
            $payload['user_id'] = $user->id;
            if (!empty($payload)) {
                AdminProfile::create($payload);
            }
        }

        $waiterProfileData = $this->waiterProfileData ?? [];
        if (!empty($waiterProfileData) && array_filter($waiterProfileData)) {
            $payload = array_intersect_key($waiterProfileData, array_flip([
                'display_name','bio','phone','birth_date','height','weight','gender','experience_years','employment_type','current_schedule','current_location','latitude','longitude','availability_hours','skills','is_available','avatar',
            ]));
            // Normalizar enums ES->EN
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

            $payload = array_filter($payload, function ($v, $k) {
                return Schema::hasColumn('waiter_profiles', $k);
            }, ARRAY_FILTER_USE_BOTH);
            $payload['user_id'] = $user->id;
            if (!empty($payload)) {
                WaiterProfile::create($payload);
            }
        }

        // Crear suscripción si viene membresía
        $membership = $this->membershipData ?? [];
        $planId = $membership['plan_id'] ?? null;
        $autoRenew = (bool)($membership['auto_renew'] ?? false);
        if ($planId) {
            $plan = Plan::find($planId);
            if ($plan) {
                $now = now();
                $periodEnd = match ($plan->interval) {
                    'monthly' => $now->copy()->addMonth(),
                    'yearly' => $now->copy()->addYear(),
                    default => $now->copy()->addMonth(),
                };
                Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'provider' => 'manual',
                    'status' => 'active',
                    'auto_renew' => $autoRenew,
                    'current_period_end' => $periodEnd,
                    'trial_ends_at' => $plan->trial_days ? $now->copy()->addDays((int)$plan->trial_days) : null,
                    'metadata' => ['assigned_by' => 'admin_panel', 'at' => $now->toDateTimeString()],
                ]);
            }
        }

        return $user;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
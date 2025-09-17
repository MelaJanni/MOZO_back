<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\AdminProfile;
use App\Models\WaiterProfile;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Guardar Cambios')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action(function () {
                    // Mostrar notificación de guardado inmediatamente
                    \Filament\Notifications\Notification::make()
                        ->title('Guardando cambios...')
                        ->body('Por favor espera mientras se actualizan los datos')
                        ->info()
                        ->duration(2000)
                        ->send();

                    try {
                        $this->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Cambios guardados exitosamente')
                            ->body('Todos los datos han sido actualizados correctamente')
                            ->success()
                            ->duration(4000)
                            ->sendAfter(1500);
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error al guardar')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\DeleteAction::make()
                ->label('Eliminar'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar datos de perfiles
        $user = $this->record;

        if ($user->adminProfile) {
            $data['adminProfile'] = $user->adminProfile->toArray();
        }

        if ($user->waiterProfile) {
            $data['waiterProfile'] = $user->waiterProfile->toArray();
        }

        // Cargar datos de suscripción
        try {
            $activeSubscription = \App\Models\Subscription::where('user_id', $user->id)
                ->whereIn('status', ['active', 'in_trial'])
                ->first();

            if ($activeSubscription) {
                $data['current_plan_id'] = $activeSubscription->plan_id;
                $data['auto_renew'] = $activeSubscription->auto_renew;
                $data['applied_coupon'] = $activeSubscription->coupon_id;

                // Determinar la fecha de vencimiento
                $endDate = $activeSubscription->status === 'in_trial'
                    ? $activeSubscription->trial_ends_at
                    : $activeSubscription->current_period_end;

                $data['subscription_expires_at'] = $endDate;
            } else {
                $data['current_plan_id'] = null;
                $data['auto_renew'] = false;
                $data['applied_coupon'] = null;
                $data['subscription_expires_at'] = null;
            }
        } catch (\Exception $e) {
            // Si hay error en la BD, usar datos por defecto
            $data['current_plan_id'] = null;
            $data['auto_renew'] = false;
            $data['applied_coupon'] = null;
            $data['subscription_expires_at'] = null;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Separar datos de perfiles
        $adminProfileData = $data['adminProfile'] ?? [];
        $waiterProfileData = $data['waiterProfile'] ?? [];

        // Remover datos de perfiles del array principal
        unset($data['adminProfile'], $data['waiterProfile']);

        // Actualizar el usuario
        $record->update($data);

        // Actualizar o crear AdminProfile
        if (!empty($adminProfileData) && array_filter($adminProfileData)) {
            $record->adminProfile()->updateOrCreate(
                ['user_id' => $record->id],
                $adminProfileData
            );
        }

        // Actualizar o crear WaiterProfile
        if (!empty($waiterProfileData) && array_filter($waiterProfileData)) {
            $record->waiterProfile()->updateOrCreate(
                ['user_id' => $record->id],
                $waiterProfileData
            );
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function updateSubscription($planId)
    {
        if (!$this->record) return;

        // Modo demostración - simular actualización sin BD
        if (!$this->isDatabaseConnected()) {
            $this->simulateSubscriptionUpdate($planId);
            return;
        }

        try {

            // Usar transacción para evitar problemas de concurrencia
            \Illuminate\Support\Facades\DB::transaction(function () use ($planId) {

                if (!$planId) {
                    // Si no hay plan, cancelar suscripciones activas
                    \App\Models\Subscription::where('user_id', $this->record->id)
                        ->whereIn('status', ['active', 'in_trial'])
                        ->update(['status' => 'canceled']);

                    \Filament\Notifications\Notification::make()
                        ->title('Suscripción cancelada')
                        ->body('El usuario ya no tiene plan asignado')
                        ->success()
                        ->duration(4000)
                        ->sendAfter(1500);
                    return;
                }

                // Calcular nueva fecha de expiración según el plan
                $newExpirationDate = match($planId) {
                    '1' => now()->addMonth(), // Plan Mensual
                    '2' => now()->addYear(),  // Plan Anual
                    '3' => now()->addYear(),  // Plan Premium (anual)
                    default => now()->addMonth()
                };

                $planNames = [
                    '1' => 'Plan Mensual',
                    '2' => 'Plan Anual',
                    '3' => 'Plan Premium'
                ];

                // Actualizar suscripción existente o crear nueva
                $activeSubscription = \App\Models\Subscription::where('user_id', $this->record->id)
                    ->whereIn('status', ['active', 'in_trial'])
                    ->first();

                if ($activeSubscription) {
                    // Actualizar suscripción existente
                    $activeSubscription->update([
                        'plan_id' => $planId,
                        'current_period_end' => $newExpirationDate,
                    ]);

                    $planName = $planNames[$planId] ?? 'Plan desconocido';

                    \Filament\Notifications\Notification::make()
                        ->title('Plan actualizado')
                        ->body("Suscripción cambiada a: {$planName}")
                        ->success()
                        ->duration(4000)
                        ->sendAfter(1500);
                } else {
                    // Crear nueva suscripción
                    \App\Models\Subscription::create([
                        'user_id' => $this->record->id,
                        'plan_id' => $planId,
                        'provider' => 'manual',
                        'status' => 'active',
                        'current_period_end' => $newExpirationDate,
                        'auto_renew' => true,
                    ]);

                    $planName = $planNames[$planId] ?? 'Plan desconocido';

                    \Filament\Notifications\Notification::make()
                        ->title('Nueva suscripción creada')
                        ->body("Usuario suscrito a: {$planName}")
                        ->success()
                        ->duration(4000)
                        ->sendAfter(1500);
                }
            });

            // Refrescar campos después de la transacción
            $this->refreshFormData(['auto_renew', 'applied_coupon', 'subscription_expires_at']);

        } catch (\Illuminate\Database\QueryException $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error de base de datos')
                ->body('Error al actualizar la suscripción. Intenta nuevamente.')
                ->danger()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al actualizar suscripción')
                ->body('Error interno del servidor. Contacta al administrador.')
                ->danger()
                ->send();
        }
    }

    private function isDatabaseConnected(): bool
    {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function simulateSubscriptionUpdate($planId): void
    {
        $planNames = [
            '1' => 'Plan Mensual',
            '2' => 'Plan Anual',
            '3' => 'Plan Premium',
            null => 'Sin plan'
        ];

        $planName = $planNames[$planId] ?? 'Plan desconocido';

        if (!$planId) {
            \Filament\Notifications\Notification::make()
                ->title('Simulación: Suscripción cancelada')
                ->body('En producción se cancelaría el plan del usuario')
                ->warning()
                ->duration(3000)
                ->send();
        } else {
            \Filament\Notifications\Notification::make()
                ->title('Simulación: Plan actualizado')
                ->body("En producción se cambiaría a: {$planName}")
                ->info()
                ->duration(3000)
                ->send();
        }

        // Simular refrescar los datos del formulario
        sleep(1);

        \Filament\Notifications\Notification::make()
            ->title('Modo demostración activo')
            ->body('Conecta la base de datos para funcionalidad completa')
            ->warning()
            ->duration(2000)
            ->sendAfter(1000);
    }
}
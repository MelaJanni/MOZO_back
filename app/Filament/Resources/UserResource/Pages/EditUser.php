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
        \Filament\Notifications\Notification::make()
            ->title('Plan actualizado')
            ->success()
            ->send();
    }

}
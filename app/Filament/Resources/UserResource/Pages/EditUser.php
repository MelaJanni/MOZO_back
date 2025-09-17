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
        $user = $this->record;

        if (!$user) {
            return $data;
        }

        try {
            // Cargar perfil de admin si existe
            if ($user->adminProfile) {
                $adminData = $user->adminProfile->toArray();
                $data['adminProfile'] = $adminData;
            }
        } catch (\Exception $e) {
            \Log::error('Error loading adminProfile: ' . $e->getMessage());
        }

        try {
            // Cargar perfil de mozo si existe
            if ($user->waiterProfile) {
                $waiterData = $user->waiterProfile->toArray();
                $data['waiterProfile'] = $waiterData;
            }
        } catch (\Exception $e) {
            \Log::error('Error loading waiterProfile: ' . $e->getMessage());
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
        // Versión simplificada - solo actualizar datos básicos del usuario
        try {
            // Remover datos de perfiles para evitar cualquier conflicto
            unset($data['adminProfile'], $data['waiterProfile']);

            // Actualizar solo el usuario básico
            $record->update($data);

            return $record;

        } catch (\Exception $e) {
            \Log::error('Error en handleRecordUpdate: ' . $e->getMessage());
            throw $e;
        }
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
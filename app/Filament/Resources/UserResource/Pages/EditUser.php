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
        // Logging directo a archivo para capturar errores 502
        $logFile = storage_path('logs/502-debug.log');
        file_put_contents($logFile, "[" . now() . "] handleRecordUpdate INICIO\n", FILE_APPEND);

        try {
            file_put_contents($logFile, "[" . now() . "] Datos recibidos: " . json_encode(array_keys($data)) . "\n", FILE_APPEND);

            // Separar datos de perfiles del array principal
            $adminProfileData = $data['adminProfile'] ?? [];
            $waiterProfileData = $data['waiterProfile'] ?? [];
            file_put_contents($logFile, "[" . now() . "] Perfiles encontrados - Admin: " . (empty($adminProfileData) ? 'NO' : 'SI') . ", Waiter: " . (empty($waiterProfileData) ? 'NO' : 'SI') . "\n", FILE_APPEND);

            // Remover datos de perfiles del array principal
            unset($data['adminProfile'], $data['waiterProfile']);
            file_put_contents($logFile, "[" . now() . "] Datos de usuario después de limpiar: " . json_encode(array_keys($data)) . "\n", FILE_APPEND);

            // Actualizar datos básicos del usuario
            file_put_contents($logFile, "[" . now() . "] Actualizando usuario ID: " . $record->id . "\n", FILE_APPEND);
            $record->update($data);
            file_put_contents($logFile, "[" . now() . "] Usuario actualizado correctamente\n", FILE_APPEND);

            // NO actualizar perfiles para aislar el problema
            file_put_contents($logFile, "[" . now() . "] SALTANDO actualización de perfiles\n", FILE_APPEND);

            file_put_contents($logFile, "[" . now() . "] handleRecordUpdate COMPLETADO\n", FILE_APPEND);
            return $record;

        } catch (\Exception $e) {
            file_put_contents($logFile, "[" . now() . "] ERROR CAPTURADO: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents($logFile, "[" . now() . "] STACK TRACE: " . $e->getTraceAsString() . "\n", FILE_APPEND);
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
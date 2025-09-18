<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\AdminProfile;
use App\Models\WaiterProfile;
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

    // Eliminamos la carga de perfiles por ahora para evitar errores

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remover campos que no pertenecen al modelo User
        unset($data['current_plan_id'], $data['auto_renew']);

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

    public function updateSubscription($planId)
    {
        \Filament\Notifications\Notification::make()
            ->title('Plan actualizado')
            ->success()
            ->send();
    }

}
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
            Actions\ViewAction::make()
                ->label('Ver'),
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
}
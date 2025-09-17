<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\AdminProfile;
use App\Models\WaiterProfile;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remover campos que no pertenecen directamente al modelo User
        unset($data['current_plan_id'], $data['auto_renew']);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
    // Separar datos de perfiles
        $adminProfileData = $data['adminProfile'] ?? [];
        $waiterProfileData = $data['waiterProfile'] ?? [];

        // Remover datos de perfiles del array principal
        unset($data['adminProfile'], $data['waiterProfile']);

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
        if (!empty($adminProfileData) && array_filter($adminProfileData)) {
            $adminProfileData['user_id'] = $user->id;
            AdminProfile::create($adminProfileData);
        }

        if (!empty($waiterProfileData) && array_filter($waiterProfileData)) {
            $waiterProfileData['user_id'] = $user->id;
            WaiterProfile::create($waiterProfileData);
        }

        return $user;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
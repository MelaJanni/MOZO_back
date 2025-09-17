<?php

namespace App\Filament\Resources\UserResourceLite\Pages;

use App\Filament\Resources\UserResourceLite;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateUserResourceLite extends CreateRecord
{
    protected static string $resource = UserResourceLite::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        return $data;
    }
}
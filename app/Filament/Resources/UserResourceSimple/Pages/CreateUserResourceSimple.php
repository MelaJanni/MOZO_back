<?php

namespace App\Filament\Resources\UserResourceSimple\Pages;

use App\Filament\Resources\UserResourceSimple;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateUserResourceSimple extends CreateRecord
{
    protected static string $resource = UserResourceSimple::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        return $data;
    }
}
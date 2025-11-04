<?php

namespace App\Filament\Resources\UserResourceSimple\Pages;

use App\Filament\Resources\UserResourceSimple;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditUserResourceSimple extends EditRecord
{
    protected static string $resource = UserResourceSimple::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['current_plan_id']);
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        return $data;
    }
}
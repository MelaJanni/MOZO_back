<?php

namespace App\Filament\Resources\UserResourceLite\Pages;

use App\Filament\Resources\UserResourceLite;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;

class EditUserResourceLite extends EditRecord
{
    protected static string $resource = UserResourceLite::class;

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
        // Versión ultra-simplificada para evitar problemas de memoria
        try {
            $record->update($data);
            return $record;
        } catch (\Exception $e) {
            \Log::error('Error simple en UserResourceLite: ' . $e->getMessage());
            throw $e;
        }
    }
}
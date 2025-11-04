<?php

namespace App\Filament\Resources\WaiterResource\Pages;

use App\Filament\Resources\WaiterResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateWaiter extends CreateRecord
{
    protected static string $resource = WaiterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $waiterRole = Role::where('name', 'waiter')->first();
        if ($waiterRole) {
            $this->record->assignRole($waiterRole);
        }

        if (isset($this->data['waiterProfile'])) {
            $this->record->waiterProfile()->create($this->data['waiterProfile']);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
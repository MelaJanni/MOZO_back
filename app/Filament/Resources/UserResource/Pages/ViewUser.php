<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\MaxWidth;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar')
                ->modalWidth(MaxWidth::SevenExtraLarge),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar datos de perfiles para la vista
        $user = $this->record;

        if ($user->adminProfile) {
            $data['adminProfile'] = $user->adminProfile->toArray();
        }

        if ($user->waiterProfile) {
            $data['waiterProfile'] = $user->waiterProfile->toArray();
        }

        return $data;
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::SevenExtraLarge;
    }
}
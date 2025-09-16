<?php

namespace App\Filament\Resources\WaiterResource\Pages;

use App\Filament\Resources\WaiterResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWaiter extends ViewRecord
{
    protected static string $resource = WaiterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar'),
        ];
    }
}
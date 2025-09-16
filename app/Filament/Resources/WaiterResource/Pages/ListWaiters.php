<?php

namespace App\Filament\Resources\WaiterResource\Pages;

use App\Filament\Resources\WaiterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWaiters extends ListRecords
{
    protected static string $resource = WaiterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Mozo'),
        ];
    }
}
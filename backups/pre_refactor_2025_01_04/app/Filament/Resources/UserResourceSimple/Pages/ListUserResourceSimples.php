<?php

namespace App\Filament\Resources\UserResourceSimple\Pages;

use App\Filament\Resources\UserResourceSimple;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserResourceSimples extends ListRecords
{
    protected static string $resource = UserResourceSimple::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
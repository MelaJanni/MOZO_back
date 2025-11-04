<?php

namespace App\Filament\Resources\UserResourceLite\Pages;

use App\Filament\Resources\UserResourceLite;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserResourceLites extends ListRecords
{
    protected static string $resource = UserResourceLite::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
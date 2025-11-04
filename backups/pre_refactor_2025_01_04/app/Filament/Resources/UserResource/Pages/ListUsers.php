<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected ?string $heading = 'Gestión de Usuarios';
    protected ?string $subheading = 'Administra todos los usuarios del sistema';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Usuario')
                ->icon('heroicon-o-plus')
                ->modalWidth(MaxWidth::SevenExtraLarge)
                ->createAnother(false)
                ->successNotificationTitle('Usuario creado exitosamente'),
        ];
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No hay usuarios registrados';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Cuando se registren nuevos usuarios en la plataforma, aparecerán aquí.';
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-users';
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Primer Usuario')
                ->icon('heroicon-o-plus'),
        ];
    }
}
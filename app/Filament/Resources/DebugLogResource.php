<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DebugLogResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;

class DebugLogResource extends Resource
{
    protected static ?string $model = null; // No necesitamos modelo

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

    protected static ?string $navigationLabel = '🔍 Debug Logs';

    protected static ?string $modelLabel = 'Debug Log';

    protected static ?string $pluralModelLabel = 'Debug Logs';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 999;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // No necesitamos formulario
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Será manejado en la página personalizada
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDebugLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
<?php

namespace App\Filament\Resources;

use App\Models\Menu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Gestión';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('business_id')
                ->relationship('business','name')
                ->required()
                ->label('Negocio'),
            Forms\Components\TextInput::make('name')
                ->required()
                ->label('Nombre del menú'),
            Forms\Components\TextInput::make('file_path')
                ->label('Ruta PDF')
                ->required()
                ->hint(fn ($record) => $record && $record->file_path ? 'Archivo: ' . basename($record->file_path) : 'No hay archivo subido')
                ->suffixAction(
                    Forms\Components\Actions\Action::make('download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->tooltip('Descargar PDF')
                        ->url(fn ($record) => $record && $record->id ? url('/api/menus/' . $record->id . '/download') : null)
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => $record && $record->id && $record->file_path)
                )
                ->suffixAction(
                    Forms\Components\Actions\Action::make('preview')
                        ->icon('heroicon-o-eye')
                        ->tooltip('Ver PDF')
                        ->url(fn ($record) => $record && $record->id ? url('/api/menus/' . $record->id . '/preview') : null)
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => $record && $record->id && $record->file_path)
                ),
            Forms\Components\Toggle::make('is_default')
                ->label('Menú por defecto'),
            Forms\Components\TextInput::make('display_order')
                ->numeric()
                ->default(1)
                ->label('Orden de visualización'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('business.name')->label('Negocio'),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\IconColumn::make('is_default')->boolean(),
                Tables\Columns\TextColumn::make('display_order')->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->icon('heroicon-o-eye')
                    ->label('')
                    ->tooltip('Ver PDF')
                    ->url(fn ($record) => url('/api/menus/' . $record->id . '/preview'))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->file_path),
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->label('')
                    ->tooltip('Descargar PDF')
                    ->url(fn ($record) => url('/api/menus/' . $record->id . '/download'))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->file_path),
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    public static function getPages(): array
    {
        return [
            'index' => MenuResource\Pages\ListMenus::route('/'),
            'create' => MenuResource\Pages\CreateMenu::route('/create'),
            'edit' => MenuResource\Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}

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
    protected static ?string $navigationLabel = 'Menús';
    protected static ?string $modelLabel = 'Menú';
    protected static ?string $pluralModelLabel = 'Menús';
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
            Forms\Components\FileUpload::make('pdf_file')
                ->label('Archivo PDF del Menú')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(10240) // 10MB
                ->directory('menus')
                ->preserveFilenames()
                ->helperText('Sube el PDF del menú (máximo 10MB)')
                ->required()
                ->afterStateUpdated(function ($state, $set) {
                    if ($state) {
                        $set('file_path', 'menus/' . $state->getClientOriginalName());
                    }
                }),
            Forms\Components\TextInput::make('file_path')
                ->label('Ruta del archivo')
                ->disabled()
                ->helperText('Se genera automáticamente al subir el PDF'),
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

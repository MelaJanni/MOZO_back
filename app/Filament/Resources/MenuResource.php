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
            Forms\Components\Select::make('business_id')->relationship('business','name')->required(),
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('file_path')->label('Ruta PDF')->required(),
            Forms\Components\Toggle::make('is_default'),
            Forms\Components\TextInput::make('display_order')->numeric()->default(1),
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
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

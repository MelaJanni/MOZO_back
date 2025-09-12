<?php

namespace App\Filament\Resources;

use App\Models\Business;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Gestión';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('email')->nullable(),
            Forms\Components\TextInput::make('phone')->nullable(),
            Forms\Components\Textarea::make('description')->nullable(),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id'),
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('email'),
            Tables\Columns\TextColumn::make('phone'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => BusinessResource\Pages\ListBusinesses::route('/'),
            'create' => BusinessResource\Pages\CreateBusiness::route('/create'),
            'edit' => BusinessResource\Pages\EditBusiness::route('/{record}/edit'),
        ];
    }
}

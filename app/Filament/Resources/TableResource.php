<?php

namespace App\Filament\Resources;

use App\Models\Table as TableModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table as TablesTable;

class TableResource extends Resource
{
    protected static ?string $model = TableModel::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';
    protected static ?string $navigationLabel = 'Mesas';
    protected static ?string $modelLabel = 'Mesa';
    protected static ?string $pluralModelLabel = 'Mesas';
    protected static ?string $navigationGroup = 'Gestión';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('number')->numeric()->required(),
            Forms\Components\TextInput::make('code')->readOnly(),
            Forms\Components\Select::make('business_id')->relationship('business','name')->required(),
            Forms\Components\Toggle::make('notifications_enabled'),
            Forms\Components\TextInput::make('capacity')->numeric()->default(4),
            Forms\Components\TextInput::make('location')->nullable(),
            Forms\Components\Select::make('status')->options([
                'available'=>'Disponible','busy'=>'Ocupada','disabled'=>'Deshabilitada'
            ])->default('available'),
        ]);
    }

    public static function table(TablesTable $table): TablesTable
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('number')->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'available',
                        'warning' => 'busy',
                        'danger' => 'disabled',
                    ]),
                Tables\Columns\IconColumn::make('notifications_enabled')->boolean(),
                Tables\Columns\TextColumn::make('business.name')->label('Negocio'),
            ])
            ->filters([
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => TableResource\Pages\ListTables::route('/'),
            'create' => TableResource\Pages\CreateTable::route('/create'),
            'edit' => TableResource\Pages\EditTable::route('/{record}/edit'),
        ];
    }
}

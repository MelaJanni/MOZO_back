<?php

namespace App\Filament\Resources;

use App\Models\Plan;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Resources\Resource;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Billing';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord:true),
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Select::make('interval')->options(['month'=>'Mensual','year'=>'Anual'])->required(),
            Forms\Components\TextInput::make('price_cents')->numeric()->required(),
            Forms\Components\TextInput::make('currency')->default('USD')->maxLength(3)->required(),
            Forms\Components\TextInput::make('trial_days')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->searchable(),
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('interval')->badge(),
            Tables\Columns\TextColumn::make('price_cents')->label('Precio (centavos)'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }
}

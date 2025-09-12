<?php

namespace App\Filament\Resources;

use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Resources\Resource;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;
    protected static ?string $navigationGroup = 'Facturación';
    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord:true),
            Forms\Components\Select::make('type')->options([
                'percent'=>'% descuento', 'fixed'=>'$ fijo', 'free_time'=>'Días gratis'
            ])->required(),
            Forms\Components\TextInput::make('value')->numeric()->label('Valor (centavos o %)')->nullable(),
            Forms\Components\TextInput::make('free_days')->numeric()->nullable(),
            Forms\Components\TextInput::make('max_redemptions')->numeric()->nullable(),
            Forms\Components\DateTimePicker::make('expires_at')->nullable(),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->searchable(),
            Tables\Columns\TextColumn::make('type')->badge(),
            Tables\Columns\TextColumn::make('value'),
            Tables\Columns\TextColumn::make('free_days'),
            Tables\Columns\TextColumn::make('redeemed_count'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => CouponResource\Pages\ListCoupons::route('/'),
            'create' => CouponResource\Pages\CreateCoupon::route('/create'),
            'edit' => CouponResource\Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}

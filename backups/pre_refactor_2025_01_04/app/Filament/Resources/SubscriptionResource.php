<?php

namespace App\Filament\Resources;

use App\Models\Subscription;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;
    protected static ?string $navigationGroup = 'Facturación';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')->relationship('user', 'email')->searchable()->required(),
            Forms\Components\Select::make('plan_id')->relationship('plan', 'name')->required(),
            Forms\Components\Select::make('provider')->options(['mp'=>'Mercado Pago','paypal'=>'PayPal','offline'=>'Transferencia'])->required(),
            Forms\Components\Select::make('status')->options([
                'active'=>'Activo','in_trial'=>'Trial','past_due'=>'Impago','canceled'=>'Cancelada','on_hold'=>'En espera'
            ])->required(),
            Forms\Components\Toggle::make('auto_renew')->default(true),
            Forms\Components\DateTimePicker::make('current_period_end')->nullable(),
            Forms\Components\DateTimePicker::make('trial_ends_at')->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('user.email')->label('Usuario')->searchable(),
            Tables\Columns\TextColumn::make('plan.name')->label('Plan'),
            Tables\Columns\TextColumn::make('provider')->badge(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\IconColumn::make('auto_renew')->boolean(),
            Tables\Columns\TextColumn::make('current_period_end')->dateTime(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => SubscriptionResource\Pages\ListSubscriptions::route('/'),
            'create' => SubscriptionResource\Pages\CreateSubscription::route('/create'),
            'edit' => SubscriptionResource\Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}

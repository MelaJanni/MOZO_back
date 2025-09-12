<?php

namespace App\Filament\Resources;

use App\Models\Payment;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationGroup = 'Facturación';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')->relationship('user', 'email')->searchable()->required(),
            Forms\Components\Select::make('subscription_id')->relationship('subscription', 'id')->searchable()->nullable(),
            Forms\Components\Select::make('provider')->options(['mp'=>'Mercado Pago','paypal'=>'PayPal','offline'=>'Transferencia'])->required(),
            Forms\Components\TextInput::make('amount_cents')->numeric()->required(),
            Forms\Components\TextInput::make('currency')->maxLength(3)->default('USD'),
            Forms\Components\Select::make('status')->options(['paid'=>'Pagado','pending'=>'Pendiente','failed'=>'Fallido','refunded'=>'Reembolsado'])->required(),
            Forms\Components\DateTimePicker::make('paid_at')->nullable(),
            Forms\Components\Textarea::make('failure_reason')->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id'),
            Tables\Columns\TextColumn::make('user.email')->label('Usuario')->searchable(),
            Tables\Columns\TextColumn::make('provider')->badge(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('amount_cents')->label('Monto (¢)'),
            Tables\Columns\TextColumn::make('paid_at')->dateTime(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => PaymentResource\Pages\ListPayments::route('/'),
            'create' => PaymentResource\Pages\CreatePayment::route('/create'),
            'edit' => PaymentResource\Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}

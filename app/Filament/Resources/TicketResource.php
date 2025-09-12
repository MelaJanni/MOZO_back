<?php

namespace App\Filament\Resources;

use App\Models\Ticket;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;
    protected static ?string $navigationGroup = 'Soporte';
    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')->relationship('user','email')->searchable()->nullable(),
            Forms\Components\Select::make('business_id')->relationship('business','name')->searchable()->nullable(),
            Forms\Components\TextInput::make('subject')->required(),
            Forms\Components\Textarea::make('message')->required()->columnSpanFull(),
            Forms\Components\Select::make('status')->options(['open'=>'Abierto','pending'=>'Pendiente','closed'=>'Cerrado'])->required(),
            Forms\Components\Select::make('priority')->options(['low'=>'Baja','normal'=>'Normal','high'=>'Alta'])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id'),
            Tables\Columns\TextColumn::make('subject')->searchable(),
            Tables\Columns\TextColumn::make('user.email')->label('Usuario')->searchable(),
            Tables\Columns\TextColumn::make('business.name')->label('Negocio'),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('priority')->badge(),
            Tables\Columns\TextColumn::make('created_at')->since(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => TicketResource\Pages\ListTickets::route('/'),
            'create' => TicketResource\Pages\CreateTicket::route('/create'),
            'edit' => TicketResource\Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}

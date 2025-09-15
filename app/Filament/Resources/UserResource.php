<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Gestión';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información Personal')
                ->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('email')->email()->required(),
                    Forms\Components\TextInput::make('password')->password()
                        ->dehydrateStateUsing(fn($s)=>$s?bcrypt($s):null)
                        ->dehydrated(fn($s)=>filled($s))
                        ->hiddenOn('edit'),
                ]),

            Forms\Components\Section::make('Membresía Legacy')
                ->description('Campos legacy - usar Subscriptions para nuevas membresías')
                ->schema([
                    Forms\Components\Select::make('membership_plan')
                        ->options([
                            'free' => 'Gratuito',
                            'basic' => 'Básico',
                            'premium' => 'Premium',
                        ]),
                    Forms\Components\DateTimePicker::make('membership_expires_at'),
                    Forms\Components\Toggle::make('is_lifetime_paid')
                        ->label('Pago de por vida'),
                ])
                ->collapsed(),

            Forms\Components\Section::make('Sistema')
                ->schema([
                    Forms\Components\Toggle::make('is_system_super_admin')
                        ->label('Super Administrador del Sistema'),
                ])
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('email')->searchable(),
            Tables\Columns\IconColumn::make('email_verified_at')
                ->boolean()
                ->label('Verificado'),
            Tables\Columns\TextColumn::make('membership_plan')
                ->label('Plan Legacy')
                ->badge()
                ->color(fn($state) => match($state) {
                    'premium' => 'success',
                    'basic' => 'warning',
                    default => 'gray'
                }),
            Tables\Columns\TextColumn::make('membership_expires_at')
                ->label('Expira')
                ->dateTime()
                ->since(),
            Tables\Columns\IconColumn::make('is_lifetime_paid')
                ->boolean()
                ->label('Vitalicio'),
            Tables\Columns\IconColumn::make('is_system_super_admin')
                ->boolean()
                ->label('Super Admin'),
        ])->filters([
            Tables\Filters\SelectFilter::make('membership_plan')
                ->options([
                    'free' => 'Gratuito',
                    'basic' => 'Básico',
                    'premium' => 'Premium',
                ]),
            Tables\Filters\TernaryFilter::make('is_lifetime_paid')
                ->label('Pago Vitalicio'),
        ])->actions([
            Tables\Actions\EditAction::make(),

            Action::make('assign_plan')
                ->label('Asignar Plan')
                ->icon('heroicon-o-credit-card')
                ->form([
                    Forms\Components\Select::make('plan')
                        ->options([
                            'free' => 'Gratuito',
                            'basic' => 'Básico',
                            'premium' => 'Premium',
                        ])
                        ->required(),
                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Fecha de Expiración')
                        ->required(),
                ])
                ->action(function (User $record, array $data) {
                    $record->update([
                        'membership_plan' => $data['plan'],
                        'membership_expires_at' => $data['expires_at'],
                    ]);

                    Notification::make()
                        ->title('Plan asignado exitosamente')
                        ->success()
                        ->send();
                }),

            Action::make('mark_lifetime')
                ->label('Marcar Vitalicio')
                ->icon('heroicon-o-star')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (User $record) {
                    $record->update([
                        'is_lifetime_paid' => true,
                        'membership_expires_at' => null,
                    ]);

                    Notification::make()
                        ->title('Usuario marcado como pago vitalicio')
                        ->success()
                        ->send();
                }),

            Tables\Actions\DeleteAction::make(),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => UserResource\Pages\ListUsers::route('/'),
            'create' => UserResource\Pages\CreateUser::route('/create'),
            'edit' => UserResource\Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

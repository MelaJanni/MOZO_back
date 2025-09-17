<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResourceSimple\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;

class UserResourceSimple extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Usuarios (Simple)';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?string $navigationGroup = 'Gestión de Usuarios';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre completo')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8),
                        Forms\Components\Toggle::make('is_system_super_admin')
                            ->label('Super Administrador del Sistema'),
                        Forms\Components\Toggle::make('is_lifetime_paid')
                            ->label('Cliente pago permanente'),
                    ])->columns(2),

                Section::make('Plan Actual')
                    ->schema([
                        Forms\Components\Select::make('current_plan_id')
                            ->label('Plan asignado')
                            ->options([
                                '' => 'Sin plan asignado',
                                '1' => 'Plan Mensual',
                                '2' => 'Plan Anual',
                                '3' => 'Plan Premium',
                            ])
                            ->nullable()
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function ($state, $record, $livewire) {
                                if (!$record) return;

                                \Filament\Notifications\Notification::make()
                                    ->title('Plan actualizado')
                                    ->success()
                                    ->send();
                            }),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->label('Rol')
                    ->getStateUsing(function ($record) {
                        if ($record->is_system_super_admin) return 'Super Admin';
                        if ($record->is_lifetime_paid) return 'Admin';
                        return 'Mozo';
                    })
                    ->colors([
                        'danger' => 'Super Admin',
                        'warning' => 'Admin',
                        'primary' => 'Mozo',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserResourceSimples::route('/'),
            'create' => Pages\CreateUserResourceSimple::route('/create'),
            'edit' => Pages\EditUserResourceSimple::route('/{record}/edit'),
        ];
    }
}
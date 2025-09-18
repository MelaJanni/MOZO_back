<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?string $navigationGroup = 'Gestión de Usuarios';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Usuario')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('cuenta')
                            ->label('Información de la Cuenta')
                            ->schema([
                                Forms\Components\Section::make('Datos Básicos')
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
                                            ->minLength(8)
                                            ->helperText(fn ($context) => $context === 'edit' ? 'Dejar vacío para mantener la contraseña actual' : 'Mínimo 8 caracteres'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Privilegios del Sistema')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_system_super_admin')
                                            ->label('Super Administrador del Sistema')
                                            ->dehydrated(true)
                                            ->helperText('Otorga acceso completo al panel administrativo'),
                                        Forms\Components\Toggle::make('is_lifetime_paid')
                                            ->label('Cliente pago permanente')
                                            ->dehydrated(true)
                                            ->helperText('Usuario con acceso de por vida sin renovaciones'),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('membresía')
                            ->label('Membresía y Pagos')
                            ->schema([
                                Forms\Components\Section::make('Plan Actual')
                                    ->schema([
                                        Forms\Components\Select::make('membership.plan_id')
                                            ->label('Plan asignado')
                                            ->options(fn () => \App\Models\Plan::query()->where('is_active', true)->get()->mapWithKeys(fn($p) => [$p->id => $p->name . ' - ' . $p->formatted_price])->toArray())
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Sin plan asignado'),
                                        Forms\Components\Toggle::make('membership.auto_renew')
                                            ->label('Renovación automática'),
                                        Forms\Components\Placeholder::make('membership_status')
                                            ->label('Estado de membresía')
                                            ->content(fn (?\App\Models\User $record) => $record ? (($record->hasActiveMembership() ? 'Activa' : 'Inactiva') . ($record->membershipTimeRemaining() ? ' • Tiempo restante: ' . $record->membershipTimeRemaining() : '')) : '-'),
                                    ])->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_system_super_admin')
                    ->label('Admin')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\EditUser::route('/{record}'),
        ];
    }
}
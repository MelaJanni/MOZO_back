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

                        Forms\Components\Tabs\Tab::make('mozo')
                            ->label('Información del Mozo')
                            ->schema([
                                Forms\Components\Section::make('Perfil de Mozo')
                                    ->schema([
                                        Forms\Components\TextInput::make('waiterProfile.display_name')
                                            ->label('Nombre a mostrar')
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('waiterProfile.bio')
                                            ->label('Bio')
                                            ->rows(3),
                                        Forms\Components\TextInput::make('waiterProfile.phone')
                                            ->label('Teléfono')
                                            ->maxLength(50),
                                        Forms\Components\DatePicker::make('waiterProfile.birth_date')
                                            ->label('Fecha de nacimiento')
                                            ->native(false)
                                            ->displayFormat('Y-m-d'),
                                        Forms\Components\TextInput::make('waiterProfile.height')
                                            ->label('Altura (m)')
                                            ->numeric()
                                            ->minValue(1.0)
                                            ->maxValue(2.5)
                                            ->step(0.01),
                                        Forms\Components\TextInput::make('waiterProfile.weight')
                                            ->label('Peso (kg)')
                                            ->numeric()
                                            ->minValue(30)
                                            ->maxValue(200)
                                            ->step(1),
                                        Forms\Components\Select::make('waiterProfile.gender')
                                            ->label('Género')
                                            ->options([
                                                'masculino' => 'Masculino',
                                                'femenino' => 'Femenino',
                                                'otro' => 'Otro',
                                            ])
                                            ->native(false),
                                        Forms\Components\TextInput::make('waiterProfile.experience_years')
                                            ->label('Años de experiencia')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(50)
                                            ->step(1),
                                        Forms\Components\Select::make('waiterProfile.employment_type')
                                            ->label('Tipo de empleo')
                                            ->options([
                                                'employee' => 'Employee',
                                                'freelancer' => 'Freelancer',
                                                'contractor' => 'Contractor',
                                            ])
                                            ->native(false)
                                            ->helperText('Enviar en inglés'),
                                        Forms\Components\Select::make('waiterProfile.current_schedule')
                                            ->label('Horario actual')
                                            ->options([
                                                'morning' => 'Morning',
                                                'afternoon' => 'Afternoon',
                                                'night' => 'Night',
                                                'mixed' => 'Mixed',
                                            ])
                                            ->native(false)
                                            ->helperText('Enviar en inglés'),
                                        Forms\Components\TextInput::make('waiterProfile.current_location')
                                            ->label('Ubicación actual')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('waiterProfile.latitude')
                                            ->label('Latitud')
                                            ->numeric()
                                            ->minValue(-90)
                                            ->maxValue(90)
                                            ->step(0.000001),
                                        Forms\Components\TextInput::make('waiterProfile.longitude')
                                            ->label('Longitud')
                                            ->numeric()
                                            ->minValue(-180)
                                            ->maxValue(180)
                                            ->step(0.000001),
                                        Forms\Components\TagsInput::make('waiterProfile.availability_hours')
                                            ->label('Horas disponibles'),
                                        Forms\Components\TagsInput::make('waiterProfile.skills')
                                            ->label('Habilidades'),
                                        Forms\Components\Toggle::make('waiterProfile.is_available')
                                            ->label('Disponible'),
                                        Forms\Components\FileUpload::make('waiterProfile.avatar')
                                            ->label('Avatar')
                                            ->image()
                                            ->directory('avatars/waiters')
                                            ->downloadable(),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('admin')
                            ->label('Información del Admin')
                            ->schema([
                                Forms\Components\Section::make('Perfil de Administrador')
                                    ->schema([
                                        Forms\Components\TextInput::make('adminProfile.display_name')
                                            ->label('Nombre a mostrar')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('adminProfile.business_name')
                                            ->label('Nombre del negocio')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('adminProfile.position')
                                            ->label('Cargo')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('adminProfile.corporate_email')
                                            ->label('Email corporativo')
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('adminProfile.corporate_phone')
                                            ->label('Teléfono corporativo')
                                            ->maxLength(50),
                                        Forms\Components\FileUpload::make('adminProfile.avatar')
                                            ->label('Avatar')
                                            ->image()
                                            ->directory('avatars/admins')
                                            ->downloadable(),
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
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->getStateUsing(fn ($record) => $record->google_avatar ?: null)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF')
                    ->size(40),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->google_id ? 'Cuenta Google' : 'Cuenta local'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon(fn ($record) => $record->google_id ? 'heroicon-o-globe-alt' : 'heroicon-o-envelope'),
                Tables\Columns\TextColumn::make('google_data')
                    ->label('Datos Google')
                    ->getStateUsing(fn ($record) => $record->google_id ? 'ID: ' . substr($record->google_id, 0, 15) . '...' : '-')
                    ->tooltip(fn ($record) => $record->google_id ? 'Google ID: ' . $record->google_id : 'No es cuenta Google')
                    ->badge()
                    ->color(fn ($record) => $record->google_id ? 'success' : 'gray'),
                Tables\Columns\IconColumn::make('is_system_super_admin')
                    ->label('Admin')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rol')
                    ->options([
                        'waiter' => 'Mozo',
                        'admin' => 'Administrador',
                    ])
                    ->placeholder('Todos los roles'),
                Tables\Filters\TernaryFilter::make('google_id')
                    ->label('Tipo de cuenta')
                    ->trueLabel('Cuentas Google')
                    ->falseLabel('Cuentas locales')
                    ->nullable(),
                Tables\Filters\TernaryFilter::make('is_system_super_admin')
                    ->label('Super Admin')
                    ->trueLabel('Super Administradores')
                    ->falseLabel('Usuarios normales'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
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
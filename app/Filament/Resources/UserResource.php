<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Plan;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Model;

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
                Tabs::make('Usuario')
                    ->tabs([
                        Tabs\Tab::make('Información de la Cuenta')
                            ->schema([
                                Section::make('Datos Básicos')
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
                                    ])->columns(2),

                                Section::make('Autenticación OAuth')
                                    ->schema([
                                        Forms\Components\TextInput::make('google_id')
                                            ->label('ID de Google')
                                            ->disabled(),
                                        Forms\Components\FileUpload::make('google_avatar')
                                            ->label('Avatar de Google')
                                            ->image()
                                            ->disabled(),
                                        Forms\Components\DateTimePicker::make('email_verified_at')
                                            ->label('Email verificado el')
                                            ->disabled(),
                                    ])->columns(2),

                                Section::make('Roles y Permisos')
                                    ->schema([
                                        Forms\Components\Select::make('roles')
                                            ->label('Rol del usuario')
                                            ->relationship('roles', 'name')
                                            ->options(Role::all()->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload(),
                                        Forms\Components\Toggle::make('is_system_super_admin')
                                            ->label('Super Administrador del Sistema')
                                            ->helperText('Acceso completo al panel administrativo')
                                            ->columnSpanFull(),
                                    ])->columns(2),

                                Section::make('Membresía y Pagos')
                                    ->schema([
                                        Forms\Components\Select::make('current_plan_id')
                                            ->label('Plan asignado')
                                            ->relationship('subscriptions.plan', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')->required(),
                                                Forms\Components\Textarea::make('description'),
                                                Forms\Components\TextInput::make('price')->numeric()->required(),
                                            ]),
                                        Forms\Components\Toggle::make('auto_renew')
                                            ->label('Renovación automática')
                                            ->default(true)
                                            ->helperText('La suscripción se renueva automáticamente'),
                                        Forms\Components\Toggle::make('is_lifetime_paid')
                                            ->label('Cliente pago permanente')
                                            ->helperText('Usuario con acceso de por vida sin renovaciones'),
                                        Forms\Components\Select::make('active_coupon')
                                            ->label('Cupón aplicado')
                                            ->options(Coupon::where('is_active', true)->pluck('code', 'id'))
                                            ->searchable()
                                            ->nullable(),
                                        Forms\Components\DateTimePicker::make('membership_expires_at')
                                            ->label('Vencimiento de membresía')
                                            ->nullable(),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('Perfil de Administrador')
                            ->schema([
                                Section::make('Información Personal')
                                    ->schema([
                                        Forms\Components\FileUpload::make('adminProfile.avatar')
                                            ->label('Avatar personalizado')
                                            ->image()
                                            ->directory('admin-avatars')
                                            ->visibility('public'),
                                        Forms\Components\TextInput::make('adminProfile.display_name')
                                            ->label('Nombre a mostrar')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('adminProfile.position')
                                            ->label('Cargo/Posición')
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('adminProfile.bio')
                                            ->label('Biografía')
                                            ->rows(3),
                                    ])->columns(2),

                                Section::make('Información de Contacto')
                                    ->schema([
                                        Forms\Components\TextInput::make('adminProfile.corporate_email')
                                            ->label('Email corporativo')
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('adminProfile.corporate_phone')
                                            ->label('Teléfono corporativo')
                                            ->tel()
                                            ->maxLength(20),
                                        Forms\Components\TextInput::make('adminProfile.office_extension')
                                            ->label('Extensión de oficina')
                                            ->maxLength(10),
                                    ])->columns(2),

                                Section::make('Configuraciones de Notificaciones')
                                    ->schema([
                                        Forms\Components\Toggle::make('adminProfile.notify_new_orders')
                                            ->label('Notificar nuevos pedidos')
                                            ->default(true),
                                        Forms\Components\Toggle::make('adminProfile.notify_staff_requests')
                                            ->label('Notificar solicitudes de personal')
                                            ->default(true),
                                        Forms\Components\Toggle::make('adminProfile.notify_reviews')
                                            ->label('Notificar nuevas reseñas')
                                            ->default(true),
                                        Forms\Components\Toggle::make('adminProfile.notify_payments')
                                            ->label('Notificar pagos')
                                            ->default(true),
                                    ])->columns(2),
                            ])
                            ->visible(fn ($record) => !$record || $record?->isAdmin()),

                        Tabs\Tab::make('Perfil de Mozo')
                            ->schema([
                                Section::make('Información Personal')
                                    ->schema([
                                        Forms\Components\FileUpload::make('waiterProfile.avatar')
                                            ->label('Avatar personalizado')
                                            ->image()
                                            ->directory('waiter-avatars')
                                            ->visibility('public'),
                                        Forms\Components\TextInput::make('waiterProfile.display_name')
                                            ->label('Nombre a mostrar')
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('waiterProfile.bio')
                                            ->label('Biografía')
                                            ->rows(3),
                                        Forms\Components\TextInput::make('waiterProfile.phone')
                                            ->label('Teléfono personal')
                                            ->tel()
                                            ->maxLength(20),
                                    ])->columns(2),

                                Section::make('Datos Físicos')
                                    ->schema([
                                        Forms\Components\DatePicker::make('waiterProfile.birth_date')
                                            ->label('Fecha de nacimiento'),
                                        Forms\Components\Select::make('waiterProfile.gender')
                                            ->label('Género')
                                            ->options([
                                                'male' => 'Masculino',
                                                'female' => 'Femenino',
                                                'other' => 'Otro',
                                                'prefer_not_to_say' => 'Prefiero no decir',
                                            ]),
                                        Forms\Components\TextInput::make('waiterProfile.height')
                                            ->label('Altura (cm)')
                                            ->numeric()
                                            ->suffix('cm'),
                                        Forms\Components\TextInput::make('waiterProfile.weight')
                                            ->label('Peso (kg)')
                                            ->numeric()
                                            ->suffix('kg'),
                                    ])->columns(2),

                                Section::make('Experiencia Laboral')
                                    ->schema([
                                        Forms\Components\TextInput::make('waiterProfile.experience_years')
                                            ->label('Años de experiencia')
                                            ->numeric()
                                            ->suffix('años'),
                                        Forms\Components\Select::make('waiterProfile.employment_type')
                                            ->label('Tipo de empleo')
                                            ->options([
                                                'full_time' => 'Tiempo completo',
                                                'part_time' => 'Medio tiempo',
                                                'freelance' => 'Freelance',
                                                'contract' => 'Por contrato',
                                            ]),
                                        Forms\Components\Select::make('waiterProfile.current_schedule')
                                            ->label('Horario actual')
                                            ->options([
                                                'morning' => 'Mañana',
                                                'afternoon' => 'Tarde',
                                                'night' => 'Noche',
                                                'flexible' => 'Flexible',
                                            ]),
                                        Forms\Components\TagsInput::make('waiterProfile.skills')
                                            ->label('Habilidades')
                                            ->placeholder('Agregar habilidad...'),
                                    ])->columns(2),

                                Section::make('Estado y Disponibilidad')
                                    ->schema([
                                        Forms\Components\Toggle::make('waiterProfile.is_active')
                                            ->label('Perfil activo')
                                            ->default(true),
                                        Forms\Components\Toggle::make('waiterProfile.is_available')
                                            ->label('Disponible para trabajar')
                                            ->default(true),
                                        Forms\Components\TextInput::make('waiterProfile.current_location')
                                            ->label('Ubicación actual')
                                            ->maxLength(255),
                                    ])->columns(2),
                            ])
                            ->visible(fn ($record) => !$record || $record?->isWaiter()),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('google_avatar')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\BadgeColumn::make('user_roles')
                    ->label('Rol')
                    ->getStateUsing(function ($record) {
                        $roles = [];

                        // Verificar si es super admin del sistema
                        if ($record->is_system_super_admin) {
                            $roles[] = 'Super Admin';
                        }

                        // Verificar roles de Spatie
                        if ($record->roles->isNotEmpty()) {
                            $roles = array_merge($roles, $record->roles->pluck('name')->toArray());
                        }

                        // Verificar roles basados en relaciones de negocio
                        if ($record->isAdmin()) {
                            $roles[] = 'Admin';
                        }

                        if ($record->isWaiter()) {
                            $roles[] = 'Mozo';
                        }

                        // Si no tiene roles, asignar "Usuario"
                        if (empty($roles)) {
                            $roles[] = 'Usuario';
                        }

                        return implode(', ', array_unique($roles));
                    })
                    ->colors([
                        'danger' => fn (string $state): bool => str_contains($state, 'Super Admin'),
                        'warning' => fn (string $state): bool => str_contains($state, 'Admin') && !str_contains($state, 'Super'),
                        'primary' => fn (string $state): bool => str_contains($state, 'Mozo'),
                        'secondary' => fn (string $state): bool => $state === 'Usuario',
                        'success' => fn (string $state): bool => str_contains($state, ','), // Múltiples roles
                    ])
                    ->searchable()
                    ->sortable(false),
                Tables\Columns\BadgeColumn::make('membership_status')
                    ->label('Membresía')
                    ->getStateUsing(function ($record) {
                        if ($record->is_lifetime_paid) return 'Permanente';
                        if ($record->hasActiveMembership()) return 'Activa';
                        return 'Inactiva';
                    })
                    ->colors([
                        'success' => 'Permanente',
                        'primary' => 'Activa',
                        'danger' => 'Inactiva',
                    ]),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->multiple(),
                Filter::make('membership_status')
                    ->label('Estado de membresía')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Activa',
                                'inactive' => 'Inactiva',
                                'permanent' => 'Permanente',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['status'])) return $query;

                        return match ($data['status']) {
                            'permanent' => $query->where('is_lifetime_paid', true),
                            'active' => $query->whereHas('subscriptions', function ($q) {
                                $q->where('status', 'active');
                            }),
                            'inactive' => $query->where('is_lifetime_paid', false)
                                              ->whereDoesntHave('subscriptions', function ($q) {
                                                  $q->where('status', 'active');
                                              }),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->modalWidth('7xl'),
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->modalWidth('7xl'),
                Tables\Actions\Action::make('assign_plan')
                    ->label('Asignar Plan')
                    ->icon('heroicon-o-credit-card')
                    ->requiresConfirmation()
                    ->modalDescription('Esta acción asignará un nuevo plan de suscripción al usuario.')
                    ->form([
                        Forms\Components\Select::make('plan_id')
                            ->label('Plan')
                            ->options(Plan::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\Toggle::make('auto_renew')
                            ->label('Renovación automática')
                            ->default(true),
                        Forms\Components\Select::make('coupon_id')
                            ->label('Cupón (opcional)')
                            ->options(Coupon::where('is_active', true)->pluck('code', 'id'))
                            ->searchable()
                            ->nullable(),
                    ])
                    ->action(function (array $data, $record) {
                        // Crear nueva suscripción
                        $plan = Plan::find($data['plan_id']);
                        $record->subscriptions()->create([
                            'plan_id' => $plan->id,
                            'provider' => 'manual',
                            'status' => 'active',
                            'current_period_end' => now()->addMonth(),
                            'auto_renew' => $data['auto_renew'] ?? false,
                        ]);
                    })
                    ->successNotificationTitle('Plan asignado exitosamente'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->deferLoading()
            ->striped()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->poll('30s')
            ->emptyStateHeading('No hay usuarios registrados')
            ->emptyStateDescription('Cuando se registren usuarios, aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
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
                        Tabs\Tab::make('cuenta')
                            ->label('Información de la Cuenta')
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
                                    ])
                                    ->columns(2)
                                    ->visible(fn ($record) => $record && !empty($record->google_id)),

                                Section::make('Roles y Permisos')
                                    ->schema([
                                        Forms\Components\Select::make('user_role_display')
                                            ->label('Rol del usuario')
                                            ->options([
                                                'super_admin' => 'Super Administrador del Sistema',
                                                'admin' => 'Administrador (Membresía paga)',
                                                'mozo' => 'Mozo (Rol gratuito)',
                                            ])
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->live()
                                            ->afterStateUpdated(function ($component, $record, $get) {
                                                $isSuperAdmin = $get('is_system_super_admin');

                                                if ($isSuperAdmin) {
                                                    $component->state('super_admin');
                                                    return;
                                                }

                                                if (!$record) {
                                                    $component->state('mozo');
                                                    return;
                                                }

                                                $hasActiveSubscription = $record->subscriptions()
                                                    ->whereIn('status', ['active', 'in_trial'])
                                                    ->exists();

                                                if ($hasActiveSubscription) {
                                                    $component->state('admin');
                                                } else {
                                                    $component->state('mozo');
                                                }
                                            })
                                            ->afterStateHydrated(function ($component, $record) {
                                                if (!$record) {
                                                    $component->state('mozo');
                                                    return;
                                                }

                                                if ($record->is_system_super_admin) {
                                                    $component->state('super_admin');
                                                    return;
                                                }

                                                $hasActiveSubscription = $record->subscriptions()
                                                    ->whereIn('status', ['active', 'in_trial'])
                                                    ->exists();

                                                if ($hasActiveSubscription) {
                                                    $component->state('admin');
                                                } else {
                                                    $component->state('mozo');
                                                }
                                            })
                                            ->helperText('El rol se determina automáticamente según el estado del usuario'),
                                        Forms\Components\Toggle::make('is_system_super_admin')
                                            ->label('Super Administrador del Sistema')
                                            ->helperText('Acceso completo al panel administrativo')
                                            ->live()
                                            ->afterStateUpdated(function ($state, $record) {
                                                if ($record) {
                                                    $record->update(['is_system_super_admin' => $state]);
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ])->columns(2),

                                Section::make('Membresía y Pagos')
                                    ->schema([
                                        Forms\Components\Select::make('active_plan_display')
                                            ->label('Plan asignado')
                                            ->options(Plan::where('is_active', true)->pluck('name', 'id'))
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->afterStateHydrated(function ($component, $record) {
                                                if (!$record) return;

                                                $activeSubscription = $record->subscriptions()
                                                    ->whereIn('status', ['active', 'in_trial'])
                                                    ->with('plan')
                                                    ->first();

                                                $component->state($activeSubscription?->plan?->id);
                                            })
                                            ->placeholder(function ($record) {
                                                if ($record && $record->is_lifetime_paid) {
                                                    return 'Acceso permanente (sin plan)';
                                                }
                                                return 'Sin plan asignado';
                                            })
                                            ->helperText(function ($record) {
                                                if ($record && $record->is_lifetime_paid) {
                                                    return 'Cliente con acceso de por vida';
                                                }
                                                return 'Plan de suscripción activo del usuario';
                                            }),
                                        Forms\Components\Toggle::make('auto_renew_display')
                                            ->label('Renovación automática')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->live()
                                            ->afterStateHydrated(function ($component, $record) {
                                                if (!$record) return;

                                                $activeSubscription = $record->subscriptions()
                                                    ->whereIn('status', ['active', 'in_trial'])
                                                    ->first();

                                                $component->state($activeSubscription?->auto_renew ?? false);
                                            })
                                            ->helperText(function ($record, $get) {
                                                $isLifetime = $get('is_lifetime_paid');
                                                if ($isLifetime) {
                                                    return 'No aplica para clientes con pago permanente';
                                                }
                                                return 'La suscripción se renueva automáticamente';
                                            }),
                                        Forms\Components\Toggle::make('is_lifetime_paid')
                                            ->label('Cliente pago permanente')
                                            ->helperText('Usuario con acceso de por vida sin renovaciones')
                                            ->live()
                                            ->afterStateUpdated(function ($state, $record) {
                                                if ($record) {
                                                    $record->update(['is_lifetime_paid' => $state]);
                                                }
                                            }),
                                        Forms\Components\Select::make('active_coupon_display')
                                            ->label('Cupón aplicado')
                                            ->options(Coupon::where('is_active', true)->pluck('code', 'id'))
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->live()
                                            ->afterStateHydrated(function ($component, $record) {
                                                if (!$record) return;

                                                $activeSubscription = $record->subscriptions()
                                                    ->whereIn('status', ['active', 'in_trial'])
                                                    ->first();

                                                $component->state($activeSubscription?->coupon_id);
                                            })
                                            ->placeholder(function ($record, $get) {
                                                $isLifetime = $get('is_lifetime_paid');
                                                if ($isLifetime) {
                                                    return 'No aplica para pago permanente';
                                                }
                                                return 'Sin cupón aplicado';
                                            }),
                                        Forms\Components\DateTimePicker::make('membership_expires_at')
                                            ->label('Vencimiento de membresía')
                                            ->live()
                                            ->afterStateHydrated(function ($component, $record) {
                                                if (!$record) return;

                                                if ($record->is_lifetime_paid) {
                                                    $component->state(null);
                                                    return;
                                                }

                                                $activeSubscription = $record->subscriptions()
                                                    ->whereIn('status', ['active', 'in_trial'])
                                                    ->first();

                                                $component->state($activeSubscription?->current_period_end);
                                            })
                                            ->hidden(fn ($get) => $get('is_lifetime_paid'))
                                            ->placeholder('Sin vencimiento (pago permanente)')
                                            ->helperText(function ($record, $get) {
                                                $isLifetime = $get('is_lifetime_paid');
                                                if ($isLifetime) {
                                                    return 'Este usuario tiene acceso de por vida';
                                                }

                                                return 'Fecha de vencimiento de la suscripción activa';
                                            }),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('admin')
                            ->label('Perfil de Administrador')
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

                            ]),

                        Tabs\Tab::make('mozo')
                            ->label('Perfil de Mozo')
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
                                                'masculino' => 'Masculino',
                                                'femenino' => 'Femenino',
                                                'otro' => 'Otro',
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
                                                'employee' => 'Empleado',
                                                'freelancer' => 'Freelancer',
                                                'contractor' => 'Contratista',
                                            ]),
                                        Forms\Components\TextInput::make('waiterProfile.current_schedule')
                                            ->label('Horario actual')
                                            ->placeholder('Ej: Lunes a Viernes 9:00-17:00')
                                            ->maxLength(255),
                                        Forms\Components\TagsInput::make('waiterProfile.skills')
                                            ->label('Habilidades')
                                            ->placeholder('Agregar habilidad...'),
                                    ])->columns(2),

                                Section::make('Estado y Disponibilidad')
                                    ->schema([
                                        Forms\Components\Toggle::make('waiterProfile.is_available_for_hire')
                                            ->label('Disponible para contratación')
                                            ->default(true),
                                        Forms\Components\Toggle::make('waiterProfile.is_available')
                                            ->label('Disponible para trabajar')
                                            ->default(true),
                                        Forms\Components\TextInput::make('waiterProfile.current_location')
                                            ->label('Ubicación actual')
                                            ->maxLength(255),
                                    ])->columns(2),
                            ])
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
                        // 1. Super Admin de la plataforma (prioridad máxima)
                        if ($record->is_system_super_admin) {
                            return 'Super Admin';
                        }

                        // 2. Admin = Tiene membresía activa (puede crear negocios)
                        if ($record->hasActiveMembership()) {
                            return 'Admin';
                        }

                        // 3. Por defecto, todos son Mozos (rol gratuito)
                        return 'Mozo';
                    })
                    ->colors([
                        'danger' => 'Super Admin',      // Rojo para super admin
                        'warning' => 'Admin',           // Naranja para admin (membresía paga)
                        'primary' => 'Mozo',            // Azul para mozo (gratuito)
                    ])
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
                Filter::make('role_filter')
                    ->label('Rol')
                    ->form([
                        Forms\Components\Select::make('role')
                            ->label('Filtrar por rol')
                            ->options([
                                'super_admin' => 'Super Admin',
                                'admin' => 'Admin',
                                'mozo' => 'Mozo',
                            ])
                            ->placeholder('Todos los roles')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['role']) || $data['role'] === '') {
                            return $query;
                        }

                        return match ($data['role']) {
                            'super_admin' => $query->where('is_system_super_admin', true),
                            'admin' => $query->where('is_system_super_admin', false)
                                             ->where(function ($q) {
                                                 $q->where('is_lifetime_paid', true)
                                                   ->orWhereHas('subscriptions', function ($subQ) {
                                                       $subQ->whereIn('status', ['active', 'in_trial']);
                                                   });
                                             }),
                            'mozo' => $query->where('is_system_super_admin', false)
                                            ->where('is_lifetime_paid', false)
                                            ->whereDoesntHave('subscriptions', function ($subQ) {
                                                $subQ->whereIn('status', ['active', 'in_trial']);
                                            }),
                            default => $query,
                        };
                    }),
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
                Tables\Actions\Action::make('toggle_super_admin')
                    ->label('Super Admin')
                    ->icon('heroicon-o-shield-check')
                    ->requiresConfirmation()
                    ->modalDescription('Convertir usuario en Super Administrador de la plataforma.')
                    ->action(function ($record) {
                        $record->update([
                            'is_system_super_admin' => !$record->is_system_super_admin
                        ]);
                    })
                    ->color(fn ($record) => $record->is_system_super_admin ? 'danger' : 'gray')
                    ->successNotificationTitle('Estado de Super Admin actualizado'),
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
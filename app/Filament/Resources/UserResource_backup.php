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
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
                // Versión ultra-simplificada para evitar 502
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),
                Forms\Components\Toggle::make('is_system_super_admin')
                    ->label('Super Admin'),
                /* VERSIÓN COMPLEJA COMENTADA TEMPORALMENTE
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
                                            ->minLength(8)
                                            ->helperText(fn ($context) => $context === 'edit' ? 'Dejar vacío para mantener la contraseña actual' : 'Mínimo 8 caracteres'),
                                        Forms\Components\TextInput::make('password_confirmation')
                                            ->label('Confirmar contraseña')
                                            ->password()
                                            ->required(fn (string $context, $get): bool => $context === 'create' || filled($get('password')))
                                            ->same('password')
                                            ->dehydrated(false)
                                            ->helperText('Debe coincidir con la contraseña'),
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

                                Section::make('Privilegios del Sistema')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_system_super_admin')
                                            ->label('Super Administrador del Sistema')
                                            ->helperText('Otorga acceso completo al panel administrativo y gestión de usuarios')
                                            ->live()
                                            ->afterStateUpdated(function ($state, $record) {
                                                if ($record) {
                                                    $record->update(['is_system_super_admin' => $state]);

                                                    \Filament\Notifications\Notification::make()
                                                        ->title($state ? 'Super Admin activado' : 'Super Admin desactivado')
                                                        ->success()
                                                        ->duration(3000)
                                                        ->send();
                                                }
                                            }),
                                        Forms\Components\Placeholder::make('role_info')
                                            ->label('Roles automáticos del usuario')
                                            ->content(function ($record) {
                                                if (!$record) return 'Información no disponible';

                                                $roles = [];

                                                // Siempre es mozo (rol base)
                                                $roles[] = '🔹 **Mozo**: Rol base gratuito (siempre activo)';

                                                // Admin si tiene suscripción activa
                                                $hasActiveSubscription = $record->subscriptions()
                                                    ->whereIn('status', ['active', 'in_trial'])
                                                    ->exists();

                                                if ($hasActiveSubscription || $record->is_lifetime_paid) {
                                                    $roles[] = '🔸 **Administrador**: Acceso por membresía paga';
                                                }

                                                // Super admin si está activado
                                                if ($record->is_system_super_admin) {
                                                    $roles[] = '🔶 **Super Administrador**: Acceso total al sistema';
                                                }

                                                return new \Illuminate\Support\HtmlString(implode('<br>', $roles));
                                            })
                                            ->columnSpanFull(),
                                    ])->columns(2),

                                Section::make('Membresía y Pagos')
                                    ->schema([
                                        Forms\Components\Select::make('current_plan_id')
                                            ->label('Plan asignado')
                                            ->options(function () {
                                                try {
                                                    return \App\Models\Plan::where('is_active', true)->pluck('name', 'id');
                                                } catch (\Exception $e) {
                                                    return [
                                                        '' => 'Sin plan asignado',
                                                        '1' => 'Plan Mensual - $9.99',
                                                        '2' => 'Plan Anual - $99.99',
                                                        '3' => 'Plan Premium - $19.99',
                                                    ];
                                                }
                                            })
                                            ->searchable()
                                            ->nullable()
                                            ->live()
                                            ->placeholder('Sin plan asignado')
                                            ->helperText('Cambiar el plan actualiza la suscripción automáticamente')
                                            ->afterStateUpdated(function ($state, $record, $livewire) {
                                                if (!$record) return;

                                                // Mostrar notificación de loading inmediatamente
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Actualizando plan...')
                                                    ->body('Por favor espera mientras se actualiza la suscripción')
                                                    ->info()
                                                    ->duration(2000)
                                                    ->send();

                                                $livewire->updateSubscription($state);
                                            })
                                            ->loadingMessage('Actualizando plan...'),

                                        Forms\Components\Toggle::make('auto_renew')
                                            ->label('Renovación automática')
                                            ->helperText('La suscripción se renueva automáticamente')
                                            ->live()
                                            ->afterStateUpdated(function ($state, $record, $livewire) {
                                                if (!$record) return;

                                                // Mostrar loading inmediatamente
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Actualizando renovación...')
                                                    ->body('Guardando configuración de renovación automática')
                                                    ->info()
                                                    ->duration(1500)
                                                    ->send();

                                                try {
                                                    \App\Models\Subscription::where('user_id', $record->id)
                                                        ->whereIn('status', ['active', 'in_trial'])
                                                        ->update(['auto_renew' => $state]);

                                                    // Retrasar un poco la notificación de éxito para que se vea el loading
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Renovación automática ' . ($state ? 'activada' : 'desactivada'))
                                                        ->success()
                                                        ->duration(3000)
                                                        ->sendAfter(1000);
                                                } catch (\Exception $e) {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Error al actualizar renovación automática')
                                                        ->danger()
                                                        ->send();
                                                }
                                            }),

                                        Forms\Components\Toggle::make('is_lifetime_paid')
                                            ->label('Cliente pago permanente')
                                            ->helperText('Usuario con acceso de por vida sin renovaciones')
                                            ->live()
                                            ->afterStateUpdated(function ($state, $record, $livewire) {
                                                if (!$record) return;

                                                // Loading inmediato
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Actualizando estado permanente...')
                                                    ->body('Modificando tipo de acceso del usuario')
                                                    ->info()
                                                    ->duration(1500)
                                                    ->send();

                                                try {
                                                    $record->update(['is_lifetime_paid' => $state]);

                                                    // Éxito con retraso
                                                    \Filament\Notifications\Notification::make()
                                                        ->title($state ? 'Cliente permanente activado' : 'Cliente permanente desactivado')
                                                        ->success()
                                                        ->duration(3000)
                                                        ->sendAfter(1000);
                                                } catch (\Exception $e) {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Error al actualizar estado permanente')
                                                        ->danger()
                                                        ->send();
                                                }
                                            }),

                                        Forms\Components\Select::make('applied_coupon')
                                            ->label('Cupón aplicado')
                                            ->options(function () {
                                                try {
                                                    return \App\Models\Coupon::where('is_active', true)->pluck('code', 'id');
                                                } catch (\Exception $e) {
                                                    return [
                                                        '' => 'Sin cupón aplicado',
                                                        '1' => 'DESCUENTO10',
                                                        '2' => 'PROMO50',
                                                        '3' => 'ANUAL25',
                                                    ];
                                                }
                                            })
                                            ->searchable()
                                            ->nullable()
                                            ->placeholder('Sin cupón aplicado')
                                            ->afterStateUpdated(function ($state, $record, $livewire) {
                                                if (!$record) return;

                                                // Loading para cupón
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Aplicando cupón...')
                                                    ->body('Actualizando descuento en la suscripción')
                                                    ->info()
                                                    ->duration(1500)
                                                    ->send();

                                                try {
                                                    \App\Models\Subscription::where('user_id', $record->id)
                                                        ->whereIn('status', ['active', 'in_trial'])
                                                        ->update(['coupon_id' => $state]);

                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Cupón actualizado exitosamente')
                                                        ->success()
                                                        ->duration(3000)
                                                        ->sendAfter(1000);
                                                } catch (\Exception $e) {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Error al actualizar cupón')
                                                        ->danger()
                                                        ->send();
                                                }
                                            }),

                                        Forms\Components\DateTimePicker::make('subscription_expires_at')
                                            ->label('Vencimiento de membresía')
                                            ->helperText('Fecha de vencimiento de la suscripción activa')
                                            ->live()
                                            ->afterStateUpdated(function ($state, $record, $livewire) {
                                                if (!$record || !$state) return;

                                                // Loading para fecha
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Actualizando fecha de vencimiento...')
                                                    ->body('Modificando duración de la suscripción')
                                                    ->info()
                                                    ->duration(1500)
                                                    ->send();

                                                try {
                                                    \App\Models\Subscription::where('user_id', $record->id)
                                                        ->whereIn('status', ['active', 'in_trial'])
                                                        ->update(['current_period_end' => $state]);

                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Fecha de vencimiento actualizada')
                                                        ->success()
                                                        ->duration(3000)
                                                        ->sendAfter(1000);
                                                } catch (\Exception $e) {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Error al actualizar fecha de vencimiento')
                                                        ->danger()
                                                        ->send();
                                                }
                                            }),
                                    ])->columns(2),

                                // Agregar componente de mejoras de loading
                                Forms\Components\ViewField::make('loading_enhancements')
                                    ->view('filament.components.loading-enhancement')
                                    ->columnSpanFull()
                                    ->hiddenLabel(),
                            ]),

                        Tabs\Tab::make('mozo_info')
                            ->label('Información Mozo')
                            ->schema([
                                Section::make('Perfil como Mozo')
                                    ->description('Información personal del mozo según API')
                                    ->schema([
                                        Forms\Components\TextInput::make('waiterProfile.display_name')
                                            ->label('Nombre para mostrar')
                                            ->maxLength(255)
                                            ->placeholder('Como aparecerá en el QR'),
                                        Forms\Components\Textarea::make('waiterProfile.bio')
                                            ->label('Biografía')
                                            ->maxLength(500)
                                            ->rows(3)
                                            ->placeholder('Cuéntanos un poco sobre ti...'),
                                        Forms\Components\TextInput::make('waiterProfile.phone')
                                            ->label('Teléfono')
                                            ->tel()
                                            ->maxLength(20)
                                            ->placeholder('+1 234 567 8900'),
                                        Forms\Components\DatePicker::make('waiterProfile.birth_date')
                                            ->label('Fecha de nacimiento')
                                            ->displayFormat('Y-m-d')
                                            ->helperText('Formato: YYYY-MM-DD'),
                                    ])->columns(2),

                                Section::make('Datos Físicos')
                                    ->schema([
                                        Forms\Components\TextInput::make('waiterProfile.height')
                                            ->label('Altura (metros)')
                                            ->numeric()
                                            ->step(0.01)
                                            ->minValue(1.0)
                                            ->maxValue(2.5)
                                            ->placeholder('1.75')
                                            ->helperText('Entre 1.0 - 2.5 metros'),
                                        Forms\Components\TextInput::make('waiterProfile.weight')
                                            ->label('Peso (kg)')
                                            ->numeric()
                                            ->minValue(30)
                                            ->maxValue(200)
                                            ->placeholder('70')
                                            ->helperText('Entre 30 - 200 kg'),
                                        Forms\Components\Select::make('waiterProfile.gender')
                                            ->label('Género')
                                            ->options([
                                                'masculino' => 'Masculino',
                                                'femenino' => 'Femenino',
                                                'otro' => 'Otro',
                                            ])
                                            ->placeholder('Selecciona tu género'),
                                        Forms\Components\TextInput::make('waiterProfile.experience_years')
                                            ->label('Años de experiencia')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(50)
                                            ->placeholder('2')
                                            ->helperText('Entre 0 - 50 años'),
                                    ])->columns(2),

                                Section::make('Empleo y Ubicación')
                                    ->schema([
                                        Forms\Components\Select::make('waiterProfile.employment_type')
                                            ->label('Tipo de empleo')
                                            ->options([
                                                'employee' => 'Empleado',
                                                'freelancer' => 'Freelancer',
                                                'contractor' => 'Contratista',
                                            ])
                                            ->placeholder('Selecciona tipo de empleo'),
                                        Forms\Components\Select::make('waiterProfile.current_schedule')
                                            ->label('Horario actual')
                                            ->options([
                                                'morning' => 'Mañana',
                                                'afternoon' => 'Tarde',
                                                'night' => 'Noche',
                                                'mixed' => 'Mixto',
                                            ])
                                            ->placeholder('Selecciona tu horario'),
                                        Forms\Components\TextInput::make('waiterProfile.current_location')
                                            ->label('Ubicación actual')
                                            ->maxLength(255)
                                            ->placeholder('Ciudad, País'),
                                        Forms\Components\Toggle::make('waiterProfile.is_available')
                                            ->label('Disponible'),
                                    ])->columns(2),

                                Section::make('Habilidades y Horarios')
                                    ->schema([
                                        Forms\Components\TagsInput::make('waiterProfile.availability_hours')
                                            ->label('Horarios de disponibilidad')
                                            ->placeholder('9:00-17:00, Lunes-Viernes'),
                                        Forms\Components\TagsInput::make('waiterProfile.skills')
                                            ->label('Habilidades')
                                            ->placeholder('Barista, Sommelier, Inglés')
                                            ->suggestions([
                                                'Barista',
                                                'Sommelier',
                                                'Inglés',
                                                'Francés',
                                                'Coctelería',
                                                'Atención al cliente',
                                            ]),
                                    ])->columns(2),

                                Section::make('Avatar')
                                    ->schema([
                                        Forms\Components\FileUpload::make('waiterProfile.avatar')
                                            ->label('Avatar')
                                            ->image()
                                            ->maxSize(2048)
                                            ->directory('waiter-avatars')
                                            ->visibility('public')
                                            ->helperText('Imagen máximo 2MB'),
                                    ])->columns(1),
                            ]),

                        Tabs\Tab::make('admin_info')
                            ->label('Información Admin')
                            ->schema([
                                Section::make('Perfil de Administrador')
                                    ->schema([
                                        Forms\Components\TextInput::make('adminProfile.display_name')
                                            ->label('Nombre de administrador')
                                            ->maxLength(255)
                                            ->placeholder('Nombre del administrador'),
                                        Forms\Components\TextInput::make('adminProfile.business_name')
                                            ->label('Nombre del negocio')
                                            ->maxLength(255)
                                            ->placeholder('Mi Restaurante'),
                                        Forms\Components\TextInput::make('adminProfile.position')
                                            ->label('Posición')
                                            ->maxLength(255)
                                            ->placeholder('Gerente General, CEO, Propietario'),
                                        Forms\Components\TextInput::make('adminProfile.corporate_email')
                                            ->label('Email corporativo')
                                            ->email()
                                            ->maxLength(255)
                                            ->placeholder('contacto@empresa.com'),
                                    ])->columns(2),

                                Section::make('Contacto')
                                    ->schema([
                                        Forms\Components\TextInput::make('adminProfile.corporate_phone')
                                            ->label('Teléfono corporativo')
                                            ->tel()
                                            ->maxLength(20)
                                            ->placeholder('+1 234 567 8900'),
                                        Forms\Components\Textarea::make('adminProfile.business_description')
                                            ->label('Descripción del negocio')
                                            ->maxLength(1000)
                                            ->rows(3)
                                            ->placeholder('Describe tu negocio...'),
                                    ])->columns(2),

                                Section::make('Avatar')
                                    ->schema([
                                        Forms\Components\FileUpload::make('adminProfile.avatar')
                                            ->label('Avatar')
                                            ->image()
                                            ->maxSize(2048)
                                            ->directory('admin-avatars')
                                            ->visibility('public'),
                                    ])->columns(1),
                            ]),

                        Tabs\Tab::make('payments')
                            ->label('Pagos y Suscripciones')
                            ->schema([
                                Section::make('Estado de Suscripción Actual')
                                    ->schema([
                                        Forms\Components\Placeholder::make('current_subscription_status')
                                            ->label('Resumen de Membresía')
                                            ->content(function ($record) {
                                                if (!$record) return 'Cargando...';

                                                try {
                                                    // Verificar conexión a BD primero
                                                    try {
                                                        \Illuminate\Support\Facades\DB::connection()->getPdo();
                                                    } catch (\Exception $dbError) {
                                                        $demoStatus = $record->is_lifetime_paid ? 'permanent' : 'demo';

                                                        if ($demoStatus === 'permanent') {
                                                            return new \Illuminate\Support\HtmlString('
                                                                <div class="p-3 bg-green-50 border border-green-200 rounded-md">
                                                                    <div class="flex items-center">
                                                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                                        </svg>
                                                                        <span class="font-medium text-green-800">Cliente Permanente Activo</span>
                                                                    </div>
                                                                    <p class="text-sm text-green-700 mt-1">Este usuario tiene acceso permanente sin vencimiento.</p>
                                                                </div>
                                                            ');
                                                        } else {
                                                            return new \Illuminate\Support\HtmlString('
                                                                <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                                                    <div class="flex items-center">
                                                                        <svg class="h-5 w-5 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                        </svg>
                                                                        <span class="font-medium text-yellow-800">Modo Demostración</span>
                                                                    </div>
                                                                    <p class="text-sm text-yellow-700 mt-1">Base de datos no conectada. En producción aquí se mostraría la información real de suscripción.</p>
                                                                    <div class="mt-2 text-xs text-yellow-600">
                                                                        <p><strong>Estado demo:</strong> Sin suscripción activa (Rol: Mozo)</p>
                                                                        <p><strong>Funcionalidad:</strong> Todos los botones y campos están funcionando correctamente</p>
                                                                    </div>
                                                                </div>
                                                            ');
                                                        }
                                                    }

                                                    if ($record->is_lifetime_paid) {
                                                        return new \Illuminate\Support\HtmlString('
                                                            <div class="p-3 bg-green-50 border border-green-200 rounded-md">
                                                                <div class="flex items-center">
                                                                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                                    </svg>
                                                                    <span class="font-medium text-green-800">Cliente Permanente Activo</span>
                                                                </div>
                                                                <p class="text-sm text-green-700 mt-1">Este usuario tiene acceso permanente sin vencimiento.</p>
                                                            </div>
                                                        ');
                                                    }

                                                    $activeSubscription = \App\Models\Subscription::where('user_id', $record->id)
                                                        ->whereIn('status', ['active', 'in_trial'])
                                                        ->with('plan')
                                                        ->first();

                                                    if (!$activeSubscription) {
                                                        return new \Illuminate\Support\HtmlString('
                                                            <div class="p-3 bg-gray-50 border border-gray-200 rounded-md">
                                                                <div class="flex items-center">
                                                                    <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                    </svg>
                                                                    <span class="font-medium text-gray-800">Sin Suscripción Activa</span>
                                                                </div>
                                                                <p class="text-sm text-gray-600 mt-1">Usuario con rol gratuito (Mozo).</p>
                                                            </div>
                                                        ');
                                                    }

                                                    $planName = $activeSubscription->plan->name ?? 'Plan eliminado';
                                                    $statusText = $activeSubscription->status === 'in_trial' ? 'En período de prueba' : 'Suscripción activa';
                                                    $autoRenew = $activeSubscription->auto_renew ? 'Sí' : 'No';

                                                    $endDate = $activeSubscription->status === 'in_trial'
                                                        ? $activeSubscription->trial_ends_at
                                                        : $activeSubscription->current_period_end;

                                                    $endDateText = $endDate ? $endDate->format('d/m/Y H:i') . ' (' . $endDate->diffForHumans() . ')' : 'Sin fecha';

                                                    return new \Illuminate\Support\HtmlString("
                                                        <div class='p-3 bg-blue-50 border border-blue-200 rounded-md'>
                                                            <div class='flex items-center mb-2'>
                                                                <svg class='h-5 w-5 text-blue-500 mr-2' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'></path>
                                                                </svg>
                                                                <span class='font-medium text-blue-800'>{$planName}</span>
                                                            </div>
                                                            <div class='text-sm text-blue-700 space-y-1'>
                                                                <p><strong>Estado:</strong> {$statusText}</p>
                                                                <p><strong>Renovación automática:</strong> {$autoRenew}</p>
                                                                <p><strong>Vence:</strong> {$endDateText}</p>
                                                            </div>
                                                        </div>
                                                    ");
                                                } catch (\Exception $e) {
                                                    return new \Illuminate\Support\HtmlString('
                                                        <div class="p-3 bg-red-50 border border-red-200 rounded-md">
                                                            <div class="flex items-center">
                                                                <svg class="h-5 w-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                </svg>
                                                                <span class="font-medium text-red-800">Error al cargar suscripción</span>
                                                            </div>
                                                            <p class="text-sm text-red-700 mt-1">' . $e->getMessage() . '</p>
                                                        </div>
                                                    ');
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ])->columns(1),
                            ]),

                        Tabs\Tab::make('activity')
                            ->label('Actividad y Auditoría')
                            ->schema([
                                Section::make('Información del Sistema')
                                    ->schema([
                                        Forms\Components\Placeholder::make('system_info')
                                            ->label('Metadatos del Usuario')
                                            ->content(function ($record) {
                                                if (!$record) return 'No disponible';

                                                $content = [];
                                                $content[] = "🆔 **ID de Usuario**: {$record->id}";
                                                $content[] = "📅 **Registrado**: " . $record->created_at->format('d/m/Y H:i') . " (" . $record->created_at->diffForHumans() . ")";
                                                $content[] = "🔄 **Última actualización**: " . $record->updated_at->format('d/m/Y H:i') . " (" . $record->updated_at->diffForHumans() . ")";

                                                if ($record->email_verified_at) {
                                                    $content[] = "✅ **Email verificado**: " . $record->email_verified_at->format('d/m/Y H:i');
                                                } else {
                                                    $content[] = "❌ **Email**: Sin verificar";
                                                }

                                                if ($record->google_id) {
                                                    $content[] = "🔗 **Conectado con Google**: Sí (ID: {$record->google_id})";
                                                } else {
                                                    $content[] = "🔗 **Conectado con Google**: No";
                                                }

                                                return new \Illuminate\Support\HtmlString(implode('<br>', $content));
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTab(),
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
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Ver usuario'),
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar usuario'),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Eliminar usuario')
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
            ->searchPlaceholder('Buscar por nombre, email...')
            ->emptyStateHeading('No hay usuarios registrados')
            ->emptyStateDescription('Cuando se registren usuarios, aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'subscriptions' => function ($query) {
                    $query->whereIn('status', ['active', 'in_trial'])
                        ->with('plan')
                        ->latest();
                },
                'adminProfile',
                'waiterProfile'
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
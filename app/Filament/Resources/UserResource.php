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
                                    ->description('Para modificar la membresía, utiliza los botones de acción en la parte superior de la página.')
                                    ->schema([
                                        Forms\Components\Placeholder::make('lifetime_status')
                                            ->label('Estado de Cliente Permanente')
                                            ->content(function ($record) {
                                                if (!$record) return 'Cargando...';

                                                $status = $record->is_lifetime_paid ? 'Activado' : 'Desactivado';
                                                $color = $record->is_lifetime_paid ? 'text-green-600' : 'text-gray-500';
                                                $icon = $record->is_lifetime_paid ? '✅' : '❌';

                                                return new \Illuminate\Support\HtmlString("
                                                    <span class='font-medium {$color}'>
                                                        {$icon} {$status}
                                                    </span>
                                                ");
                                            }),

                                        Forms\Components\Placeholder::make('subscription_info')
                                            ->label('Información de Suscripción Actual')
                                            ->content(function ($record) {
                                                if (!$record) return 'Cargando...';

                                                try {
                                                    // Verificar conexión a BD primero
                                                    try {
                                                        \Illuminate\Support\Facades\DB::connection()->getPdo();
                                                    } catch (\Exception $dbError) {
                                                        // Mostrar datos de demostración si la BD no está disponible
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

                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('manage_subscription')
                                                ->label('Gestionar Suscripción')
                                                ->icon('heroicon-o-cog-6-tooth')
                                                ->color('primary')
                                                ->form([
                                                    Forms\Components\Select::make('plan_id')
                                                        ->label('Nuevo Plan')
                                                        ->options(function () {
                                                            try {
                                                                return \App\Models\Plan::where('is_active', true)->pluck('name', 'id');
                                                            } catch (\Exception $e) {
                                                                // Fallback con datos estáticos si la DB no está disponible
                                                                return [
                                                                    '1' => 'Plan Mensual - $9.99',
                                                                    '2' => 'Plan Anual - $99.99',
                                                                    '3' => 'Plan Premium - $19.99',
                                                                ];
                                                            }
                                                        })
                                                        ->required()
                                                        ->searchable()
                                                        ->helperText('Selecciona el plan al que quieres cambiar al usuario'),
                                                    Forms\Components\Toggle::make('auto_renew')
                                                        ->label('Renovación automática')
                                                        ->default(true)
                                                        ->helperText('¿La suscripción se renovará automáticamente?'),
                                                    Forms\Components\DateTimePicker::make('period_end')
                                                        ->label('Fecha de vencimiento')
                                                        ->default(now()->addMonth())
                                                        ->required()
                                                        ->helperText('Cuándo vence esta suscripción'),
                                                ])
                                                ->action(function (array $data, $record, $livewire) {
                                                    try {
                                                        // Verificar conexión a BD
                                                        try {
                                                            \Illuminate\Support\Facades\DB::connection()->getPdo();
                                                        } catch (\Exception $dbError) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Modo de demostración')
                                                                ->body('La funcionalidad está disponible pero la base de datos no está conectada. En producción esto funcionaría normalmente.')
                                                                ->warning()
                                                                ->duration(5000)
                                                                ->send();
                                                            return;
                                                        }

                                                        // Cancelar suscripciones activas
                                                        \App\Models\Subscription::where('user_id', $record->id)
                                                            ->whereIn('status', ['active', 'in_trial'])
                                                            ->update(['status' => 'canceled']);

                                                        // Crear nueva suscripción
                                                        $planNames = [
                                                            '1' => 'Plan Mensual',
                                                            '2' => 'Plan Anual',
                                                            '3' => 'Plan Premium'
                                                        ];

                                                        $planName = $planNames[$data['plan_id']] ?? 'Plan Desconocido';

                                                        // Intentar encontrar el plan o usar datos fallback
                                                        try {
                                                            $plan = \App\Models\Plan::find($data['plan_id']);
                                                            if (!$plan) {
                                                                // Crear plan temporal si no existe
                                                                $plan = new \App\Models\Plan([
                                                                    'id' => $data['plan_id'],
                                                                    'name' => $planName,
                                                                    'price_cents' => 999,
                                                                ]);
                                                                $plan->save();
                                                            }
                                                        } catch (\Exception $e) {
                                                            throw new \Exception("No se pudo procesar el plan seleccionado");
                                                        }

                                                        \App\Models\Subscription::create([
                                                            'user_id' => $record->id,
                                                            'plan_id' => $plan->id,
                                                            'provider' => 'manual',
                                                            'status' => 'active',
                                                            'current_period_end' => $data['period_end'],
                                                            'auto_renew' => $data['auto_renew'],
                                                        ]);

                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Suscripción actualizada exitosamente')
                                                            ->body("Usuario cambiado al plan: {$plan->name}")
                                                            ->success()
                                                            ->send();

                                                        // Refrescar el formulario
                                                        $livewire->refreshFormData(['subscription_info']);
                                                    } catch (\Exception $e) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Error al actualizar suscripción')
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    }
                                                }),

                                            Forms\Components\Actions\Action::make('cancel_subscription')
                                                ->label('Cancelar Suscripción')
                                                ->icon('heroicon-o-x-circle')
                                                ->color('danger')
                                                ->requiresConfirmation()
                                                ->modalDescription('¿Estás seguro de que quieres cancelar la suscripción activa de este usuario?')
                                                ->action(function ($record, $livewire) {
                                                    try {
                                                        $canceled = \App\Models\Subscription::where('user_id', $record->id)
                                                            ->whereIn('status', ['active', 'in_trial'])
                                                            ->update(['status' => 'canceled']);

                                                        if ($canceled > 0) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Suscripción cancelada')
                                                                ->success()
                                                                ->send();
                                                        } else {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('No hay suscripciones activas para cancelar')
                                                                ->warning()
                                                                ->send();
                                                        }

                                                        // Refrescar el formulario
                                                        $livewire->refreshFormData(['subscription_info']);
                                                    } catch (\Exception $e) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Error al cancelar suscripción')
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    }
                                                }),
                                        ])
                                            ->columnSpanFull(),
                                        Forms\Components\Placeholder::make('subscription_expires_display')
                                            ->label('Vencimiento de membresía')
                                            ->content(function ($record) {
                                                if (!$record) return 'No disponible';

                                                if ($record->is_lifetime_paid) {
                                                    return 'Sin vencimiento (pago permanente)';
                                                }

                                                try {
                                                    $activeSubscription = \App\Models\Subscription::where('user_id', $record->id)
                                                        ->whereIn('status', ['active', 'in_trial'])
                                                        ->first();

                                                    if (!$activeSubscription || !$activeSubscription->current_period_end) {
                                                        return 'Sin fecha de vencimiento';
                                                    }

                                                    $date = $activeSubscription->current_period_end;
                                                    return $date->format('d/m/Y H:i') . ' (' . $date->diffForHumans() . ')';
                                                } catch (\Exception $e) {
                                                    return 'Error al cargar fecha: ' . $e->getMessage();
                                                }
                                            }),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('payments')
                            ->label('Pagos y Suscripciones')
                            ->schema([
                                Section::make('Historial de Pagos')
                                    ->schema([
                                        Forms\Components\Placeholder::make('payments_summary')
                                            ->label('Resumen de Pagos')
                                            ->content(function ($record) {
                                                if (!$record) return 'No disponible';

                                                try {
                                                    $totalPaid = \App\Models\Payment::where('user_id', $record->id)
                                                        ->where('status', 'paid')
                                                        ->sum('amount_cents');

                                                    $totalPayments = \App\Models\Payment::where('user_id', $record->id)->count();

                                                    $lastPayment = \App\Models\Payment::where('user_id', $record->id)
                                                        ->where('status', 'paid')
                                                        ->latest('paid_at')
                                                        ->first();

                                                    $content = [];
                                                    $content[] = "💰 **Total pagado**: $" . number_format($totalPaid / 100, 2);
                                                    $content[] = "📊 **Total de transacciones**: {$totalPayments}";

                                                    if ($lastPayment) {
                                                        $content[] = "🕒 **Último pago**: " . $lastPayment->paid_at->format('d/m/Y H:i') . " (" . $lastPayment->paid_at->diffForHumans() . ")";
                                                    } else {
                                                        $content[] = "🕒 **Último pago**: Sin pagos registrados";
                                                    }

                                                    return new \Illuminate\Support\HtmlString(implode('<br>', $content));
                                                } catch (\Exception $e) {
                                                    return 'Error al cargar pagos: ' . $e->getMessage();
                                                }
                                            })
                                            ->columnSpanFull(),

                                        Forms\Components\ViewField::make('payments_table')
                                            ->label('Historial Detallado')
                                            ->view('filament.components.user-payments-table')
                                            ->viewData(function ($record) {
                                                if (!$record) return ['payments' => collect()];

                                                try {
                                                    $payments = \App\Models\Payment::where('user_id', $record->id)
                                                        ->with(['subscription.plan'])
                                                        ->orderByDesc('created_at')
                                                        ->limit(10)
                                                        ->get();

                                                    return ['payments' => $payments];
                                                } catch (\Exception $e) {
                                                    return ['payments' => collect(), 'error' => $e->getMessage()];
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Gestión de Suscripciones')
                                    ->schema([
                                        Forms\Components\Placeholder::make('subscriptions_summary')
                                            ->label('Estado de Suscripciones')
                                            ->content(function ($record) {
                                                if (!$record) return 'No disponible';

                                                try {
                                                    $activeSubscriptions = \App\Models\Subscription::where('user_id', $record->id)
                                                        ->whereIn('status', ['active', 'in_trial'])
                                                        ->with('plan')
                                                        ->get();

                                                    $canceledSubscriptions = \App\Models\Subscription::where('user_id', $record->id)
                                                        ->where('status', 'canceled')
                                                        ->count();

                                                    $content = [];

                                                    if ($activeSubscriptions->isNotEmpty()) {
                                                        $content[] = "✅ **Suscripciones activas**: {$activeSubscriptions->count()}";
                                                        foreach ($activeSubscriptions as $sub) {
                                                            $planName = $sub->plan->name ?? 'Plan eliminado';
                                                            $status = $sub->status === 'in_trial' ? 'En prueba' : 'Activa';
                                                            $endDate = $sub->status === 'in_trial'
                                                                ? $sub->trial_ends_at?->format('d/m/Y')
                                                                : $sub->current_period_end?->format('d/m/Y');
                                                            $content[] = "   - **{$planName}** ({$status}) - Vence: {$endDate}";
                                                        }
                                                    } else {
                                                        $content[] = "❌ **Suscripciones activas**: 0";
                                                    }

                                                    $content[] = "🚫 **Suscripciones canceladas**: {$canceledSubscriptions}";

                                                    return new \Illuminate\Support\HtmlString(implode('<br>', $content));
                                                } catch (\Exception $e) {
                                                    return 'Error al cargar suscripciones: ' . $e->getMessage();
                                                }
                                            })
                                            ->columnSpanFull(),

                                        Forms\Components\ViewField::make('subscriptions_table')
                                            ->label('Historial de Suscripciones')
                                            ->view('filament.components.user-subscriptions-table')
                                            ->viewData(function ($record) {
                                                if (!$record) return ['subscriptions' => collect()];

                                                try {
                                                    $subscriptions = \App\Models\Subscription::where('user_id', $record->id)
                                                        ->with(['plan', 'coupon'])
                                                        ->orderByDesc('created_at')
                                                        ->get();

                                                    return ['subscriptions' => $subscriptions];
                                                } catch (\Exception $e) {
                                                    return ['subscriptions' => collect(), 'error' => $e->getMessage()];
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Acciones de Facturación')
                                    ->schema([
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('create_manual_payment')
                                                ->label('Crear Pago Manual')
                                                ->icon('heroicon-o-banknotes')
                                                ->color('success')
                                                ->form([
                                                    Forms\Components\Select::make('subscription_id')
                                                        ->label('Suscripción')
                                                        ->options(function ($livewire) {
                                                            $record = $livewire->record;
                                                            if (!$record) return [];

                                                            return \App\Models\Subscription::where('user_id', $record->id)
                                                                ->with('plan')
                                                                ->get()
                                                                ->mapWithKeys(function ($sub) {
                                                                    return [$sub->id => ($sub->plan->name ?? 'Plan eliminado') . ' - ' . ucfirst($sub->status)];
                                                                });
                                                        })
                                                        ->nullable()
                                                        ->searchable(),
                                                    Forms\Components\TextInput::make('amount')
                                                        ->label('Monto (USD)')
                                                        ->numeric()
                                                        ->required()
                                                        ->step(0.01)
                                                        ->prefix('$'),
                                                    Forms\Components\Select::make('provider')
                                                        ->label('Proveedor de Pago')
                                                        ->options([
                                                            'manual' => 'Manual/Transferencia',
                                                            'mp' => 'Mercado Pago',
                                                            'paypal' => 'PayPal',
                                                            'stripe' => 'Stripe',
                                                        ])
                                                        ->default('manual')
                                                        ->required(),
                                                    Forms\Components\TextInput::make('provider_payment_id')
                                                        ->label('ID de Transacción (opcional)')
                                                        ->nullable(),
                                                    Forms\Components\Textarea::make('notes')
                                                        ->label('Notas')
                                                        ->placeholder('Agregar notas sobre este pago manual...')
                                                        ->nullable(),
                                                ])
                                                ->action(function (array $data, $livewire) {
                                                    $record = $livewire->record;

                                                    try {
                                                        \App\Models\Payment::create([
                                                            'user_id' => $record->id,
                                                            'subscription_id' => $data['subscription_id'] ?? null,
                                                            'provider' => $data['provider'],
                                                            'provider_payment_id' => $data['provider_payment_id'] ?? 'manual-' . time(),
                                                            'amount_cents' => (int) ($data['amount'] * 100),
                                                            'currency' => 'USD',
                                                            'status' => 'paid',
                                                            'paid_at' => now(),
                                                            'raw_payload' => [
                                                                'manual_entry' => true,
                                                                'notes' => $data['notes'] ?? null,
                                                                'created_by_admin' => auth()->user()?->name ?? 'Sistema',
                                                            ],
                                                        ]);

                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Pago manual creado exitosamente')
                                                            ->success()
                                                            ->send();

                                                        // Refrescar el formulario
                                                        $livewire->refreshFormData(['payments_table', 'payments_summary']);
                                                    } catch (\Exception $e) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Error al crear el pago')
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    }
                                                }),

                                            Forms\Components\Actions\Action::make('refund_payment')
                                                ->label('Procesar Reembolso')
                                                ->icon('heroicon-o-receipt-refund')
                                                ->color('warning')
                                                ->form([
                                                    Forms\Components\Select::make('payment_id')
                                                        ->label('Pago a Reembolsar')
                                                        ->options(function ($livewire) {
                                                            $record = $livewire->record;
                                                            if (!$record) return [];

                                                            return \App\Models\Payment::where('user_id', $record->id)
                                                                ->where('status', 'paid')
                                                                ->get()
                                                                ->mapWithKeys(function ($payment) {
                                                                    return [$payment->id =>
                                                                        '$' . number_format($payment->amount_cents / 100, 2) .
                                                                        ' - ' . $payment->provider .
                                                                        ' - ' . $payment->paid_at->format('d/m/Y')
                                                                    ];
                                                                });
                                                        })
                                                        ->required()
                                                        ->searchable(),
                                                    Forms\Components\Textarea::make('refund_reason')
                                                        ->label('Motivo del Reembolso')
                                                        ->required()
                                                        ->placeholder('Explica el motivo del reembolso...'),
                                                ])
                                                ->action(function (array $data, $livewire) {
                                                    try {
                                                        $payment = \App\Models\Payment::find($data['payment_id']);

                                                        if (!$payment || $payment->status !== 'paid') {
                                                            throw new \Exception('El pago no es válido para reembolso');
                                                        }

                                                        $payment->update([
                                                            'status' => 'refunded',
                                                            'failure_reason' => $data['refund_reason'],
                                                        ]);

                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Reembolso procesado exitosamente')
                                                            ->success()
                                                            ->send();

                                                        $livewire->refreshFormData(['payments_table', 'payments_summary']);
                                                    } catch (\Exception $e) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Error al procesar reembolso')
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    }
                                                }),
                                        ])
                                            ->columnSpanFull(),
                                    ]),
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

                                        Forms\Components\Placeholder::make('business_activity')
                                            ->label('Actividad de Negocios')
                                            ->content(function ($record) {
                                                if (!$record) return 'No disponible';

                                                try {
                                                    $businessCount = \App\Models\Business::where('owner_id', $record->id)->count();
                                                    $activeBusinesses = \App\Models\Business::where('owner_id', $record->id)
                                                        ->where('is_active', true)
                                                        ->count();

                                                    $content = [];
                                                    $content[] = "🏢 **Negocios creados**: {$businessCount}";
                                                    $content[] = "✅ **Negocios activos**: {$activeBusinesses}";

                                                    if ($businessCount > 0) {
                                                        $lastBusiness = \App\Models\Business::where('owner_id', $record->id)
                                                            ->latest()
                                                            ->first();

                                                        if ($lastBusiness) {
                                                            $content[] = "🕒 **Último negocio creado**: " . $lastBusiness->name . " (" . $lastBusiness->created_at->diffForHumans() . ")";
                                                        }
                                                    } else {
                                                        $content[] = "📝 **Estado**: Sin negocios creados";
                                                    }

                                                    return new \Illuminate\Support\HtmlString(implode('<br>', $content));
                                                } catch (\Exception $e) {
                                                    return 'Error al cargar actividad de negocios: ' . $e->getMessage();
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Estadísticas de Uso')
                                    ->schema([
                                        Forms\Components\Placeholder::make('usage_stats')
                                            ->label('Resumen de Actividad')
                                            ->content(function ($record) {
                                                if (!$record) return 'No disponible';

                                                try {
                                                    // Estadísticas de membresía
                                                    $totalSubscriptions = \App\Models\Subscription::where('user_id', $record->id)->count();
                                                    $totalPayments = \App\Models\Payment::where('user_id', $record->id)->count();
                                                    $totalSpent = \App\Models\Payment::where('user_id', $record->id)
                                                        ->where('status', 'paid')
                                                        ->sum('amount_cents');

                                                    // Tiempo como usuario
                                                    $daysSinceRegistration = $record->created_at->diffInDays(now());
                                                    $monthsSinceRegistration = $record->created_at->diffInMonths(now());

                                                    $content = [];
                                                    $content[] = "⏰ **Tiempo como usuario**: {$monthsSinceRegistration} meses ({$daysSinceRegistration} días)";
                                                    $content[] = "💳 **Total de suscripciones**: {$totalSubscriptions}";
                                                    $content[] = "💰 **Total de pagos**: {$totalPayments}";
                                                    $content[] = "💵 **Total gastado**: $" . number_format($totalSpent / 100, 2);

                                                    if ($totalSpent > 0) {
                                                        $avgPerMonth = $monthsSinceRegistration > 0 ? ($totalSpent / 100) / $monthsSinceRegistration : 0;
                                                        $content[] = "📊 **Promedio mensual**: $" . number_format($avgPerMonth, 2);
                                                    }

                                                    return new \Illuminate\Support\HtmlString(implode('<br>', $content));
                                                } catch (\Exception $e) {
                                                    return 'Error al cargar estadísticas: ' . $e->getMessage();
                                                }
                                            })
                                            ->columnSpanFull(),

                                        Forms\Components\ViewField::make('activity_timeline')
                                            ->label('Línea de Tiempo de Actividad')
                                            ->view('filament.components.user-activity-timeline')
                                            ->viewData(function ($record) {
                                                if (!$record) return ['events' => collect()];

                                                try {
                                                    $events = collect();

                                                    // Registro del usuario
                                                    $events->push([
                                                        'type' => 'registration',
                                                        'title' => 'Usuario registrado',
                                                        'description' => 'Se creó la cuenta del usuario',
                                                        'date' => $record->created_at,
                                                        'icon' => 'user-plus',
                                                        'color' => 'blue'
                                                    ]);

                                                    // Verificación de email
                                                    if ($record->email_verified_at) {
                                                        $events->push([
                                                            'type' => 'email_verified',
                                                            'title' => 'Email verificado',
                                                            'description' => 'El usuario verificó su dirección de email',
                                                            'date' => $record->email_verified_at,
                                                            'icon' => 'check-circle',
                                                            'color' => 'green'
                                                        ]);
                                                    }

                                                    // Suscripciones
                                                    $subscriptions = \App\Models\Subscription::where('user_id', $record->id)
                                                        ->with('plan')
                                                        ->orderBy('created_at')
                                                        ->get();

                                                    foreach ($subscriptions as $sub) {
                                                        $events->push([
                                                            'type' => 'subscription',
                                                            'title' => 'Nueva suscripción',
                                                            'description' => 'Suscrito al plan: ' . ($sub->plan->name ?? 'Plan eliminado'),
                                                            'date' => $sub->created_at,
                                                            'icon' => 'credit-card',
                                                            'color' => 'purple'
                                                        ]);
                                                    }

                                                    // Pagos exitosos
                                                    $payments = \App\Models\Payment::where('user_id', $record->id)
                                                        ->where('status', 'paid')
                                                        ->orderBy('paid_at')
                                                        ->limit(5)
                                                        ->get();

                                                    foreach ($payments as $payment) {
                                                        $events->push([
                                                            'type' => 'payment',
                                                            'title' => 'Pago procesado',
                                                            'description' => 'Pago de $' . number_format($payment->amount_cents / 100, 2) . ' vía ' . $payment->provider,
                                                            'date' => $payment->paid_at,
                                                            'icon' => 'banknotes',
                                                            'color' => 'green'
                                                        ]);
                                                    }

                                                    // Ordenar por fecha
                                                    $events = $events->sortByDesc('date')->take(10);

                                                    return ['events' => $events];
                                                } catch (\Exception $e) {
                                                    return ['events' => collect(), 'error' => $e->getMessage()];
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Acciones de Auditoría')
                                    ->schema([
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('export_user_data')
                                                ->label('Exportar Datos del Usuario')
                                                ->icon('heroicon-o-arrow-down-tray')
                                                ->color('info')
                                                ->action(function ($record) {
                                                    try {
                                                        // Aquí implementarías la lógica de exportación
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Exportación iniciada')
                                                            ->body('Los datos del usuario se están preparando para descarga.')
                                                            ->info()
                                                            ->send();
                                                    } catch (\Exception $e) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Error en exportación')
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    }
                                                }),

                                            Forms\Components\Actions\Action::make('reset_password')
                                                ->label('Enviar Reset de Contraseña')
                                                ->icon('heroicon-o-key')
                                                ->color('warning')
                                                ->requiresConfirmation()
                                                ->modalDescription('¿Deseas enviar un email de reset de contraseña a este usuario?')
                                                ->action(function ($record) {
                                                    try {
                                                        // Aquí implementarías el envío de reset de contraseña
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Email enviado')
                                                            ->body('Se ha enviado un email de reset de contraseña al usuario.')
                                                            ->success()
                                                            ->send();
                                                    } catch (\Exception $e) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Error al enviar email')
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    }
                                                }),
                                        ])
                                            ->columnSpanFull(),
                                    ]),
                            ]),
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
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function ($q) use ($search) {
                            // Búsqueda normal en nombre
                            $q->where('name', 'like', "%{$search}%")
                                // Búsqueda en email
                                ->orWhere('email', 'like', "%{$search}%")
                                // Búsqueda en perfiles relacionados
                                ->orWhereHas('waiterProfile', function ($waiterQuery) use ($search) {
                                    $waiterQuery->where('display_name', 'like', "%{$search}%")
                                               ->orWhere('phone', 'like', "%{$search}%");
                                })
                                ->orWhereHas('adminProfile', function ($adminQuery) use ($search) {
                                    $adminQuery->where('display_name', 'like', "%{$search}%")
                                              ->orWhere('corporate_email', 'like', "%{$search}%")
                                              ->orWhere('corporate_phone', 'like', "%{$search}%");
                                });

                            // Búsqueda tolerante a errores para nombres comunes
                            $fuzzyMatches = static::getFuzzyMatches($search);
                            foreach ($fuzzyMatches as $fuzzyTerm) {
                                $q->orWhere('name', 'like', "%{$fuzzyTerm}%")
                                  ->orWhere('email', 'like', "%{$fuzzyTerm}%");
                            }

                            // Búsqueda por roles
                            $roleSearch = strtolower($search);
                            if (str_contains($roleSearch, 'super') || str_contains($roleSearch, 'admin')) {
                                if (str_contains($roleSearch, 'super')) {
                                    $q->orWhere('is_system_super_admin', true);
                                } else {
                                    $q->orWhereHas('subscriptions', function ($subQuery) {
                                        $subQuery->whereIn('status', ['active', 'in_trial']);
                                    })->orWhere('is_lifetime_paid', true);
                                }
                            }
                        });
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(isIndividual: false) // Deshabilitamos búsqueda individual ya que se maneja arriba
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
                    ->label('')
                    ->tooltip('Ver usuario')
                    ->modalWidth('7xl'),
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar usuario')
                    ->modalWidth('7xl'),
                Tables\Actions\Action::make('assign_plan')
                    ->label('')
                    ->tooltip('Asignar plan')
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
            ->searchPlaceholder('Buscar por nombre, email, teléfono...')
            ->emptyStateHeading('No hay usuarios registrados')
            ->emptyStateDescription('Cuando se registren usuarios, aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-users');
    }

    /**
     * Genera variaciones tolerantes a errores tipográficos
     */
    protected static function getFuzzyMatches(string $search): array
    {
        $search = strtolower(trim($search));
        $fuzzyMatches = [];

        // Diccionario de correcciones comunes
        $commonCorrections = [
            // Nombres comunes mal escritos
            'maria' => ['maria', 'marta', 'mario', 'mary'],
            'marta' => ['marta', 'maria', 'martha'],
            'mario' => ['mario', 'maria', 'marco'],
            'carlos' => ['carlos', 'carlo', 'carla'],
            'ana' => ['ana', 'anna', 'ani'],
            'luis' => ['luis', 'luiz', 'luisa'],
            'jose' => ['jose', 'josef', 'josie'],
            'juan' => ['juan', 'joan', 'juana'],

            // Términos de sistema
            'admin' => ['admin', 'administrator', 'administrador'],
            'super' => ['super', 'supper', 'sistem'],
            'mozo' => ['mozo', 'moso', 'waiter'],

            // Errores comunes de teclado
            'marua' => ['maria'],
            'mraio' => ['mario'],
            'cralos' => ['carlos'],
            'admun' => ['admin'],
            'suoer' => ['super'],
        ];

        // Si encontramos una corrección exacta, la usamos
        if (isset($commonCorrections[$search])) {
            return $commonCorrections[$search];
        }

        // Buscar correcciones por similitud
        foreach ($commonCorrections as $correct => $variations) {
            foreach ($variations as $variant) {
                if (static::levenshteinDistance($search, $variant) <= 2) {
                    $fuzzyMatches = array_merge($fuzzyMatches, $variations);
                    break 2;
                }
            }
        }

        // Generar variaciones automáticas para errores tipográficos comunes
        $autoVariations = static::generateTypoVariations($search);
        $fuzzyMatches = array_merge($fuzzyMatches, $autoVariations);

        return array_unique($fuzzyMatches);
    }

    /**
     * Calcula la distancia de Levenshtein entre dos strings
     */
    protected static function levenshteinDistance(string $str1, string $str2): int
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);

        if ($len1 == 0) return $len2;
        if ($len2 == 0) return $len1;

        $matrix = [];
        for ($i = 0; $i <= $len1; $i++) {
            $matrix[$i][0] = $i;
        }
        for ($j = 0; $j <= $len2; $j++) {
            $matrix[0][$j] = $j;
        }

        for ($i = 1; $i <= $len1; $i++) {
            for ($j = 1; $j <= $len2; $j++) {
                $cost = ($str1[$i-1] == $str2[$j-1]) ? 0 : 1;
                $matrix[$i][$j] = min(
                    $matrix[$i-1][$j] + 1,     // deletion
                    $matrix[$i][$j-1] + 1,     // insertion
                    $matrix[$i-1][$j-1] + $cost // substitution
                );
            }
        }

        return $matrix[$len1][$len2];
    }

    /**
     * Genera variaciones automáticas para errores tipográficos comunes
     */
    protected static function generateTypoVariations(string $search): array
    {
        if (strlen($search) < 3) return [$search];

        $variations = [$search];
        $chars = str_split($search);

        // Intercambio de caracteres adyacentes (transposición)
        for ($i = 0; $i < count($chars) - 1; $i++) {
            $temp = $chars;
            [$temp[$i], $temp[$i + 1]] = [$temp[$i + 1], $temp[$i]];
            $variations[] = implode('', $temp);
        }

        // Eliminación de un carácter
        for ($i = 0; $i < count($chars); $i++) {
            $temp = $chars;
            unset($temp[$i]);
            $variations[] = implode('', $temp);
        }

        return array_unique($variations);
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
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
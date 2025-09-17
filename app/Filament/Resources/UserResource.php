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

                                Section::make('Membresía y Pagos - MINIMAL TEST')
                                    ->schema([
                                        Forms\Components\TextInput::make('test_field')
                                            ->label('Test básico')
                                            ->default('Si ves esto, el formulario funciona'),
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
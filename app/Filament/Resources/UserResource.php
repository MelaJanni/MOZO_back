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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Spatie\Permission\Models\Role;

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
                Section::make('Información Personal')
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

                Section::make('Información Adicional')
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
                Tables\Columns\BadgeColumn::make('roles.name')
                    ->label('Rol')
                    ->colors([
                        'danger' => 'super_admin',
                        'warning' => 'admin',
                        'primary' => 'waiter',
                        'secondary' => 'user',
                    ])
                    ->separator(','),
                Tables\Columns\IconColumn::make('is_system_super_admin')
                    ->label('Super Admin')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-x-mark'),
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
                Tables\Columns\TextColumn::make('businessesAsAdmin_count')
                    ->label('Negocios')
                    ->counts('businessesAsAdmin')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('is_system_super_admin')
                    ->label('Super Administrador'),
                Tables\Filters\TernaryFilter::make('is_lifetime_paid')
                    ->label('Cliente permanente'),
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
                Filter::make('created_at')
                    ->label('Fecha de registro')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\Action::make('assign_plan')
                    ->label('Asignar Plan')
                    ->icon('heroicon-o-credit-card')
                    ->form([
                        Forms\Components\Select::make('plan_id')
                            ->label('Plan')
                            ->options(Plan::where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\Toggle::make('auto_renew')
                            ->label('Renovación automática')
                            ->default(true),
                        Forms\Components\Select::make('coupon_id')
                            ->label('Cupón (opcional)')
                            ->options(Coupon::where('is_active', true)->pluck('code', 'id'))
                            ->nullable(),
                    ])
                    ->action(function (array $data, $record) {
                        // Crear nueva suscripción
                        $plan = Plan::find($data['plan_id']);
                        $record->subscriptions()->create([
                            'plan_id' => $plan->id,
                            'status' => 'active',
                            'current_period_start' => now(),
                            'current_period_end' => now()->addDays($plan->billing_interval_days),
                            'auto_renew' => $data['auto_renew'],
                            'price' => $plan->price,
                            'currency' => 'ARS',
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
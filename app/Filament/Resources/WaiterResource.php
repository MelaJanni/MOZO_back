<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WaiterResource\Pages;
use App\Models\User;
use App\Models\Business;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class WaiterResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Mozos';
    protected static ?string $modelLabel = 'Mozo';
    protected static ?string $pluralModelLabel = 'Mozos';
    protected static ?string $navigationGroup = 'Gestión de Usuarios';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('roles', function($query) {
                $query->where('name', 'waiter');
            })
            ->orWhereHas('businessesAsWaiter');
    }

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

                Section::make('Perfil de Mozo')
                    ->schema([
                        Forms\Components\TextInput::make('waiterProfile.experience_years')
                            ->label('Años de experiencia')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Select::make('waiterProfile.availability')
                            ->label('Disponibilidad')
                            ->options([
                                'full_time' => 'Tiempo completo',
                                'part_time' => 'Medio tiempo',
                                'weekend' => 'Fines de semana',
                                'flexible' => 'Flexible',
                            ])
                            ->default('full_time'),
                        Forms\Components\Textarea::make('waiterProfile.skills')
                            ->label('Habilidades especiales')
                            ->placeholder('Ej: Conocimiento de vinos, idiomas, etc.')
                            ->rows(3),
                        Forms\Components\FileUpload::make('waiterProfile.photo')
                            ->label('Foto de perfil')
                            ->image()
                            ->directory('waiter-photos'),
                    ])->columns(2),

                Section::make('Negocios Vinculados')
                    ->schema([
                        Forms\Components\Repeater::make('businessesAsWaiter')
                            ->label('Negocios donde trabaja')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('business_id')
                                    ->label('Negocio')
                                    ->options(Business::where('is_active', true)->pluck('name', 'id'))
                                    ->required()
                                    ->searchable(),
                                Forms\Components\Select::make('employment_status')
                                    ->label('Estado de empleo')
                                    ->options([
                                        'active' => 'Activo',
                                        'inactive' => 'Inactivo',
                                        'terminated' => 'Terminado',
                                    ])
                                    ->default('active')
                                    ->required(),
                                Forms\Components\Select::make('employment_type')
                                    ->label('Tipo de empleo')
                                    ->options([
                                        'full_time' => 'Tiempo completo',
                                        'part_time' => 'Medio tiempo',
                                        'casual' => 'Casual',
                                    ])
                                    ->default('full_time'),
                                Forms\Components\TextInput::make('hourly_rate')
                                    ->label('Tarifa por hora')
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\DateTimePicker::make('hired_at')
                                    ->label('Fecha de contratación')
                                    ->default(now()),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => Business::find($state['business_id'])?->name ?? 'Nuevo negocio'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('waiterProfile.photo')
                    ->label('Foto')
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
                Tables\Columns\TextColumn::make('waiterProfile.experience_years')
                    ->label('Experiencia')
                    ->suffix(' años')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('waiterProfile.availability')
                    ->label('Disponibilidad')
                    ->colors([
                        'success' => 'full_time',
                        'warning' => 'part_time',
                        'info' => 'weekend',
                        'secondary' => 'flexible',
                    ]),
                Tables\Columns\TextColumn::make('businesses_count')
                    ->label('Negocios')
                    ->getStateUsing(fn ($record) => $record->businessesAsWaiter()->where('employment_status', 'active')->count())
                    ->badge()
                    ->color('primary'),
                Tables\Columns\BadgeColumn::make('employment_status')
                    ->label('Estado')
                    ->getStateUsing(function ($record) {
                        $activeEmployments = $record->businessesAsWaiter()->where('employment_status', 'active')->count();
                        return $activeEmployments > 0 ? 'Empleado' : 'Disponible';
                    })
                    ->colors([
                        'success' => 'Empleado',
                        'warning' => 'Disponible',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('availability')
                    ->label('Disponibilidad')
                    ->relationship('waiterProfile', 'availability')
                    ->options([
                        'full_time' => 'Tiempo completo',
                        'part_time' => 'Medio tiempo',
                        'weekend' => 'Fines de semana',
                        'flexible' => 'Flexible',
                    ]),
                SelectFilter::make('business')
                    ->label('Negocio')
                    ->relationship('businessesAsWaiter', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('employment_status')
                    ->label('Estado de empleo')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'employed' => 'Empleado',
                                'available' => 'Disponible',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['status'])) return $query;

                        return match ($data['status']) {
                            'employed' => $query->whereHas('businessesAsWaiter', function ($q) {
                                $q->where('employment_status', 'active');
                            }),
                            'available' => $query->whereDoesntHave('businessesAsWaiter', function ($q) {
                                $q->where('employment_status', 'active');
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
                Tables\Actions\Action::make('assign_business')
                    ->label('Asignar a Negocio')
                    ->icon('heroicon-o-building-storefront')
                    ->form([
                        Forms\Components\Select::make('business_id')
                            ->label('Negocio')
                            ->options(Business::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('employment_type')
                            ->label('Tipo de empleo')
                            ->options([
                                'full_time' => 'Tiempo completo',
                                'part_time' => 'Medio tiempo',
                                'casual' => 'Casual',
                            ])
                            ->default('full_time')
                            ->required(),
                        Forms\Components\TextInput::make('hourly_rate')
                            ->label('Tarifa por hora')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->action(function (array $data, $record) {
                        $record->businessesAsWaiter()->attach($data['business_id'], [
                            'employment_status' => 'active',
                            'employment_type' => $data['employment_type'],
                            'hourly_rate' => $data['hourly_rate'] ?? null,
                            'hired_at' => now(),
                        ]);
                    }),
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
            'index' => Pages\ListWaiters::route('/'),
            'create' => Pages\CreateWaiter::route('/create'),
            'view' => Pages\ViewWaiter::route('/{record}'),
            'edit' => Pages\EditWaiter::route('/{record}/edit'),
        ];
    }
}
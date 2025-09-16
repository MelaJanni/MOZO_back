<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Planes de Pago';
    protected static ?string $modelLabel = 'Plan';
    protected static ?string $pluralModelLabel = 'Planes';
    protected static ?string $navigationGroup = 'Facturación';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Plan')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del plan')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('interval')
                            ->label('Intervalo de facturación')
                            ->options([
                                'monthly' => 'Mensual',
                                'yearly' => 'Anual',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('price_cents')
                            ->label('Precio (en centavos)')
                            ->required()
                            ->numeric()
                            ->helperText('Ingrese el precio en centavos (ej: 1500 para $15.00)'),
                        Forms\Components\TextInput::make('currency')
                            ->label('Moneda')
                            ->default('USD')
                            ->required()
                            ->maxLength(3),
                        Forms\Components\TextInput::make('trial_days')
                            ->label('Días de prueba')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Plan activo')
                            ->default(true),
                    ])->columns(2),

                Section::make('Configuración Avanzada')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadatos')
                            ->helperText('Información adicional del plan en formato clave-valor'),
                        Forms\Components\KeyValue::make('provider_plan_ids')
                            ->label('IDs de Proveedores')
                            ->helperText('IDs del plan en diferentes proveedores de pago'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('interval')
                    ->label('Intervalo')
                    ->colors([
                        'primary' => 'monthly',
                        'success' => 'yearly',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'monthly' => 'Mensual',
                        'yearly' => 'Anual',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('formatted_price')
                    ->label('Precio')
                    ->sortable(['price_cents'])
                    ->getStateUsing(fn ($record) => '$' . number_format($record->price_cents / 100, 2)),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Moneda')
                    ->badge(),
                Tables\Columns\TextColumn::make('trial_days')
                    ->label('Días de prueba')
                    ->suffix(' días')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->label('Suscripciones')
                    ->getStateUsing(fn ($record) => $record->subscriptions()->count())
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('interval')
                    ->label('Intervalo')
                    ->options([
                        'monthly' => 'Mensual',
                        'yearly' => 'Anual',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
                SelectFilter::make('currency')
                    ->label('Moneda')
                    ->options([
                        'USD' => 'USD',
                        'EUR' => 'EUR',
                        'COP' => 'COP',
                    ]),
                Filter::make('price_range')
                    ->label('Rango de precio')
                    ->form([
                        Forms\Components\TextInput::make('price_from')
                            ->label('Desde')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('price_to')
                            ->label('Hasta')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $price): Builder => $query->where('price_cents', '>=', $price * 100),
                            )
                            ->when(
                                $data['price_to'],
                                fn (Builder $query, $price): Builder => $query->where('price_cents', '<=', $price * 100),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn ($record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                            });
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => false]);
                            });
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'view' => Pages\ViewPlan::route('/{record}'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}

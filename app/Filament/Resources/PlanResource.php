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
                        Forms\Components\Select::make('billing_period')
                            ->label('Periodo de facturación')
                            ->options([
                                'monthly' => 'Mensual',
                                'yearly' => 'Anual',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('price_ars')
                            ->label('Precio ARS')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->helperText('Ingrese el precio en pesos argentinos (ej: 15.000)')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') : '')
                            ->dehydrateStateUsing(fn ($state) => $state ? (int) str_replace(['.', ','], '', $state) : null)
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $cleanValue = (int) str_replace(['.', ','], '', $state);
                                    $set('price_ars', number_format($cleanValue, 0, ',', '.'));
                                }
                            }),
                        Forms\Components\TextInput::make('price_usd')
                            ->label('Precio USD')
                            ->numeric()
                            ->prefix('$')
                            ->helperText('Precio en dólares (opcional)')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') : '')
                            ->dehydrateStateUsing(fn ($state) => $state ? (int) str_replace(['.', ','], '', $state) : null)
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $cleanValue = (int) str_replace(['.', ','], '', $state);
                                    $set('price_usd', number_format($cleanValue, 0, ',', '.'));
                                }
                            }),
                        Forms\Components\Select::make('currency')
                            ->label('Moneda')
                            ->options([
                                'ARS' => 'Peso Argentino (ARS)',
                                'USD' => 'Dólar Estadounidense (USD)',
                            ])
                            ->default('ARS')
                            ->required(),
                        Forms\Components\TextInput::make('trial_days')
                            ->label('Días de prueba')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Plan activo')
                            ->default(true),
                    ])->columns(2),

                Section::make('Precios por Moneda')
                    ->description('Configure los precios del plan en diferentes monedas')
                    ->schema([
                        Forms\Components\Repeater::make('currency_prices')
                            ->label('Precios')
                            ->schema([
                                Forms\Components\Select::make('currency')
                                    ->label('Moneda')
                                    ->options([
                                        'ARS' => 'Peso Argentino (ARS)',
                                        'USD' => 'Dólar Estadounidense (USD)',
                                        'EUR' => 'Euro (EUR)',
                                        'BRL' => 'Real Brasileño (BRL)',
                                    ])
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                Forms\Components\TextInput::make('price')
                                    ->label('Precio')
                                    ->required()
                                    ->numeric()
                                    ->prefix(fn($get) => match($get('currency')) {
                                        'USD', 'ARS' => '$',
                                        'EUR' => '€',
                                        'BRL' => 'R$',
                                        default => '$'
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $cleanValue = (int) str_replace(['.', ','], '', $state);
                                            $set('price', number_format($cleanValue, 0, ',', '.'));
                                        }
                                    })
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') : '')
                                    ->dehydrateStateUsing(fn ($state) => $state ? (int) str_replace(['.', ','], '', $state) : null),
                                Forms\Components\TextInput::make('discount_percentage')
                                    ->label('Descuento (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->helperText('Descuento opcional para esta moneda'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Agregar Precio')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['currency'] ?? 'Nueva moneda') . ': ' . (isset($state['price']) ? number_format($state['price'], 0, ',', '.') : '0'))
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $data['metadata'] = $data;
                                return $data;
                            }),
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
                Tables\Columns\BadgeColumn::make('billing_period')
                    ->label('Periodo')
                    ->colors([
                        'primary' => 'monthly',
                        'success' => 'yearly',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'monthly' => 'Mensual',
                        'yearly' => 'Anual',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('formatted_price_ars')
                    ->label('Precio ARS')
                    ->sortable(['price_ars'])
                    ->getStateUsing(fn ($record) => $record->price_ars ? '$' . number_format($record->price_ars, 0, ',', '.') : '-'),
                Tables\Columns\TextColumn::make('formatted_price_usd')
                    ->label('Precio USD')
                    ->sortable(['price_usd'])
                    ->getStateUsing(fn ($record) => $record->price_usd ? '$' . number_format($record->price_usd, 0, ',', '.') : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->label('Suscripciones Totales')
                    ->getStateUsing(fn ($record) => $record->subscriptions()->count())
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('active_subscriptions_count')
                    ->label('Suscripciones Activas')
                    ->getStateUsing(fn ($record) => $record->getActiveSubscriptionsCount())
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->tooltip(fn ($record) => $record->canBeDeleted() ? 'Plan disponible para eliminación' : 'Este plan no puede eliminarse'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('billing_period')
                    ->label('Periodo')
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
                        'ARS' => 'Peso Argentino (ARS)',
                        'USD' => 'Dólar Estadounidense (USD)',
                    ]),
                Filter::make('price_range_ars')
                    ->label('Rango de precio ARS')
                    ->form([
                        Forms\Components\TextInput::make('price_from')
                            ->label('Desde')
                            ->numeric()
                            ->prefix('$')
                            ->helperText('Precio mínimo en pesos'),
                        Forms\Components\TextInput::make('price_to')
                            ->label('Hasta')
                            ->numeric()
                            ->prefix('$')
                            ->helperText('Precio máximo en pesos'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $price): Builder => $query->where('price_ars', '>=', $price),
                            )
                            ->when(
                                $data['price_to'],
                                fn (Builder $query, $price): Builder => $query->where('price_ars', '<=', $price),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_status')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->tooltip(fn ($record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                    }),
                Tables\Actions\Action::make('deactivate_plan')
                    ->label('Desactivar Plan')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('¿Desactivar este plan?')
                    ->modalDescription('Los usuarios con este plan entrarán en período de gracia cuando expire su suscripción actual.')
                    ->visible(fn ($record) => $record->is_active && $record->hasActiveSubscriptions())
                    ->action(function ($record) {
                        $record->update(['is_active' => false]);

                        \Filament\Notifications\Notification::make()
                            ->title('Plan desactivado')
                            ->body('Los usuarios existentes tendrán un período de gracia para seleccionar un nuevo plan.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->requiresConfirmation()
                    ->modalHeading('¿Eliminar este plan?')
                    ->modalDescription('Esta acción no se puede deshacer.')
                    ->visible(fn ($record) => !$record->hasActiveSubscriptions())
                    ->before(function ($record) {
                        if ($record->hasActiveSubscriptions()) {
                            \Filament\Notifications\Notification::make()
                                ->title('No se puede eliminar')
                                ->body($record->getDeletionRestrictionReason() . ' Usa "Desactivar Plan" en su lugar.')
                                ->danger()
                                ->send();

                            throw new \Filament\Actions\Exceptions\Cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Reactivar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('¿Reactivar planes seleccionados?')
                        ->modalDescription('Los planes volverán a estar disponibles para nuevas suscripciones.')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                            });
                        }),
                    Tables\Actions\BulkAction::make('deactivate_selected')
                        ->label('Desactivar seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('¿Desactivar planes seleccionados?')
                        ->modalDescription('Los usuarios con estos planes entrarán en período de gracia cuando expire su suscripción actual.')
                        ->action(function ($records) {
                            $deactivatedCount = 0;

                            foreach ($records as $record) {
                                if ($record->is_active) {
                                    $record->update(['is_active' => false]);
                                    $deactivatedCount++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Planes desactivados')
                                ->body("Se desactivaron {$deactivatedCount} planes. Los usuarios tendrán período de gracia.")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->requiresConfirmation()
                        ->modalHeading('¿Eliminar planes seleccionados?')
                        ->modalDescription('Solo se eliminarán planes sin suscripciones activas.')
                        ->action(function ($records) {
                            $deletedCount = 0;
                            $protectedCount = 0;

                            foreach ($records as $record) {
                                if (!$record->canBeDeleted()) {
                                    $protectedCount++;
                                } else {
                                    $record->delete();
                                    $deletedCount++;
                                }
                            }

                            if ($deletedCount > 0 && $protectedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Eliminación parcial completada')
                                    ->body("Se eliminaron {$deletedCount} planes. {$protectedCount} planes fueron protegidos.")
                                    ->warning()
                                    ->send();
                            } elseif ($protectedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No se pudo eliminar')
                                    ->body("Los {$protectedCount} planes tienen suscripciones activas. Usa \"Desactivar\" en su lugar.")
                                    ->danger()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Planes eliminados')
                                    ->body("Se eliminaron {$deletedCount} planes correctamente.")
                                    ->success()
                                    ->send();
                            }
                        }),
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

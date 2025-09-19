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
                        Forms\Components\TextInput::make('trial_days')
                            ->label('Días de prueba')
                            ->numeric()
                            ->default(14),
                        Forms\Components\Toggle::make('trial_enabled')
                            ->label('Período de prueba habilitado')
                            ->default(true)
                            ->live(),
                        Forms\Components\Toggle::make('trial_requires_payment_method')
                            ->label('Requiere método de pago para trial')
                            ->default(false)
                            ->visible(fn ($get) => $get('trial_enabled')),
                        Forms\Components\TextInput::make('yearly_discount_percentage')
                            ->label('Descuento anual (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(20)
                            ->helperText('Descuento aplicado al pago anual'),
                        Forms\Components\TextInput::make('quarterly_discount_percentage')
                            ->label('Descuento trimestral (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(10)
                            ->helperText('Descuento aplicado al pago trimestral'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Plan activo')
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Orden de visualización')
                            ->numeric()
                            ->default(1)
                            ->helperText('Número para ordenar los planes (menor = primero)'),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Plan destacado')
                            ->default(false),
                        Forms\Components\Toggle::make('is_popular')
                            ->label('Plan popular')
                            ->default(false),
                    ])->columns(2),

                Section::make('Precios por Moneda')
                    ->description('Configure los precios del plan en diferentes monedas')
                    ->schema([
                        Forms\Components\KeyValue::make('prices')
                            ->label('Precios por moneda')
                            ->keyLabel('Moneda (código de 3 letras)')
                            ->valueLabel('Precio')
                            ->keyPlaceholder('ARS')
                            ->valuePlaceholder('15.000')
                            ->helperText('Ejemplo: ARS = 15.000, USD = 50')
                            ->default(['ARS' => 0])
                            ->required()
                            ->addActionLabel('Agregar precio')
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if (is_array($state)) {
                                    $formatted = [];
                                    foreach ($state as $currency => $price) {
                                        if ($price && is_numeric(str_replace(['.', ','], '', $price))) {
                                            $cleanValue = str_replace(['.', ','], '', $price);
                                            $formatted[$currency] = number_format((float)$cleanValue, 0, ',', '.');
                                        } else {
                                            $formatted[$currency] = $price;
                                        }
                                    }
                                    $set('prices', $formatted);
                                }
                            })
                            ->dehydrateStateUsing(function ($state) {
                                if (is_array($state)) {
                                    $cleaned = [];
                                    foreach ($state as $currency => $price) {
                                        if ($price) {
                                            $cleanValue = str_replace(['.', ','], '', $price);
                                            $cleaned[$currency] = is_numeric($cleanValue) ? (int) $cleanValue : $price;
                                        } else {
                                            $cleaned[$currency] = $price;
                                        }
                                    }
                                    return $cleaned;
                                }
                                return $state;
                            })
                            ->formatStateUsing(function ($state) {
                                if (is_array($state)) {
                                    $formatted = [];
                                    foreach ($state as $currency => $price) {
                                        if ($price && is_numeric($price)) {
                                            $formatted[$currency] = number_format((float)$price, 0, ',', '.');
                                        } else {
                                            $formatted[$currency] = $price;
                                        }
                                    }
                                    return $formatted;
                                }
                                return $state;
                            }),
                        Forms\Components\Select::make('default_currency')
                            ->label('Moneda por defecto')
                            ->options([
                                'ARS' => 'Peso Argentino (ARS)',
                                'USD' => 'Dólar Estadounidense (USD)',
                                'EUR' => 'Euro (EUR)',
                                'BRL' => 'Real Brasileño (BRL)',
                            ])
                            ->default('ARS')
                            ->required()
                            ->helperText('Moneda que se mostrará por defecto en las listas'),
                    ])->columns(1),

                Section::make('Características del Plan')
                    ->description('Configure las características incluidas en este plan')
                    ->schema([
                        Forms\Components\TagsInput::make('features')
                            ->label('Características incluidas')
                            ->placeholder('Agregar característica...')
                            ->helperText('Presiona Enter después de cada característica para agregarla')
                            ->required()
                            ->afterStateHydrated(function ($state, $set, $record) {
                                // Si estamos editando un registro existente y hay datos, usarlos
                                if ($record && !empty($record->features)) {
                                    $set('features', $record->features);
                                } elseif (empty($state)) {
                                    // Si no hay datos, usar valores por defecto solo para nuevos registros
                                    $set('features', [
                                        'Códigos QR personalizados',
                                        'Menú digital',
                                        'Notificaciones móviles',
                                        'Dashboard básico',
                                        'Soporte por email',
                                    ]);
                                }
                            })
                            ->separator(',')
                            ->splitKeys(['Enter', ',', 'Tab']),
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
                Tables\Columns\TextColumn::make('default_price')
                    ->label('Precio Principal')
                    ->getStateUsing(fn ($record) => $record->getFormattedPrice()),
                Tables\Columns\TextColumn::make('available_currencies')
                    ->label('Monedas disponibles')
                    ->getStateUsing(fn ($record) => implode(', ', $record->getAvailableCurrencies()))
                    ->badge()
                    ->separator(',')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('default_currency')
                    ->label('Moneda por defecto')
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
                SelectFilter::make('default_currency')
                    ->label('Moneda por defecto')
                    ->options([
                        'ARS' => 'Peso Argentino (ARS)',
                        'USD' => 'Dólar Estadounidense (USD)',
                        'EUR' => 'Euro (EUR)',
                        'BRL' => 'Real Brasileño (BRL)',
                    ]),
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

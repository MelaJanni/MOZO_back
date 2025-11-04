<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
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
use Carbon\Carbon;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Cupones de Descuento';
    protected static ?string $modelLabel = 'Cupón';
    protected static ?string $pluralModelLabel = 'Cupones';
    protected static ?string $navigationGroup = 'Facturación';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Cupón')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Código del cupón')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Código único que los usuarios usarán para aplicar el descuento'),
                        Forms\Components\Select::make('type')
                            ->label('Tipo de descuento')
                            ->options([
                                'percent' => 'Porcentaje',
                                'fixed' => 'Cantidad fija',
                                'free_time' => 'Tiempo gratis',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('value', null)),
                        Forms\Components\TextInput::make('value')
                            ->label(function (callable $get) {
                                return match ($get('type')) {
                                    'percent' => 'Porcentaje de descuento (%)',
                                    'fixed' => 'Cantidad de descuento (en centavos)',
                                    default => 'Valor',
                                };
                            })
                            ->required()
                            ->numeric()
                            ->helperText(function (callable $get) {
                                return match ($get('type')) {
                                    'percent' => 'Ingrese el porcentaje (ej: 10 para 10%)',
                                    'fixed' => 'Ingrese la cantidad en centavos (ej: 1500 para $15.00)',
                                    'free_time' => 'No se usa para tiempo gratis',
                                    default => 'Valor del descuento',
                                };
                            })
                            ->hidden(fn (callable $get) => $get('type') === 'free_time'),
                        Forms\Components\TextInput::make('free_days')
                            ->label('Días gratis')
                            ->required(fn (callable $get) => $get('type') === 'free_time')
                            ->numeric()
                            ->helperText('Número de días de servicio gratuito')
                            ->visible(fn (callable $get) => $get('type') === 'free_time'),
                    ])->columns(2),

                Section::make('Limitaciones y Vigencia')
                    ->schema([
                        Forms\Components\TextInput::make('max_redemptions')
                            ->label('Máximo de usos')
                            ->numeric()
                            ->helperText('Dejar vacío para uso ilimitado'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Fecha de expiración')
                            ->helperText('Dejar vacío si no expira'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Cupón activo')
                            ->default(true),
                    ])->columns(2),

                Section::make('Información Adicional')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadatos')
                            ->helperText('Información adicional del cupón en formato clave-valor'),
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
                    ->sortable()
                    ->copyable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'primary' => 'percent',
                        'success' => 'fixed',
                        'warning' => 'free_time',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percent' => 'Porcentaje',
                        'fixed' => 'Cantidad fija',
                        'free_time' => 'Tiempo gratis',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('discount_description')
                    ->label('Descuento')
                    ->getStateUsing(fn ($record) => $record->getDiscountDescription()),
                Tables\Columns\TextColumn::make('usage_info')
                    ->label('Uso')
                    ->getStateUsing(function ($record) {
                        if ($record->max_redemptions) {
                            return "{$record->redeemed_count}/{$record->max_redemptions}";
                        }
                        return "{$record->redeemed_count}/∞";
                    })
                    ->badge()
                    ->color(function ($record) {
                        if (!$record->max_redemptions) return 'info';
                        $percentage = ($record->redeemed_count / $record->max_redemptions) * 100;
                        if ($percentage >= 90) return 'danger';
                        if ($percentage >= 70) return 'warning';
                        return 'success';
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->getStateUsing(function ($record) {
                        if (!$record->is_active) return 'Inactivo';
                        if ($record->expires_at && $record->expires_at->isPast()) return 'Expirado';
                        if ($record->max_redemptions && $record->redeemed_count >= $record->max_redemptions) return 'Agotado';
                        return 'Activo';
                    })
                    ->colors([
                        'success' => 'Activo',
                        'danger' => 'Inactivo',
                        'warning' => 'Expirado',
                        'secondary' => 'Agotado',
                    ]),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expira')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Sin expiración')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'percent' => 'Porcentaje',
                        'fixed' => 'Cantidad fija',
                        'free_time' => 'Tiempo gratis',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
                Filter::make('expires_at')
                    ->label('Expiración')
                    ->form([
                        Forms\Components\DatePicker::make('expires_from')
                            ->label('Expira desde'),
                        Forms\Components\DatePicker::make('expires_until')
                            ->label('Expira hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['expires_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('expires_at', '>=', $date),
                            )
                            ->when(
                                $data['expires_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('expires_at', '<=', $date),
                            );
                    }),
                Filter::make('valid_coupons')
                    ->label('Solo cupones válidos')
                    ->query(function (Builder $query): Builder {
                        return $query->where('is_active', true)
                            ->where(function ($q) {
                                $q->whereNull('expires_at')
                                  ->orWhere('expires_at', '>', now());
                            })
                            ->where(function ($q) {
                                $q->whereNull('max_redemptions')
                                  ->orWhereRaw('redeemed_count < max_redemptions');
                            });
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
                Tables\Actions\Action::make('reset_usage')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->tooltip('Reiniciar contador')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['redeemed_count' => 0]);
                    })
                    ->visible(fn ($record) => $record->redeemed_count > 0),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'view' => Pages\ViewCoupon::route('/{record}'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->count();
    }
}

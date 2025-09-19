<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodResource\Pages;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Métodos de Pago';
    protected static ?string $modelLabel = 'Método de Pago';
    protected static ?string $pluralModelLabel = 'Métodos de Pago';
    protected static ?string $navigationGroup = 'Facturación';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->maxLength(65535),
                    ])->columns(2),

                Section::make('Configuración')
                    ->schema([
                        Forms\Components\Toggle::make('is_enabled')
                            ->label('Habilitado')
                            ->default(true),
                        Forms\Components\Toggle::make('is_test_mode')
                            ->label('Modo de prueba')
                            ->default(false),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),

                Section::make('Límites de Monto')
                    ->schema([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Monto mínimo')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('Monto máximo')
                            ->numeric()
                            ->prefix('$'),
                    ])->columns(2),

                Section::make('Comisiones')
                    ->schema([
                        Forms\Components\KeyValue::make('fees')
                            ->label('Estructura de comisiones')
                            ->helperText('Ej: {"percentage": 3.99, "fixed": 0.30}'),
                    ])->columns(1),

                Section::make('Monedas Soportadas')
                    ->schema([
                        Forms\Components\TagsInput::make('supported_currencies')
                            ->label('Monedas')
                            ->placeholder('ARS, USD, EUR')
                            ->helperText('Códigos de moneda de 3 letras'),
                    ])->columns(1),

                Section::make('Configuración de API')
                    ->schema([
                        Forms\Components\KeyValue::make('config')
                            ->label('Configuración de API')
                            ->helperText('Claves API, URLs, etc.'),
                    ])->columns(1),

                Section::make('Webhooks')
                    ->schema([
                        Forms\Components\TextInput::make('webhook_url')
                            ->label('URL del Webhook')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('webhook_secret')
                            ->label('Secret del Webhook')
                            ->password()
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Apariencia')
                    ->schema([
                        Forms\Components\TextInput::make('logo_url')
                            ->label('URL del Logo')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\ColorPicker::make('color_primary')
                            ->label('Color Primario'),
                        Forms\Components\ColorPicker::make('color_secondary')
                            ->label('Color Secundario'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=FFFFFF&background=' . ltrim($record->color_primary ?? '007BFF', '#')),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('supported_currencies')
                    ->label('Monedas')
                    ->badge()
                    ->separator(','),
                Tables\Columns\TextColumn::make('fees_display')
                    ->label('Comisiones')
                    ->getStateUsing(function ($record) {
                        $fees = $record->fees ?? [];
                        $percentage = $fees['percentage'] ?? 0;
                        $fixed = $fees['fixed'] ?? 0;
                        return $percentage . '% + $' . number_format($fixed, 2);
                    }),
                Tables\Columns\IconColumn::make('is_enabled')
                    ->label('Habilitado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\IconColumn::make('is_test_mode')
                    ->label('Prueba')
                    ->boolean()
                    ->trueIcon('heroicon-o-beaker')
                    ->falseIcon('heroicon-o-check'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_enabled')
                    ->label('Estado')
                    ->options([
                        1 => 'Habilitados',
                        0 => 'Deshabilitados',
                    ]),
                SelectFilter::make('is_test_mode')
                    ->label('Modo')
                    ->options([
                        1 => 'Modo Prueba',
                        0 => 'Modo Producción',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn ($record) => $record->is_enabled ? 'Deshabilitar' : 'Habilitar')
                    ->icon(fn ($record) => $record->is_enabled ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_enabled ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_enabled' => !$record->is_enabled]);
                    }),
                Tables\Actions\Action::make('test_connection')
                    ->label('Probar')
                    ->icon('heroicon-o-beaker')
                    ->color('info')
                    ->action(function ($record) {
                        // TODO: Implementar test de conexión
                        \Filament\Notifications\Notification::make()
                            ->title('Test no implementado')
                            ->body('La funcionalidad de test está pendiente de implementación')
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('enable')
                        ->label('Habilitar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_enabled' => true]);
                            });
                        }),
                    Tables\Actions\BulkAction::make('disable')
                        ->label('Deshabilitar seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_enabled' => false]);
                            });
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('sort_order');
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
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'view' => Pages\ViewPaymentMethod::route('/{record}'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_enabled', true)->count();
    }
}
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteSettingResource\Pages;
use App\Filament\Resources\SiteSettingResource\RelationManagers;
use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configuraciones del Sitio';

    protected static ?string $modelLabel = 'Configuración';

    protected static ?string $pluralModelLabel = 'Configuraciones del Sitio';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 90;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Configuración')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Clave')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Identificador único para esta configuración'),

                        Forms\Components\TextInput::make('label')
                            ->label('Etiqueta')
                            ->required()
                            ->helperText('Nombre descriptivo para mostrar en el admin'),

                        Forms\Components\Select::make('group')
                            ->label('Grupo')
                            ->options([
                                'contact' => 'Contacto',
                                'social' => 'Redes Sociales',
                                'general' => 'General',
                                'appearance' => 'Apariencia',
                            ])
                            ->helperText('Categoría de la configuración'),

                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'text' => 'Texto',
                                'email' => 'Email',
                                'phone' => 'Teléfono',
                                'url' => 'URL',
                                'textarea' => 'Texto Largo',
                                'json' => 'JSON',
                                'boolean' => 'Verdadero/Falso',
                                'integer' => 'Número Entero',
                                'float' => 'Número Decimal',
                            ])
                            ->required()
                            ->live()
                            ->helperText('Define el tipo de valor y cómo se mostrará el campo'),
                    ]),

                Forms\Components\Section::make('Valor de la Configuración')
                    ->schema([
                        Forms\Components\TextInput::make('value')
                            ->label('Valor')
                            ->visible(fn ($get) => in_array($get('type'), ['text', 'email', 'phone', 'url', 'integer', 'float']))
                            ->helperText('El valor actual de esta configuración'),

                        Forms\Components\Textarea::make('value')
                            ->label('Valor')
                            ->visible(fn ($get) => $get('type') === 'textarea')
                            ->rows(3)
                            ->helperText('El valor actual de esta configuración'),

                        Forms\Components\Toggle::make('value')
                            ->label('Activado')
                            ->visible(fn ($get) => $get('type') === 'boolean')
                            ->helperText('Activar o desactivar esta configuración'),

                        Forms\Components\Textarea::make('value')
                            ->label('Valor JSON')
                            ->visible(fn ($get) => $get('type') === 'json')
                            ->rows(5)
                            ->helperText('Formato JSON válido')
                            ->placeholder('{"key": "value"}'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(2)
                            ->helperText('Descripción opcional para explicar el uso de esta configuración'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Si está desactivado, la configuración no será utilizada'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label('Grupo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'contact' => 'info',
                        'social' => 'success',
                        'general' => 'warning',
                        'appearance' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('label')
                    ->label('Etiqueta')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('key')
                    ->label('Clave')
                    ->searchable()
                    ->fontFamily('mono')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),

                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('Grupo')
                    ->options([
                        'contact' => 'Contacto',
                        'social' => 'Redes Sociales',
                        'general' => 'General',
                        'appearance' => 'Apariencia',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'text' => 'Texto',
                        'email' => 'Email',
                        'phone' => 'Teléfono',
                        'url' => 'URL',
                        'textarea' => 'Texto Largo',
                        'json' => 'JSON',
                        'boolean' => 'Verdadero/Falso',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('group')
            ->groupedBulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
            'index' => Pages\ListSiteSettings::route('/'),
            'create' => Pages\CreateSiteSetting::route('/create'),
            'edit' => Pages\EditSiteSetting::route('/{record}/edit'),
        ];
    }
}

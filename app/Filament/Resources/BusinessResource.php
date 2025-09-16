<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessResource\Pages;
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

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Negocios';
    protected static ?string $modelLabel = 'Negocio';
    protected static ?string $pluralModelLabel = 'Negocios';
    protected static ?string $navigationGroup = 'Gestión de Usuarios';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Negocio')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del negocio')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug (URL amigable)')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3),
                        Forms\Components\FileUpload::make('logo')
                            ->label('Logo')
                            ->image()
                            ->directory('business-logos'),
                    ])->columns(2),

                Section::make('Información de Contacto')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Dirección')
                            ->rows(2),
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email(),
                        Forms\Components\TextInput::make('website')
                            ->label('Sitio web')
                            ->url(),
                    ])->columns(2),

                Section::make('Configuración')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Negocio activo')
                            ->default(true),
                        Forms\Components\Select::make('business_type')
                            ->label('Tipo de negocio')
                            ->options([
                                'restaurant' => 'Restaurante',
                                'cafe' => 'Café',
                                'bar' => 'Bar',
                                'fast_food' => 'Comida rápida',
                                'other' => 'Otro',
                            ]),
                        Forms\Components\TimePicker::make('opening_time')
                            ->label('Hora de apertura'),
                        Forms\Components\TimePicker::make('closing_time')
                            ->label('Hora de cierre'),
                    ])->columns(2),

                Section::make('Información Adicional')
                    ->schema([
                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Notas internas')
                            ->helperText('Solo visible para administradores')
                            ->rows(3),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->copyable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('tables_count')
                    ->label('Mesas')
                    ->getStateUsing(fn ($record) => $record->tables()->count())
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('admins_count')
                    ->label('Administradores')
                    ->getStateUsing(fn ($record) => $record->admins()->count())
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('waiters_count')
                    ->label('Mozos')
                    ->getStateUsing(fn ($record) => $record->waiters()->count())
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('menus_count')
                    ->label('Menús PDF')
                    ->getStateUsing(fn ($record) => $record->menus()->count())
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
                Filter::make('created_at')
                    ->label('Fecha de creación')
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
                Tables\Actions\Action::make('view_staff')
                    ->label('Ver Personal')
                    ->icon('heroicon-o-users')
                    ->action(function ($record) {
                        // Implementar vista de personal
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
            'index' => Pages\ListBusinesses::route('/'),
            'create' => Pages\CreateBusiness::route('/create'),
            'view' => Pages\ViewBusiness::route('/{record}'),
            'edit' => Pages\EditBusiness::route('/{record}/edit'),
        ];
    }
}

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
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3),
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
                    ])->columns(2),

                Section::make('Configuración')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Negocio activo')
                            ->default(true),
                    ])->columns(1),

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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->limit(30),
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
                    ->hidden(),
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
                    ->label('')
                    ->tooltip('Ver'),
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar'),
                Tables\Actions\Action::make('view_staff')
                    ->label('')
                    ->icon('heroicon-o-users')
                    ->tooltip('Ver Personal')
                    ->modalHeading(fn ($record) => "Personal de {$record->name}")
                    ->modalDescription('Administradores y mozos del negocio')
                    ->modalContent(function ($record) {
                        $admins = $record->admins()->get();
                        $waiters = $record->waiters()->get();

                        $content = '<div class="space-y-6">';

                        // Administradores
                        $content .= '<div>';
                        $content .= '<h3 class="text-lg font-semibold text-gray-900 mb-3">Administradores (' . $admins->count() . ')</h3>';
                        if ($admins->count() > 0) {
                            $content .= '<div class="grid gap-3">';
                            foreach ($admins as $admin) {
                                $content .= '<div class="flex items-center gap-3 p-3 bg-orange-50 rounded-lg">';
                                $content .= '<div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">';
                                $content .= strtoupper(substr($admin->name, 0, 2));
                                $content .= '</div>';
                                $content .= '<div>';
                                $content .= '<p class="font-medium text-gray-900">' . $admin->name . '</p>';
                                $content .= '<p class="text-sm text-gray-600">' . $admin->email . '</p>';
                                $content .= '</div>';
                                $content .= '</div>';
                            }
                            $content .= '</div>';
                        } else {
                            $content .= '<p class="text-gray-500 italic">No hay administradores asignados</p>';
                        }
                        $content .= '</div>';

                        // Mozos
                        $content .= '<div>';
                        $content .= '<h3 class="text-lg font-semibold text-gray-900 mb-3">Mozos (' . $waiters->count() . ')</h3>';
                        if ($waiters->count() > 0) {
                            $content .= '<div class="grid gap-3">';
                            foreach ($waiters as $waiter) {
                                $content .= '<div class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">';
                                $content .= '<div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">';
                                $content .= strtoupper(substr($waiter->name, 0, 2));
                                $content .= '</div>';
                                $content .= '<div>';
                                $content .= '<p class="font-medium text-gray-900">' . $waiter->name . '</p>';
                                $content .= '<p class="text-sm text-gray-600">' . $waiter->email . '</p>';
                                $content .= '</div>';
                                $content .= '</div>';
                            }
                            $content .= '</div>';
                        } else {
                            $content .= '<p class="text-gray-500 italic">No hay mozos asignados</p>';
                        }
                        $content .= '</div>';

                        $content .= '</div>';

                        return new \Illuminate\Support\HtmlString($content);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
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

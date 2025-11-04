<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Ticket;
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

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Tickets de Soporte';
    protected static ?string $modelLabel = 'Ticket';
    protected static ?string $pluralModelLabel = 'Tickets';
    protected static ?string $navigationGroup = 'Soporte y Reportes';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Ticket')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Usuario')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('business_id')
                            ->label('Negocio')
                            ->relationship('business', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('subject')
                            ->label('Asunto')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'open' => 'Abierto',
                                'pending' => 'Pendiente',
                                'closed' => 'Cerrado',
                            ])
                            ->default('open')
                            ->required(),
                        Forms\Components\Select::make('priority')
                            ->label('Prioridad')
                            ->options([
                                'low' => 'Baja',
                                'normal' => 'Normal',
                                'high' => 'Alta',
                            ])
                            ->default('normal')
                            ->required(),
                    ])->columns(2),

                Section::make('Contenido del Mensaje')
                    ->schema([
                        Forms\Components\Textarea::make('message')
                            ->label('Mensaje')
                            ->required()
                            ->rows(5),
                        Forms\Components\TagsInput::make('tags')
                            ->label('Etiquetas')
                            ->placeholder('Agregar etiquetas...')
                            ->separator(','),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('business.name')
                    ->label('Negocio')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Asunto')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'success' => 'closed',
                        'warning' => 'pending',
                        'danger' => 'open',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Abierto',
                        'pending' => 'Pendiente',
                        'closed' => 'Cerrado',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Prioridad')
                    ->colors([
                        'secondary' => 'low',
                        'primary' => 'normal',
                        'danger' => 'high',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Baja',
                        'normal' => 'Normal',
                        'high' => 'Alta',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('tags')
                    ->label('Etiquetas')
                    ->badge()
                    ->separator(',')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'open' => 'Abierto',
                        'pending' => 'Pendiente',
                        'closed' => 'Cerrado',
                    ]),
                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'low' => 'Baja',
                        'normal' => 'Normal',
                        'high' => 'Alta',
                    ]),
                SelectFilter::make('user')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('business')
                    ->label('Negocio')
                    ->relationship('business', 'name')
                    ->searchable()
                    ->preload(),
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
                Tables\Actions\Action::make('close_ticket')
                    ->label('Cerrar Ticket')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'closed']);
                    })
                    ->visible(fn ($record) => $record->status !== 'closed'),
                Tables\Actions\Action::make('reopen_ticket')
                    ->label('Reabrir Ticket')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'open']);
                    })
                    ->visible(fn ($record) => $record->status === 'closed'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('close_selected')
                        ->label('Cerrar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 'closed']);
                            });
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'view' => Pages\ViewTicket::route('/{record}'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'open')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'open')->count() > 10 ? 'danger' : 'primary';
    }
}

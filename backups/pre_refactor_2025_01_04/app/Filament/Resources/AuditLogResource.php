<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Filament\Resources\AuditLogResource\RelationManagers;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-eye';
    protected static ?string $navigationLabel = 'Audit Logs';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('event')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('auditable_type')
                    ->label('Tipo de Modelo')
                    ->disabled(),
                Forms\Components\TextInput::make('auditable_id')
                    ->label('ID del Modelo')
                    ->disabled(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->disabled(),
                Forms\Components\Textarea::make('old_values')
                    ->label('Valores Anteriores')
                    ->formatStateUsing(fn($state) => json_encode($state, JSON_PRETTY_PRINT))
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('new_values')
                    ->label('Valores Nuevos')
                    ->formatStateUsing(fn($state) => json_encode($state, JSON_PRETTY_PRINT))
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('ip_address')
                    ->label('Dirección IP')
                    ->disabled(),
                Forms\Components\TextInput::make('user_agent')
                    ->label('User Agent')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('event')
                    ->label('Evento')
                    ->colors([
                        'success' => 'created',
                        'warning' => 'updated',
                        'danger' => 'deleted',
                        'info' => 'payment_processed',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('auditable_type')
                    ->label('Modelo')
                    ->formatStateUsing(fn($state) => class_basename($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('auditable_id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->default('Sistema')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->options([
                        'created' => 'Creado',
                        'updated' => 'Actualizado',
                        'deleted' => 'Eliminado',
                        'payment_processed' => 'Pago Procesado',
                    ]),
                Tables\Filters\Filter::make('created_at')
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
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}

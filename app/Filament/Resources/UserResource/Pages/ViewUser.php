<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\MaxWidth;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggle_lifetime')
                ->label('Cambiar Estado Permanente')
                ->icon('heroicon-o-star')
                ->color(fn () => $this->record->is_lifetime_paid ? 'danger' : 'success')
                ->action(function () {
                    $newState = !$this->record->is_lifetime_paid;
                    $this->record->update(['is_lifetime_paid' => $newState]);

                    \Filament\Notifications\Notification::make()
                        ->title($newState ? 'Cliente permanente activado' : 'Cliente permanente desactivado')
                        ->success()
                        ->send();

                    // Refrescar la página
                    $this->refreshFormData(['subscription_info']);
                }),

            Actions\Action::make('manage_subscription')
                ->label('Gestionar Suscripción')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\Select::make('plan_id')
                        ->label('Nuevo Plan')
                        ->options(function () {
                            try {
                                return \App\Models\Plan::where('is_active', true)->pluck('name', 'id');
                            } catch (\Exception $e) {
                                return [
                                    '1' => 'Plan Mensual - $9.99',
                                    '2' => 'Plan Anual - $99.99',
                                    '3' => 'Plan Premium - $19.99',
                                ];
                            }
                        })
                        ->required()
                        ->searchable(),
                    \Filament\Forms\Components\Toggle::make('auto_renew')
                        ->label('Renovación automática')
                        ->default(true),
                    \Filament\Forms\Components\DateTimePicker::make('period_end')
                        ->label('Fecha de vencimiento')
                        ->default(now()->addMonth())
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        // Verificar conexión a BD
                        try {
                            \Illuminate\Support\Facades\DB::connection()->getPdo();
                        } catch (\Exception $dbError) {
                            \Filament\Notifications\Notification::make()
                                ->title('Modo de demostración')
                                ->body('La funcionalidad está disponible pero la base de datos no está conectada.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Cancelar suscripciones activas
                        \App\Models\Subscription::where('user_id', $this->record->id)
                            ->whereIn('status', ['active', 'in_trial'])
                            ->update(['status' => 'canceled']);

                        // Crear nueva suscripción
                        $planNames = ['1' => 'Plan Mensual', '2' => 'Plan Anual', '3' => 'Plan Premium'];
                        $planName = $planNames[$data['plan_id']] ?? 'Plan Desconocido';

                        \App\Models\Subscription::create([
                            'user_id' => $this->record->id,
                            'plan_id' => $data['plan_id'],
                            'provider' => 'manual',
                            'status' => 'active',
                            'current_period_end' => $data['period_end'],
                            'auto_renew' => $data['auto_renew'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Suscripción actualizada exitosamente')
                            ->body("Usuario cambiado al plan: {$planName}")
                            ->success()
                            ->send();

                        $this->refreshFormData(['subscription_info']);
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error al actualizar suscripción')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('cancel_subscription')
                ->label('Cancelar Suscripción')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        try {
                            \Illuminate\Support\Facades\DB::connection()->getPdo();
                        } catch (\Exception $dbError) {
                            \Filament\Notifications\Notification::make()
                                ->title('Modo de demostración')
                                ->body('La funcionalidad está disponible pero la base de datos no está conectada.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $canceled = \App\Models\Subscription::where('user_id', $this->record->id)
                            ->whereIn('status', ['active', 'in_trial'])
                            ->update(['status' => 'canceled']);

                        if ($canceled > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Suscripción cancelada exitosamente')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('No hay suscripciones activas para cancelar')
                                ->warning()
                                ->send();
                        }

                        $this->refreshFormData(['subscription_info']);
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error al cancelar suscripción')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\EditAction::make()
                ->label('Editar Usuario')
                ->modalWidth(MaxWidth::SevenExtraLarge),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar datos de perfiles para la vista
        $user = $this->record;

        if ($user->adminProfile) {
            $data['adminProfile'] = $user->adminProfile->toArray();
        }

        if ($user->waiterProfile) {
            $data['waiterProfile'] = $user->waiterProfile->toArray();
        }

        return $data;
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::SevenExtraLarge;
    }
}
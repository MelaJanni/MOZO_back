<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;

class ViewUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Guardar Cambios')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('save'),
            Actions\DeleteAction::make(),
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

    public function updateSubscription($planId)
    {
        if (!$this->record) return;

        try {
            // Verificar conexión a BD
            try {
                \Illuminate\Support\Facades\DB::connection()->getPdo();
            } catch (\Exception $dbError) {
                \Filament\Notifications\Notification::make()
                    ->title('Modo demostración')
                    ->body('Base de datos no conectada - funcionaría en producción')
                    ->warning()
                    ->send();
                return;
            }

            if (!$planId) {
                // Si no hay plan, cancelar suscripciones activas
                $canceled = \App\Models\Subscription::where('user_id', $this->record->id)
                    ->whereIn('status', ['active', 'in_trial'])
                    ->update(['status' => 'canceled']);

                \Filament\Notifications\Notification::make()
                    ->title('Suscripción cancelada')
                    ->body('El usuario ya no tiene plan asignado')
                    ->success()
                    ->send();

                $this->refreshFormData(['auto_renew', 'applied_coupon', 'subscription_expires_at']);
                return;
            }

            // Actualizar suscripción existente o crear nueva
            $activeSubscription = \App\Models\Subscription::where('user_id', $this->record->id)
                ->whereIn('status', ['active', 'in_trial'])
                ->first();

            $planNames = [
                '1' => 'Plan Mensual',
                '2' => 'Plan Anual',
                '3' => 'Plan Premium'
            ];

            if ($activeSubscription) {
                // Actualizar suscripción existente
                $activeSubscription->update([
                    'plan_id' => $planId,
                ]);

                $planName = $planNames[$planId] ?? 'Plan desconocido';

                \Filament\Notifications\Notification::make()
                    ->title('Plan actualizado')
                    ->body("Suscripción cambiada a: {$planName}")
                    ->success()
                    ->send();
            } else {
                // Crear nueva suscripción
                \App\Models\Subscription::create([
                    'user_id' => $this->record->id,
                    'plan_id' => $planId,
                    'provider' => 'manual',
                    'status' => 'active',
                    'current_period_end' => now()->addMonth(),
                    'auto_renew' => true,
                ]);

                $planName = $planNames[$planId] ?? 'Plan desconocido';

                \Filament\Notifications\Notification::make()
                    ->title('Nueva suscripción creada')
                    ->body("Usuario suscrito a: {$planName}")
                    ->success()
                    ->send();
            }

            // Refrescar otros campos relacionados
            $this->refreshFormData(['auto_renew', 'applied_coupon', 'subscription_expires_at']);

        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al actualizar suscripción')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
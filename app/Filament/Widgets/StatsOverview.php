<?php

namespace App\Filament\Widgets;

use App\Models\{Business,User,Subscription,Payment};
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $biz = Business::count();
        $users = User::count();
        $activeSubs = Subscription::whereIn('status',[ 'active','in_trial' ])->count();
        $pendingSubs = Subscription::where('status', 'pending')->count();

        $mrr = Payment::where('status','paid')
            ->where('paid_at','>=',now()->subDays(30))
            ->sum('amount_cents')/100;

        $pendingPayments = Payment::where('status', 'pending')->count();

        return [
            Stat::make('Negocios Totales', (string) $biz)
                ->description('Registrados en el sistema')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),

            Stat::make('Usuarios', (string) $users)
                ->description('Total de usuarios')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Suscripciones Activas', (string) $activeSubs)
                ->description($pendingSubs > 0 ? "{$pendingSubs} pendientes" : 'Todas activas')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Ingresos (30d)', '$'.number_format($mrr,2))
                ->description($pendingPayments > 0 ? "{$pendingPayments} pagos pendientes" : 'Últimos 30 días')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
        ];
    }
}

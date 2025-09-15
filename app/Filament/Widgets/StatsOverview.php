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
        $mrr = Payment::where('status','paid')
            ->where('paid_at','>=',now()->subDays(30))
            ->sum('amount_cents')/100;

        return [
            Stat::make('Negocios', (string) $biz),
            Stat::make('Usuarios', (string) $users),
            Stat::make('Suscripciones activas', (string) $activeSubs),
            Stat::make('Ingresos 30d', '$'.number_format($mrr,2)),
        ];
    }
}

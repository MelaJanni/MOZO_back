<?php

namespace App\Filament\Pages;

use App\Models\Subscription;
use App\Models\Payment;
use App\Models\User;
use App\Models\AuditLog;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MetricsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Métricas';
    protected static ?string $title = 'Dashboard de Métricas';
    protected static string $view = 'filament.pages.metrics-dashboard';
    protected static ?int $navigationSort = 10;

    public function getHeaderWidgets(): array
    {
        return [
            MetricsOverview::class,
            RevenueChart::class,
            SubscriptionChart::class,
        ];
    }
}

class MetricsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            BaseWidget\Stat::make('Usuarios Activos', User::count())
                ->description('Total de usuarios registrados')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            BaseWidget\Stat::make('Suscripciones Activas', Subscription::where('status', 'active')->count())
                ->description('Suscripciones en estado activo')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('info'),

            BaseWidget\Stat::make('Ingresos del Mes', '$' . number_format($this->getMonthlyRevenue(), 0, ',', '.'))
                ->description('Ingresos de ' . now()->format('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            BaseWidget\Stat::make('Tasa de Conversión', $this->getConversionRate() . '%')
                ->description('Usuarios que se suscriben')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($this->getConversionRate() >= 10 ? 'success' : 'danger'),
        ];
    }

    private function getMonthlyRevenue(): float
    {
        return Payment::where('status', 'completed')
                     ->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year)
                     ->sum('amount');
    }

    private function getConversionRate(): float
    {
        $totalUsers = User::count();
        $subscribedUsers = User::whereHas('subscriptions', function($query) {
            $query->where('status', 'active');
        })->count();

        return $totalUsers > 0 ? round(($subscribedUsers / $totalUsers) * 100, 1) : 0;
    }
}

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Ingresos por Mes';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $months = collect();
        $revenues = collect();

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push($date->format('M Y'));

            $revenue = Payment::where('status', 'completed')
                            ->whereMonth('created_at', $date->month)
                            ->whereYear('created_at', $date->year)
                            ->sum('amount');

            $revenues->push($revenue);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos (ARS)',
                    'data' => $revenues->values()->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $months->values()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

class SubscriptionChart extends ChartWidget
{
    protected static ?string $heading = 'Estado de Suscripciones';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $statuses = Subscription::select('status', DB::raw('count(*) as count'))
                               ->groupBy('status')
                               ->pluck('count', 'status')
                               ->toArray();

        $colors = [
            'active' => '#10B981',
            'cancelled' => '#EF4444',
            'past_due' => '#F59E0B',
            'pending' => '#6B7280',
            'in_trial' => '#8B5CF6',
        ];

        return [
            'datasets' => [
                [
                    'data' => array_values($statuses),
                    'backgroundColor' => array_map(fn($status) => $colors[$status] ?? '#9CA3AF', array_keys($statuses)),
                ],
            ],
            'labels' => array_map(fn($status) => ucfirst(str_replace('_', ' ', $status)), array_keys($statuses)),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
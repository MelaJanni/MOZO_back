<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Plan;

class PublicPlansPage extends Page
{
    protected static string $view = 'filament.pages.public-plans';
    protected static ?string $title = 'Planes y Precios - MOZO QR';
    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        // Esta página no requiere autenticación
    }

    protected function getViewData(): array
    {
        return [
            'plans' => Plan::active()->ordered()->get(),
            'featuredPlan' => Plan::active()->featured()->first(),
            'popularPlan' => Plan::active()->popular()->first(),
        ];
    }
}
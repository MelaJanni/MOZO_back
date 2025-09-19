<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PublicSuccessPage extends Page
{
    protected static string $view = 'filament.pages.public-success';
    protected static ?string $title = '¡Pago Exitoso! - MOZO QR';
    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        // Esta página no requiere autenticación
    }
}
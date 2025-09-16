<?php

namespace App\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Assets\Js;

class FilamentSkeletonServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Registrar CSS personalizado para skeletons
        FilamentAsset::register([
            Css::make('skeleton-styles', resource_path('css/skeleton.css')),
            Js::make('skeleton-scripts', resource_path('js/skeleton.js')),
        ]);
    }
}
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\Staff;
use App\Observers\UserObserver;
use App\Observers\StaffObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Registrar el Observer para auto-crear WaiterProfile
        User::observe(UserObserver::class);
        Staff::observe(StaffObserver::class);
    }
}

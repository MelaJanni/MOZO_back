<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Business;
use App\Policies\BusinessPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
    Business::class => BusinessPolicy::class,
    ];

    public function boot(): void
    {
    // Policies are auto-registered from $policies array.
    }
}

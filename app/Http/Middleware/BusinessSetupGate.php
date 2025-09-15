<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BusinessSetupGate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar en rutas del panel admin (no en login)
        if (!$request->routeIs('filament.admin.*') || $request->routeIs('filament.admin.auth.*')) {
            return $next($request);
        }

        $user = auth()->user();

        // Skip for super admins
        if ($user && ($user->is_system_super_admin || $user->hasRole('super_admin'))) {
            return $next($request);
        }

        // Check business setup requirements
        if ($user && $this->needsBusinessSetup($user)) {
            // Redirect to onboarding wizard
            return redirect()->route('filament.admin.pages.business-setup');
        }

        return $next($request);
    }

    private function needsBusinessSetup($user): bool
    {
        // Check admin profile completeness
        $adminProfile = $user->adminProfile;
        if (!$adminProfile || !$adminProfile->isComplete()) {
            return true;
        }

        // Check business completeness for admins
        $businesses = $user->businessesAsAdmin()->where('business_admins.is_active', true)->get();

        foreach ($businesses as $business) {
            // Check required business fields
            if (!$business->name || !$business->address || !$business->phone || !$business->description) {
                return true;
            }

            // Check if business has at least one menu
            if ($business->menus()->count() === 0) {
                return true;
            }
        }

        return false;
    }
}
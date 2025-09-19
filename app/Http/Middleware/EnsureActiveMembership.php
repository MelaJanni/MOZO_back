<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveMembership
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        // Permitir algunas rutas aunque no tenga membresía (estado de cuenta, logout, pago)
        $path = $request->path();
        $allowed = [
            'api/user',
            'api/logout',
        ];
        // Permitir prefijos relacionados al flujo de pago/membresía
        $allowedPrefixes = [
            'api/membership',
            'api/billing',
            'api/payments',
        ];

        foreach ($allowed as $allowedExact) {
            if ($path === $allowedExact) {
                return $next($request);
            }
        }
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $next($request);
            }
        }

        // TEMPORAL: Dar acceso completo a todos los usuarios admin sin restricciones de plan
        // Los usuarios con rol admin pueden usar todas las funciones independientemente del plan
        if ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->is_lifetime_paid) {
            return $next($request);
        }

        if (!$user->hasActiveMembership()) {
            return response()->json([
                'success' => false,
                'code' => 'membership_required',
                'message' => 'Tu membresía no está activa. Necesitas pagar o renovar para continuar.',
                'membership' => [
                    'plan' => $user->membership_plan,
                    'expires_at' => $user->membership_expires_at,
                    'days_remaining' => $user->membershipDaysRemaining(),
                ],
            ], Response::HTTP_PAYMENT_REQUIRED);
        }

        return $next($request);
    }
}

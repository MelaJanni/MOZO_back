<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DevAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo permitir en ambiente de desarrollo
        if (app()->environment('local', 'development')) {
            // Verificar si hay un token de desarrollo en los headers
            $bearerToken = $request->bearerToken();
            
            if ($bearerToken === 'test_token_for_development_only') {
                // Crear un usuario de prueba y asignarlo a la request
                $user = new User();
                $user->id = 1;
                $user->name = 'Admin User';
                $user->email = 'admin@example.com';
                // Nota: no establecer $user->role; los permisos se basan en pivots y policies
                
                $request->setUserResolver(function () use ($user) {
                    return $user;
                });
                
                return $next($request);
            }
        }
        
        // Si no es un token de desarrollo, continuar con el flujo normal
        return $next($request);
    }
}

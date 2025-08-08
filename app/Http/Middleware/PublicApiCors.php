<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicApiCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Lista de orígenes permitidos para APIs públicas
        $allowedOrigins = [
            'https://mozoqr.com',
            'https://www.mozoqr.com',
            'http://mozoqr.com',
            'http://www.mozoqr.com',
        ];

        // En desarrollo, permitir más orígenes
        if (app()->environment(['local', 'development', 'testing'])) {
            $allowedOrigins = array_merge($allowedOrigins, [
                'http://localhost:3000',
                'http://localhost:5173',
                'http://127.0.0.1:3000',
                'http://127.0.0.1:5173',
                'https://localhost:3000',
                'https://localhost:5173',
            ]);
        }

        $origin = $request->header('Origin');
        
        // Si no hay origin (misma origin) o está en la lista permitida
        if (!$origin || in_array($origin, $allowedOrigins) || app()->environment('local')) {
            $response = $next($request);
            
            // Añadir headers CORS
            $response->headers->set('Access-Control-Allow-Origin', $origin ?: '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Accept, Authorization, Content-Type, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'false');
            
            // Para requests OPTIONS (preflight)
            if ($request->getMethod() === 'OPTIONS') {
                $response->headers->set('Access-Control-Max-Age', '3600');
                return response('', 200)->withHeaders($response->headers->all());
            }
            
            return $response;
        }

        // Si el origin no está permitido, continuar sin headers CORS
        return $next($request);
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DefinitiveCors
{
    /**
     * Handle an incoming request.
     * This middleware ALWAYS allows ALL origins and handles ALL CORS scenarios.
     * ULTRA DEFINITIVO - intercepta TODO antes que cualquier otra cosa.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // SIEMPRE añadir headers CORS ANTES de procesar la request
        $this->addCorsHeaders($request);

        // Handle preflight OPTIONS requests INMEDIATAMENTE
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 200);
            $this->addCorsHeaders($request, $response);
            return $response;
        }

        try {
            $response = $next($request);
        } catch (\Exception $e) {
            // Si hay error, crear respuesta y añadir CORS
            $response = response()->json(['error' => $e->getMessage()], 500);
        }

        // GARANTIZAR que la respuesta tenga headers CORS
        $this->addCorsHeaders($request, $response);

        return $response;
    }

    /**
     * Añade headers CORS SOLO a la respuesta (sin duplicar)
     */
    private function addCorsHeaders(Request $request, Response $response = null)
    {
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD',
            'Access-Control-Allow-Headers' => 'Accept, Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Origin',
            'Access-Control-Expose-Headers' => 'Authorization, X-CSRF-TOKEN',
            'Access-Control-Allow-Credentials' => 'false', // Cambiado a false para permitir *
            'Access-Control-Max-Age' => '86400',
        ];

        // Solo añadir a la respuesta (evitar duplicación)
        if ($response) {
            foreach ($headers as $key => $value) {
                // Usar replace en lugar de set para evitar duplicados
                $response->headers->set($key, $value, true);
            }
        }
    }
}
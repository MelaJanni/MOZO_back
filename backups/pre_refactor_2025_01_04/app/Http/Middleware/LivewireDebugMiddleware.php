<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LivewireDebugMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Solo para rutas de Livewire
        if (!$request->is('livewire/*')) {
            return $next($request);
        }

        Log::channel('livewire')->info('Livewire request started', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'memory_start' => memory_get_usage(true),
            'time_start' => microtime(true)
        ]);

        try {
            $response = $next($request);

            Log::channel('livewire')->info('Livewire request completed successfully', [
                'status' => $response->getStatusCode(),
                'memory_end' => memory_get_usage(true),
                'time_end' => microtime(true)
            ]);

            return $response;

        } catch (\Throwable $e) {
            Log::channel('livewire')->error('Livewire request failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'memory_peak' => memory_get_peak_usage(true),
                'request_data' => $request->all()
            ]);

            // Re-lanzar la excepción para que Laravel la maneje normalmente
            throw $e;
        }
    }
}
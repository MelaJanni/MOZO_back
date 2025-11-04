<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FilamentRequestLogger
{
    public function handle(Request $request, Closure $next)
    {
        $path = ltrim($request->path(), '/');
        if (!str_starts_with($path, 'filament') && !str_starts_with($path, 'admin')) {
            return $next($request);
        }

        $start = microtime(true);
        Log::channel('livewire')->info('Filament request started', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        try {
            $response = $next($request);
            Log::channel('livewire')->info('Filament request finished', [
                'status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
                'duration_ms' => round((microtime(true) - $start) * 1000),
            ]);
            return $response;
        } catch (\Throwable $e) {
            Log::channel('livewire')->error('Filament request failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}

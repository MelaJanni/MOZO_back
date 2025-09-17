<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DetailedErrorLogger
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Log request details
        Log::info('=== REQUEST START ===', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'input' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
            'csrf_token' => $request->session()->token(),
            'memory_start' => $this->formatBytes($startMemory),
            'time_start' => date('Y-m-d H:i:s.u')
        ]);

        try {
            $response = $next($request);

            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            $duration = $endTime - $startTime;

            Log::info('=== REQUEST SUCCESS ===', [
                'status' => $response->getStatusCode(),
                'duration_seconds' => round($duration, 4),
                'memory_used' => $this->formatBytes($endMemory - $startMemory),
                'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
                'response_size' => strlen($response->getContent()),
                'time_end' => date('Y-m-d H:i:s.u')
            ]);

            return $response;

        } catch (Throwable $e) {
            $endTime = microtime(true);
            $duration = $endTime - $startTime;

            Log::error('=== REQUEST FAILED ===', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_type' => get_class($e),
                'duration_seconds' => round($duration, 4),
                'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
                'stack_trace' => $e->getTraceAsString(),
                'previous_exception' => $e->getPrevious() ? [
                    'message' => $e->getPrevious()->getMessage(),
                    'file' => $e->getPrevious()->getFile(),
                    'line' => $e->getPrevious()->getLine()
                ] : null,
                'time_end' => date('Y-m-d H:i:s.u')
            ]);

            // Re-throw the exception
            throw $e;
        }
    }

    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision) . ' ' . $units[$i];
    }
}
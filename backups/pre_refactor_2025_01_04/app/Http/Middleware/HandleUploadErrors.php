<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleUploadErrors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si es una petición de subida que puede fallar por tamaño
        if ($request->isMethod('POST') && $this->isUploadRoute($request)) {
            // Verificar límites PHP antes de procesar
            $contentLength = $request->header('Content-Length');
            $maxPostSize = $this->parseSize(ini_get('post_max_size'));
            
            if ($contentLength && $contentLength > $maxPostSize) {
                return response()->json([
                    'success' => false,
                    'error' => 'REQUEST_TOO_LARGE',
                    'message' => 'El archivo es demasiado grande para el servidor.',
                    'details' => [
                        'content_length' => $this->formatBytes($contentLength),
                        'max_allowed' => ini_get('post_max_size'),
                        'server_limit' => $this->formatBytes($maxPostSize)
                    ],
                    'recommendations' => [
                        'Reducir el tamaño del archivo',
                        'Usar formatos comprimidos',
                        'Contactar al administrador para aumentar límites'
                    ],
                    'diagnosis_url' => url('/api/menus/upload-limits')
                ], 413);
            }
        }

        try {
            return $next($request);
        } catch (\Exception $e) {
            // Capturar errores 413 que no fueron manejados
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $e->getStatusCode() === 413) {
                return response()->json([
                    'success' => false,
                    'error' => 'REQUEST_TOO_LARGE',
                    'message' => 'El archivo es demasiado grande para el servidor.',
                    'technical_details' => $e->getMessage(),
                    'diagnosis_url' => url('/api/menus/upload-limits')
                ], 413);
            }
            
            throw $e;
        }
    }

    /**
     * Verificar si es una ruta de subida
     */
    private function isUploadRoute(Request $request): bool
    {
        $uploadRoutes = [
            'api/menus',
            'api/menus/upload',
            'api/upload'
        ];
        
        $path = trim($request->getPathInfo(), '/');
        
        foreach ($uploadRoutes as $uploadRoute) {
            if (str_starts_with($path, $uploadRoute)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Parsear tamaño de string a bytes
     */
    private function parseSize($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }

    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

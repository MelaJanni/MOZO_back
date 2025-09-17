<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
    'api/*',              // Excluir todas las rutas API del CSRF
    'qr/call-waiter',     // Llamado de mozo desde página pública QR (se maneja con validaciones propias)
    'debug-502-test',     // Endpoint de debug para troubleshooting
    'webhooks/*',         // Excluir webhooks del CSRF
    'livewire/update',    // Temporal fix para Livewire - revisar configuración CSRF
    ];
}

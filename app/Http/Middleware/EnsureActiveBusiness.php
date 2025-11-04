<?php

namespace App\Http\Middleware;

use App\Services\BusinessResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que resuelve y valida el business_id activo del usuario autenticado.
 * 
 * Inyecta el business_id en el request para que los controllers puedan usarlo directamente:
 * - $request->business_id
 * - $request->attributes->get('business_id')
 * 
 * Parámetros configurables en rutas:
 * - role: Rol requerido ('admin', 'waiter', null)
 * - require: Si es true, falla si no se encuentra business (default: true)
 * 
 * Ejemplo de uso en routes/api.php:
 * Route::middleware(['auth:sanctum', 'business:admin'])->group(function () {
 *     Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
 * });
 * 
 * @package App\Http\Middleware
 */
class EnsureActiveBusiness
{
    /**
     * @var BusinessResolver
     */
    protected BusinessResolver $resolver;

    public function __construct(BusinessResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Maneja una solicitud entrante.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $role Rol requerido ('admin', 'waiter', null)
     * @param string $require Si es 'true', requiere business_id (default: true)
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $role = null, string $require = 'true'): Response
    {
        $user = $request->user();

        // Si no hay usuario autenticado, dejar pasar (auth middleware se encargará)
        if (!$user) {
            return $next($request);
        }

        $requireBusiness = $require === 'true';

        // Resolver business_id activo (sin lanzar excepciones)
        try {
            $businessId = $this->resolver->resolve($user, $role, false);

            // Si no se encontró business_id y es requerido
            if (!$businessId && $requireBusiness) {
                Log::warning('EnsureActiveBusiness: business_id no encontrado para usuario', [
                    'user_id' => $user->id,
                    'role' => $role,
                    'route' => $request->path()
                ]);

                return response()->json([
                    'error' => 'No active business found',
                    'message' => 'You must have an active business to access this resource.',
                    'requires_business_setup' => true,
                    'code' => 'NO_ACTIVE_BUSINESS'
                ], 403);
            }

            // Inyectar business_id en el request
            if ($businessId) {
                $request->merge(['business_id' => $businessId]);
                $request->attributes->set('business_id', $businessId);
                $request->attributes->set('business_role', $role);

                Log::debug('EnsureActiveBusiness: business_id inyectado', [
                    'user_id' => $user->id,
                    'business_id' => $businessId,
                    'role' => $role,
                    'route' => $request->path()
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('EnsureActiveBusiness: Error inesperado al resolver business_id', [
                'user_id' => $user->id,
                'role' => $role,
                'error' => $e->getMessage(),
                'route' => $request->path()
            ]);

            return response()->json([
                'error' => 'Business resolution error',
                'message' => 'An unexpected error occurred while resolving your business.',
                'code' => 'BUSINESS_RESOLUTION_ERROR'
            ], 500);
        }

        return $next($request);
    }

    /**
     * Middleware alias para rol 'admin'.
     * Usar en rutas: ->middleware('business.admin')
     */
    public static function admin(): string
    {
        return 'business:admin,true';
    }

    /**
     * Middleware alias para rol 'waiter'.
     * Usar en rutas: ->middleware('business.waiter')
     */
    public static function waiter(): string
    {
        return 'business:waiter,true';
    }

    /**
     * Middleware alias sin rol específico.
     * Usar en rutas: ->middleware('business.any')
     */
    public static function any(): string
    {
        return 'business:null,true';
    }

    /**
     * Middleware alias opcional (no requiere business).
     * Usar en rutas: ->middleware('business.optional')
     */
    public static function optional(): string
    {
        return 'business:null,false';
    }
}

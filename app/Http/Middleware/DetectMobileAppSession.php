<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class DetectMobileAppSession
{
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si es una sesión de la app móvil
        $mobileApiKey = $request->header('X-Mobile-API-Key');
        $mobileUserId = $request->header('X-Mobile-User-ID');
        $mobileToken = $request->header('X-Mobile-Token');

        // También verificar en query parameters para enlaces directos
        if (!$mobileApiKey && $request->has('mobile_api_key')) {
            $mobileApiKey = $request->get('mobile_api_key');
            $mobileUserId = $request->get('mobile_user_id');
            $mobileToken = $request->get('mobile_token');
        }

        if ($mobileApiKey && $mobileUserId && $mobileToken) {
            // Verificar que la API key es válida
            if ($mobileApiKey === config('app.mobile_api_key', env('MOBILE_APP_API_KEY'))) {
                // Buscar el usuario y validar el token
                $user = User::find($mobileUserId);

                if ($user && $this->validateMobileToken($user, $mobileToken)) {
                    // Autenticar al usuario para la sesión web
                    Auth::login($user);

                    // Marcar en la sesión que viene de la app móvil
                    session(['mobile_app_session' => true]);
                    session(['mobile_user_id' => $mobileUserId]);
                }
            }
        }

        return $next($request);
    }

    private function validateMobileToken(User $user, string $token): bool
    {
        // Aquí puedes implementar la validación del token móvil
        // Por ejemplo, verificar que el token no haya expirado o que sea válido

        // Implementación simple: verificar que el token no esté vacío
        // En producción, deberías implementar una validación más robusta
        return !empty($token) && strlen($token) >= 10;
    }
}
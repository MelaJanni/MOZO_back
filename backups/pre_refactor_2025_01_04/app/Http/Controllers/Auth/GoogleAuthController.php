<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle(Request $request)
    {
        $clientId = config('services.google.client_id');
        $redirectUri = config('services.google.redirect_uri');

        if (!$clientId) {
            return back()->withErrors(['google' => 'Google OAuth no está configurado.']);
        }

        // Guardar el plan_id en la sesión si viene del checkout
        if ($request->has('plan_id')) {
            session(['checkout_plan_id' => $request->get('plan_id')]);
        }

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'email profile',
            'response_type' => 'code',
            'state' => csrf_token(),
        ]);

        return redirect("https://accounts.google.com/o/oauth2/auth?{$params}");
    }

    public function handleGoogleCallback(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');

        // Verificar el state token
        if ($state !== csrf_token()) {
            return redirect()->route('login')->withErrors(['google' => 'Error de autenticación.']);
        }

        if (!$code) {
            return redirect()->route('login')->withErrors(['google' => 'Error al obtener código de Google.']);
        }

        try {
            // Intercambiar código por token
            $tokenResponse = Http::post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('services.google.redirect_uri'),
            ]);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Error al obtener token de Google');
            }

            $token = $tokenResponse->json()['access_token'];

            // Obtener información del usuario
            $userResponse = Http::withToken($token)->get('https://www.googleapis.com/oauth2/v2/userinfo');

            if (!$userResponse->successful()) {
                throw new \Exception('Error al obtener información del usuario');
            }

            $googleUser = $userResponse->json();

            // Buscar o crear usuario
            $user = User::where('email', $googleUser['email'])->first();

            if (!$user) {
                // Crear usuario SIN observer para evitar duplicados
                $user = new User();
                $user->name = $googleUser['name'];
                $user->email = $googleUser['email'];
                $user->email_verified_at = now();
                $user->password = bcrypt(Str::random(32)); // Password aleatoria
                $user->google_id = $googleUser['id'];
                $user->avatar_url = $googleUser['picture'] ?? null;
                $user->saveQuietly(); // Sin disparar events/observers

                // Crear WaiterProfile manualmente de forma segura
                \App\Models\WaiterProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'display_name' => $user->name,
                        'is_available' => true,
                        'is_available_for_hire' => true,
                    ]
                );
            } else {
                // Actualizar información de Google si no la tiene
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser['id'],
                        'avatar_url' => $googleUser['picture'] ?? $user->avatar_url,
                        'email_verified_at' => $user->email_verified_at ?? now(),
                    ]);
                }
            }

            // Autenticar usuario
            Auth::login($user);

            // Redirigir al checkout del plan si existe
            $planId = session('checkout_plan_id');
            if ($planId) {
                session()->forget('checkout_plan_id');
                return redirect()->route('public.checkout.plan', $planId);
            }

            // Redirigir a la página principal
            return redirect('/')->with('success', '¡Sesión iniciada exitosamente con Google!');

        } catch (\Exception $e) {
            Log::error('Error en autenticación Google', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')->withErrors(['google' => 'Error al autenticar con Google. Intenta nuevamente.']);
        }
    }
}
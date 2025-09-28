<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PublicAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        return view('auth.login');
    }

    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        return view('auth.register');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Redirigir al checkout si hay un plan en sesión
            if (session('checkout_plan_id')) {
                $planId = session('checkout_plan_id');
                session()->forget('checkout_plan_id');
                return redirect()->route('public.checkout.plan', $planId);
            }

            return redirect()->intended('/');
        }

        throw ValidationException::withMessages([
            'email' => ['Las credenciales proporcionadas no coinciden con nuestros registros.'],
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Usar transacción para evitar condiciones de carrera
            $user = \DB::transaction(function () use ($request) {
                // Verificar una vez más si el usuario existe (por si acaso)
                $existingUser = User::where('email', $request->email)->first();
                if ($existingUser) {
                    throw new \Exception('El usuario ya existe');
                }

                // Crear usuario SIN observer para evitar duplicados
                $user = new User();
                $user->name = $request->name;
                $user->email = $request->email;
                $user->password = Hash::make($request->password);
                $user->email_verified_at = now();
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

                return $user;
            });

            Auth::login($user);

            // Redirigir al checkout si hay un plan en sesión
            if (session('checkout_plan_id')) {
                $planId = session('checkout_plan_id');
                session()->forget('checkout_plan_id');
                return redirect()->route('public.checkout.plan', $planId);
            }

            return redirect('/')->with('success', '¡Cuenta creada exitosamente!');

        } catch (\Exception $e) {
            // Si hay error de duplicados, intentar hacer login
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'already exists')) {
                $user = User::where('email', $request->email)->first();
                if ($user) {
                    Auth::login($user);
                    return redirect('/')->with('success', '¡Sesión iniciada exitosamente!');
                }
            }

            \Log::error('Error en registro', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withErrors(['email' => 'Error al crear la cuenta. Intenta nuevamente.'])
                ->withInput();
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
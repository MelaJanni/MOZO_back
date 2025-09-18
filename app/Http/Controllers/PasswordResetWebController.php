<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\User;

class PasswordResetWebController extends Controller
{
    /**
     * Mostrar el formulario de reset de contraseña
     */
    public function showResetForm(Request $request, $token = null)
    {
        $email = $request->query('email');

        // Validar que tenemos token y email
        if (!$token || !$email) {
            return view('auth.reset-password-error', [
                'error' => 'Enlace de restablecimiento inválido. Por favor, solicita un nuevo enlace de restablecimiento.'
            ]);
        }

        // Verificar que el token es válido
        $user = User::where('email', $email)->first();
        if (!$user) {
            return view('auth.reset-password-error', [
                'error' => 'Usuario no encontrado. Verifica que el email sea correcto.'
            ]);
        }

        // Verificar que el token no ha expirado
        $tokenData = \DB::table('password_reset_tokens')->where('email', $email)->first();
        if (!$tokenData || !Hash::check($token, $tokenData->token)) {
            return view('auth.reset-password-error', [
                'error' => 'El enlace de restablecimiento ha expirado o es inválido. Por favor, solicita un nuevo enlace.'
            ]);
        }

        // Si todo está bien, mostrar el formulario
        return view('auth.reset-password', compact('token', 'email'));
    }

    /**
     * Procesar el reset de contraseña desde el formulario web
     */
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ], [
                'password.required' => 'La contraseña es obligatoria.',
                'password.confirmed' => 'Las contraseñas no coinciden.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'email.required' => 'El email es obligatorio.',
                'email.email' => 'El email debe ser válido.',
                'token.required' => 'Token de verificación requerido.',
            ]);

            // Usar el sistema de reset de Laravel
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ]);

                    $user->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return view('auth.reset-password-success', [
                    'message' => 'Tu contraseña ha sido restablecida exitosamente. Ya puedes iniciar sesión en la aplicación con tu nueva contraseña.'
                ]);
            }

            // Si hay error, volver al formulario con mensaje
            return back()->withErrors(['email' => [trans($status)]]);

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput($request->except('password', 'password_confirmation'));
        } catch (\Exception $e) {
            \Log::error('Error en reset de contraseña web', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);

            return view('auth.reset-password-error', [
                'error' => 'Ocurrió un error inesperado. Por favor, inténtalo de nuevo o contacta al soporte.'
            ]);
        }
    }

    /**
     * Mostrar formulario para solicitar reset de contraseña
     */
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Enviar enlace de reset de contraseña
     */
    public function sendResetLinkEmail(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ], [
                'email.required' => 'El email es obligatorio.',
                'email.email' => 'El email debe ser válido.',
            ]);

            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return view('auth.forgot-password-sent', [
                    'email' => $request->email,
                    'message' => 'Te hemos enviado un enlace de restablecimiento de contraseña a tu correo electrónico.'
                ]);
            }

            return back()->withErrors(['email' => [trans($status)]]);

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Error enviando enlace de reset', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? 'N/A'
            ]);

            return back()->withErrors(['email' => ['Ocurrió un error inesperado. Por favor, inténtalo de nuevo.']]);
        }
    }
}
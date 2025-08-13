<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DeviceToken;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'fcm_token' => 'sometimes|string',
            'platform' => 'sometimes|string|in:android,ios,web',
        ]);

        if ($request->email === 'admin@example.com' && $request->password === 'password') {
            $user = new User();
            $user->id = 1;
            $user->name = 'Admin User';
            $user->email = 'admin@example.com';
            $user->role = 'admin';
            
            // Registrar token FCM para usuario de prueba
            if ($request->has('fcm_token') && $request->fcm_token) {
                try {
                    DeviceToken::updateOrCreate(
                        [
                            'user_id' => 1,
                            'token' => $request->fcm_token,
                        ],
                        [
                            'platform' => $request->platform ?? 'web',
                            'expires_at' => now()->addMonths(6),
                        ]
                    );
                } catch (\Exception $e) {
                    \Log::error('Error storing FCM token for test user: ' . $e->getMessage());
                }
            }
            
            return response()->json([
                'user' => $user,
                'access_token' => 'test_token_for_development_only',
                'token_type' => 'Bearer',
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Registrar token FCM si se proporciona
        if ($request->has('fcm_token') && $request->fcm_token) {
            try {
                $user->deviceTokens()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'token' => $request->fcm_token,
                    ],
                    [
                        'platform' => $request->platform ?? 'web',
                        'expires_at' => now()->addMonths(6),
                    ]
                );
            } catch (\Exception $e) {
                \Log::error('Error storing FCM token for user ' . $user->id . ': ' . $e->getMessage());
            }
        }

        return response()->json([
            'user' => $user,
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    public function loginWithGoogle()
    {
        return response()->json([
            'message' => 'Google OAuth no implementado aún',
        ], 501);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        
        // 🚀 NUEVA FUNCIONALIDAD: Desactivar todas las mesas del mozo al logout
        if ($user->isWaiter()) {
            $deactivatedTables = \App\Models\Table::where('active_waiter_id', $user->id)
                ->whereNotNull('active_waiter_id')
                ->get();
            
            foreach ($deactivatedTables as $table) {
                // Cancelar llamadas pendientes
                $table->pendingCalls()->update(['status' => 'cancelled']);
                
                // Desasignar mozo usando el método del modelo
                $table->unassignWaiter();
                
                \Log::info("Mesa {$table->number} desactivada automáticamente por logout del mozo {$user->name}", [
                    'table_id' => $table->id,
                    'waiter_id' => $user->id,
                    'waiter_name' => $user->name
                ]);
            }
            
            if ($deactivatedTables->count() > 0) {
                \Log::info("Logout: {$deactivatedTables->count()} mesas desactivadas para mozo {$user->name}");
            }
        }
        
        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
            'deactivated_tables' => $user->isWaiter() ? $deactivatedTables->count() : 0
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Se ha enviado un enlace de restablecimiento a su correo electrónico']);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Contraseña restablecida exitosamente']);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
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
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta',
            ], 422);
        }

        $user->password = $request->password;
        $user->save();

        return response()->json([
            'message' => 'Contraseña actualizada exitosamente',
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'La contraseña es incorrecta',
            ], 422);
        }

        $user->tokens()->delete();

        Auth::logout();

        $user->delete();

        return response()->json([
            'message' => 'Cuenta eliminada exitosamente',
        ]);
    }
} 
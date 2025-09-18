<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DeviceToken;
use App\Models\Staff;
use App\Models\Business;
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
    /**
     * Public endpoint to check if an email is already registered.
     * POST /api/check-user-exists { email }
     */
    public function checkUserExists(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->input('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'exists' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'exists' => true,
            'user_id' => $user->id,
            'has_google_account' => !empty($user->google_id),
            'name' => $user->name,
        ]);
    }

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

    public function loginWithGoogle(Request $request)
    {
        $request->validate([
            'google_token' => 'required|string',
            'fcm_token' => 'sometimes|string',
            'platform' => 'sometimes|string|in:android,ios,web',
            'business_invitation_code' => 'sometimes|string'
        ]);

        return \DB::transaction(function () use ($request) {
            try {
                // Verificar token de Google
                $googleUser = $this->verifyGoogleToken($request->google_token);

                if (!$googleUser) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Token de Google inválido'
                    ], 401);
                }

                // Normalizar email y buscar usuario existente por email o Google ID
                $normalizedEmail = strtolower(trim($googleUser['email']));
                $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
                           ->orWhere('google_id', $googleUser['sub'])
                           ->orWhere('email', $googleUser['email']) // Búsqueda exacta adicional
                           ->first();

                if ($user) {
                    \Log::info('Google login: Found existing user', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'has_google_id' => !empty($user->google_id)
                    ]);

                    // Usuario existente - actualizar información de Google si es necesario
                    if (!$user->google_id || $user->google_id !== $googleUser['sub']) {
                        $user->update([
                            'google_id' => $googleUser['sub'],
                            'google_avatar' => $googleUser['picture'] ?? null
                        ]);
                        \Log::info('Google login: Updated user Google info', ['user_id' => $user->id]);
                    }
                } else {
                    // Crear nuevo usuario dentro de la transacción con manejo de duplicados
                    try {
                        $user = User::create([
                            'name' => $googleUser['name'],
                            'email' => $normalizedEmail, // Usar email normalizado
                            'google_id' => $googleUser['sub'],
                            'google_avatar' => $googleUser['picture'] ?? null,
                            'email_verified_at' => now(),
                            'password' => Hash::make(Str::random(32)), // Contraseña aleatoria
                        ]);

                        // User created successfully
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Si hay error de duplicado, buscar el usuario existente
                        if ($e->errorInfo[1] == 1062) { // Duplicate entry error
                            // Duplicate user detected, searching existing user

                            // Buscar con normalización de email y Google ID (incluyendo soft deletes)
                            $user = User::withTrashed()
                                       ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
                                       ->orWhere('google_id', $googleUser['sub'])
                                       ->first();

                            // Si aún no lo encuentra, buscar de forma más amplia
                            if (!$user) {
                                $user = User::withTrashed()
                                          ->where(function ($query) use ($googleUser, $normalizedEmail) {
                                              $query->whereRaw('LOWER(TRIM(email)) LIKE ?', ['%' . $normalizedEmail . '%'])
                                                    ->orWhere('google_id', $googleUser['sub'])
                                                    ->orWhereRaw('REPLACE(LOWER(TRIM(email)), " ", "") = ?', [str_replace(' ', '', $normalizedEmail)])
                                                    ->orWhere('email', $googleUser['email']); // Búsqueda exacta original
                                          })->first();
                            }

                            // Si lo encontramos pero está soft deleted, restaurarlo
                            if ($user && $user->trashed()) {
                                $user->restore();
                            }

                            if (!$user) {
                                // Could not find duplicate user, create with different approach
                                throw $e; // Re-throw if we can't find the user
                            }

                            // Actualizar información de Google si es necesario
                            if (!$user->google_id || $user->google_id !== $googleUser['sub']) {
                                $user->update([
                                    'google_id' => $googleUser['sub'],
                                    'google_avatar' => $googleUser['picture'] ?? null
                                ]);
                            }

                            // Using existing user after duplicate detection
                        } else {
                            throw $e; // Re-throw other database errors
                        }
                    }

                    // Solo para usuarios realmente nuevos (no duplicados encontrados)
                    if (isset($user) && $user->wasRecentlyCreated) {
                        // Establecer rol por fuera del fillable
                        $user->role = 'waiter';
                        $user->save();

                        // Role set successfully

                        // Crear perfil básico de mozo
                        if (method_exists($user, 'waiterProfile')) {
                            try {
                                $user->waiterProfile()->create([
                                    'display_name' => $user->name,
                                ]);
                                // Waiter profile created successfully
                            } catch (\Exception $e) {
                                // Failed to create waiter profile, continue with login
                            }
                        }
                    } else {
                        // Skipping profile creation for existing user
                    }

                    $staffRequestCreated = false;
                    $businessName = null;

                    // Si se proporciona código de invitación, crear Staff request
                    if ($request->has('business_invitation_code') && $request->business_invitation_code) {
                        $business = Business::where('invitation_code', $request->business_invitation_code)->first();

                        if ($business) {
                            // Crear solicitud de staff automáticamente
                            $staffRequest = Staff::create([
                                'business_id' => $business->id,
                                'user_id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email,
                                'status' => 'pending',
                                'position' => 'Mozo',
                            ]);

                            $staffRequestCreated = true;
                            $businessName = $business->name;

                            // Asignar business activo
                            $user->active_business_id = $business->id;
                            $user->save();

                            // Agregar usuario al business
                            $user->businesses()->attach($business->id);

                            // TODO: Re-enable Firebase notification when websockets issue is resolved
                            // Staff request created successfully
                        }
                    }
                }

                // Registrar token FCM si se proporciona (no crítico para el login)
                \DB::afterCommit(function () use ($user, $request) {
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
                            \Log::warning('Error storing FCM token for Google user ' . $user->id . ': ' . $e->getMessage());
                        }
                    }
                });

                $token = $user->createToken('auth_token')->plainTextToken;

                // Return minimal user data to avoid serialization issues
                $userData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->google_id,
                    'google_avatar' => $user->google_avatar,
                    'is_system_super_admin' => $user->is_system_super_admin,
                    'is_lifetime_paid' => $user->is_lifetime_paid,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];

                return response()->json([
                    'success' => true,
                    'user' => $userData,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'staff_request_created' => $staffRequestCreated ?? false,
                    'business_name' => $businessName ?? null,
                    'message' => ($staffRequestCreated ?? false)
                        ? "Bienvenido {$user->name}. Tu solicitud para trabajar en {$businessName} ha sido enviada."
                        : "Bienvenido {$user->name}. Puedes unirte a un negocio usando un código de invitación."
                ]);

            } catch (\Exception $e) {
                // Log only critical errors to avoid permission issues
                error_log('Google login error: ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Error al autenticar con Google'
                ], 500);
            }
        });
    }

    /**
     * Verificar token de Google OAuth
     */
    private function verifyGoogleToken(string $token): ?array
    {
        try {
            // Verificar el token con Google con reintentos
            $response = \Illuminate\Support\Facades\Http::retry(2, 1000)
                ->timeout(15)
                ->get('https://oauth2.googleapis.com/tokeninfo', [
                    'id_token' => $token
                ]);

            if (!$response->successful()) {
                \Log::warning('Google token verification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'attempt' => 'with_retry'
                ]);
                return null;
            }

            $data = $response->json();

            // Verificar que el token sea válido
            if (!isset($data['aud']) || !isset($data['email'])) {
                \Log::warning('Invalid Google token data', ['data' => $data]);
                return null;
            }

            // Opcional: Verificar audience (client ID)
            $expectedAudience = config('services.google.client_id');
            if ($expectedAudience && $data['aud'] !== $expectedAudience) {
                \Log::warning('Google token audience mismatch', [
                    'expected' => $expectedAudience,
                    'received' => $data['aud']
                ]);
                return null;
            }

            // Google token verified successfully

            return $data;

        } catch (\Exception $e) {
            error_log('Error verifying Google token: ' . $e->getMessage());
            return null;
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        // Si el cliente env aia 'token' en el body, eliminar ese token concreto
        try {
            if ($request->has('token') && $request->token) {
                $deleted = $user->deviceTokens()->where('token', $request->token)->delete();
                \Log::info("Deleted device token on logout for user {$user->id}: " . ($deleted ? 'deleted' : 'not found'));
            } else {
                // Eliminar todos los device tokens del usuario para evitar recibir notificaciones
                $count = $user->deviceTokens()->count();
                if ($count > 0) {
                    $user->deviceTokens()->delete();
                    \Log::info("Deleted {$count} device tokens for user {$user->id} on logout");
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error deleting device token(s) on logout for user ' . $user->id . ': ' . $e->getMessage());
        }

        // Desactivar mesas asignadas si era mozo (mismo comportamiento que antes)
        $deactivatedTablesCount = 0;
        if ($user->isWaiter()) {
            $deactivatedTables = \App\Models\Table::where('active_waiter_id', $user->id)
                ->whereNotNull('active_waiter_id')
                ->get();

            foreach ($deactivatedTables as $table) {
                // Cancelar llamadas pendientes
                try {
                    $table->pendingCalls()->update(['status' => 'cancelled']);
                } catch (\Exception $e) {
                    \Log::warning('Could not cancel pending calls for table ' . $table->id . ': ' . $e->getMessage());
                }

                // Desasignar mozo usando el m todo del modelo si existe
                try {
                    if (method_exists($table, 'unassignWaiter')) {
                        $table->unassignWaiter();
                    } else {
                        $table->active_waiter_id = null;
                        $table->save();
                    }
                } catch (\Exception $e) {
                    \Log::warning('Could not unassign waiter for table ' . $table->id . ': ' . $e->getMessage());
                }

                \Log::info("Mesa {$table->number} desactivada por logout del mozo {$user->name}", ['table_id' => $table->id, 'waiter_id' => $user->id]);
            }

            $deactivatedTablesCount = isset($deactivatedTables) ? $deactivatedTables->count() : 0;
        }

        // Borrar token de acceso actual
        try {
            $current = $user->currentAccessToken();
            if ($current) $current->delete();
        } catch (\Exception $e) {
            \Log::warning('Failed to delete current access token for user ' . $user->id . ': ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Sesi n cerrada exitosamente',
            'deactivated_tables' => $deactivatedTablesCount
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
            'business_invitation_code' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        // Marcar email como verificado desde el inicio (solo persistencia, no se usa para autorizar)
        try { $user->email_verified_at = now(); $user->save(); } catch (\Throwable $e) { /* noop */ }
        // Establecer rol por fuera del fillable
        try { $user->role = 'waiter'; $user->save(); } catch (\Throwable $e) { /* noop */ }

        // Crear perfil básico del mozo
        if (method_exists($user, 'waiterProfile')) {
            $user->waiterProfile()->create([
                'display_name' => $user->name,
            ]);
        }

        $staffRequestCreated = false;
        $businessName = null;

        // Si se proporciona código de invitación, crear Staff request automáticamente
        if ($request->has('business_invitation_code') && $request->business_invitation_code) {
            $business = Business::where('invitation_code', $request->business_invitation_code)->first();
            
            if ($business) {
                // Crear solicitud de staff automáticamente
                Staff::create([
                    'business_id' => $business->id,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => 'pending',
                    'position' => 'Mozo', // Posición por defecto
                ]);
                
                $staffRequestCreated = true;
                $businessName = $business->name;
                
                // Asignar business activo al usuario
                $user->active_business_id = $business->id;
                $user->save();
                
                // Agregar usuario al business (relación many-to-many)
                $user->businesses()->attach($business->id);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'staff_request_created' => $staffRequestCreated,
            'business_name' => $businessName,
            'message' => $staffRequestCreated 
                ? "Registro exitoso. Tu solicitud para trabajar en {$businessName} ha sido enviada al administrador."
                : 'Registro exitoso. Puedes unirte a un negocio usando un código de invitación.'
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
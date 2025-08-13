<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TableProfileController extends Controller
{
    /**
     * Listar perfiles del mozo
     */
    public function index(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        $profiles = Profile::forCurrentUser()
            ->where('business_id', $waiter->active_business_id)
            ->with(['tables:id,number,name'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'profiles' => $profiles->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'description' => $profile->description,
                    'is_active' => $profile->is_active,
                    'activated_at' => $profile->activated_at,
                    'tables_count' => $profile->tables->count(),
                    'tables' => $profile->tables->map(function ($table) {
                        return [
                            'id' => $table->id,
                            'number' => $table->number,
                            'name' => $table->name
                        ];
                    })
                ];
            })
        ]);
    }

    /**
     * Crear nuevo perfil
     */
    public function store(Request $request): JsonResponse
    {
        $waiter = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'table_ids' => 'required|array|min:1|max:20',
            'table_ids.*' => 'integer|exists:tables,id'
        ]);

        // Verificar que no existe perfil con mismo nombre para este mozo
        $existingProfile = Profile::forCurrentUser()
            ->where('name', $request->name)
            ->where('business_id', $waiter->active_business_id)
            ->first();

        if ($existingProfile) {
            throw ValidationException::withMessages([
                'name' => ['Ya tienes un perfil con ese nombre']
            ]);
        }

        // Verificar que todas las mesas pertenecen al business del mozo
        $tables = Table::whereIn('id', $request->table_ids)
            ->where('business_id', $waiter->active_business_id)
            ->get();

        if ($tables->count() !== count($request->table_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Algunas mesas no pertenecen a tu negocio o no existen'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Crear perfil
            $profile = Profile::create([
                'user_id' => $waiter->id,
                'name' => $request->name,
                'description' => $request->description,
                'business_id' => $waiter->active_business_id,
                'is_active' => false
            ]);

            // Asociar mesas
            $profile->tables()->attach($request->table_ids);

            DB::commit();

            Log::info("Perfil de mesas creado", [
                'profile_id' => $profile->id,
                'profile_name' => $profile->name,
                'waiter_id' => $waiter->id,
                'tables_count' => count($request->table_ids)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Perfil creado exitosamente',
                'profile' => [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'description' => $profile->description,
                    'tables_count' => count($request->table_ids)
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creando perfil de mesas", [
                'error' => $e->getMessage(),
                'waiter_id' => $waiter->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Mostrar perfil específico
     */
    public function show(Profile $profile): JsonResponse
    {
        $waiter = Auth::user();

        // Verificar que el perfil pertenece al mozo
        if ($profile->user_id !== $waiter->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a este perfil'
            ], 403);
        }

        $profile->load(['tables' => function ($query) {
            $query->with('activeWaiter:id,name');
        }]);

        return response()->json([
            'success' => true,
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'description' => $profile->description,
                'is_active' => $profile->is_active,
                'activated_at' => $profile->activated_at,
                'tables' => $profile->tables->map(function ($table) {
                    return [
                        'id' => $table->id,
                        'number' => $table->number,
                        'name' => $table->name,
                        'status' => $table->active_waiter_id ? 'occupied' : 'available',
                        'assigned_waiter' => $table->activeWaiter ? [
                            'id' => $table->activeWaiter->id,
                            'name' => $table->activeWaiter->name
                        ] : null,
                        'is_silenced' => $table->isSilenced()
                    ];
                })
            ]
        ]);
    }

    /**
     * Actualizar perfil
     */
    public function update(Request $request, Profile $profile): JsonResponse
    {
        $waiter = Auth::user();

        // Verificar que el perfil pertenece al mozo
        if ($profile->user_id !== $waiter->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a este perfil'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'table_ids' => 'required|array|min:1|max:20',
            'table_ids.*' => 'integer|exists:tables,id'
        ]);

        // Verificar nombre único (excluyendo el perfil actual)
        $existingProfile = Profile::forCurrentUser()
            ->where('name', $request->name)
            ->where('business_id', $waiter->active_business_id)
            ->where('id', '!=', $profile->id)
            ->first();

        if ($existingProfile) {
            throw ValidationException::withMessages([
                'name' => ['Ya tienes un perfil con ese nombre']
            ]);
        }

        // Verificar mesas del business
        $tables = Table::whereIn('id', $request->table_ids)
            ->where('business_id', $waiter->active_business_id)
            ->get();

        if ($tables->count() !== count($request->table_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Algunas mesas no pertenecen a tu negocio o no existen'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Actualizar perfil
            $profile->update([
                'name' => $request->name,
                'description' => $request->description
            ]);

            // Reemplazar mesas
            $profile->tables()->sync($request->table_ids);

            DB::commit();

            Log::info("Perfil de mesas actualizado", [
                'profile_id' => $profile->id,
                'profile_name' => $profile->name,
                'waiter_id' => $waiter->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado exitosamente',
                'profile' => [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'description' => $profile->description,
                    'tables_count' => count($request->table_ids)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error actualizando perfil de mesas", [
                'profile_id' => $profile->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Eliminar perfil
     */
    public function destroy(Profile $profile): JsonResponse
    {
        $waiter = Auth::user();

        // Verificar que el perfil pertenece al mozo
        if ($profile->user_id !== $waiter->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a este perfil'
            ], 403);
        }

        $profileName = $profile->name;
        $profileId = $profile->id;

        $profile->delete();

        Log::info("Perfil de mesas eliminado", [
            'profile_id' => $profileId,
            'profile_name' => $profileName,
            'waiter_id' => $waiter->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Perfil eliminado exitosamente'
        ]);
    }

    /**
     * Activar perfil completo
     */
    public function activate(Profile $profile): JsonResponse
    {
        $waiter = Auth::user();

        // Verificar que el perfil pertenece al mozo
        if ($profile->user_id !== $waiter->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a este perfil'
            ], 403);
        }

        // Desactivar otros perfiles activos del mozo
        Profile::forCurrentUser()
            ->where('business_id', $waiter->active_business_id)
            ->where('id', '!=', $profile->id)
            ->update(['is_active' => false, 'activated_at' => null]);

        // Activar perfil usando el método del modelo
        $result = $profile->activate();

        // Actualizar timestamp de activación
        $profile->update(['activated_at' => now()]);

        Log::info("Perfil activado", [
            'profile_id' => $profile->id,
            'profile_name' => $profile->name,
            'waiter_id' => $waiter->id,
            'activated_tables' => $result['activated_tables'],
            'conflicting_tables' => $result['conflicting_tables']->count()
        ]);

        return response()->json([
            'success' => true,
            'message' => $this->buildActivationMessage($result),
            'result' => [
                'profile_name' => $profile->name,
                'total_tables' => $result['total_tables'],
                'activated_tables' => $result['activated_tables'],
                'own_tables' => $result['own_tables'],
                'conflicting_tables' => $result['conflicting_tables']->map(function ($table) {
                    return [
                        'id' => $table->id,
                        'number' => $table->number,
                        'name' => $table->name,
                        'assigned_waiter' => [
                            'id' => $table->activeWaiter->id,
                            'name' => $table->activeWaiter->name
                        ]
                    ];
                })->values()
            ]
        ]);
    }

    /**
     * Desactivar perfil
     */
    public function deactivate(Profile $profile): JsonResponse
    {
        $waiter = Auth::user();

        // Verificar que el perfil pertenece al mozo
        if ($profile->user_id !== $waiter->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a este perfil'
            ], 403);
        }

        $profile->update([
            'is_active' => false,
            'activated_at' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Perfil desactivado exitosamente'
        ]);
    }

    /**
     * Construir mensaje de activación
     */
    private function buildActivationMessage(array $result): string
    {
        $messages = [];

        if ($result['activated_tables'] > 0) {
            $messages[] = "{$result['activated_tables']} mesas activadas";
        }

        if ($result['own_tables'] > 0) {
            $messages[] = "{$result['own_tables']} ya eran tuyas";
        }

        if ($result['conflicting_tables']->count() > 0) {
            $conflicts = $result['conflicting_tables']->map(function ($table) {
                return "Mesa {$table->number} ({$table->activeWaiter->name})";
            })->join(', ');
            $messages[] = "Conflictos: {$conflicts}";
        }

        return 'Perfil activado. ' . implode('. ', $messages);
    }

    /**
     * Obtener notificaciones de auto-completar del mozo
     */
    public function getAutoCompleteNotifications(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        // Simular obtener notificaciones de auto-completar desde logs o base de datos
        // En un caso real, tendrías una tabla de notificaciones
        $notifications = [
            [
                'id' => 1,
                'type' => 'profile_auto_complete',
                'message' => 'Mesa 5 auto-activada (perfil: Patio Trasero)',
                'table_id' => 5,
                'table_number' => 5,
                'profile_name' => 'Patio Trasero',
                'created_at' => now()->subMinutes(5),
                'read' => false
            ],
            [
                'id' => 2,
                'type' => 'profile_auto_complete', 
                'message' => 'Mesa 12 auto-activada (perfil: Salón Principal)',
                'table_id' => 12,
                'table_number' => 12,
                'profile_name' => 'Salón Principal',
                'created_at' => now()->subMinutes(15),
                'read' => true
            ]
        ];

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => collect($notifications)->where('read', false)->count()
        ]);
    }

    /**
     * Marcar notificación como leída
     */
    public function markNotificationAsRead(Request $request, $notificationId): JsonResponse
    {
        // En un caso real, actualizarías la base de datos
        Log::info("Notificación auto-complete marcada como leída", [
            'notification_id' => $notificationId,
            'waiter_id' => Auth::id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída'
        ]);
    }
}
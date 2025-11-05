<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\JsonResponses;
use App\Models\ArchivedStaff;
use App\Models\Staff;
use App\Models\Review;
use App\Models\Table;
use App\Models\User;
use App\Models\WaiterCall;
use App\Notifications\GenericDataNotification;
use App\Services\FirebaseService;
use App\Services\StaffNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminStaffController extends Controller
{
    use JsonResponses;

    /**
     * 🔥 PUNTO 10: Eliminar staff por user_id
     * 
     * El frontend envía user_id en la ruta, no staff.id
     * Endpoint: DELETE /api/admin/staff/{userId}
     */
    public function removeStaff(Request $request, $userId)
    {
        $user = Auth::user();
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $businessId = $request->business_id;

        // 🔥 CAMBIO: Buscar por user_id en vez de id
        $staff = Staff::where('user_id', $userId)
            ->where('business_id', $businessId)
            ->first();

        if (!$staff) {
            return response()->json([
                'message' => 'El miembro de personal no existe o no pertenece a tu negocio activo',
                'user_id' => (int)$userId, // 🔥 CAMBIO: Devolver user_id
                'active_business_id' => (int)$businessId,
            ], 404);
        }

        // Marcar como desvinculado antes de limpiar referencias para que las sincronizaciones reflejen el evento
        if ($staff->status !== 'unlinked') {
            $staff->status = 'unlinked';
            $staff->save();
            $staff->refresh();
        }
        $staffSnapshot = clone $staff;

        // Ejecutar desvinculación completa (desasignar mesas, cancelar llamadas, revocar pivot, notificar)
        try {
            $this->performWaiterUnlink($staff, (int)$businessId);
        } catch (\Throwable $e) {
            Log::warning('Fallo en efectos colaterales al desvincular staff', [
                'staff_id' => $staff->id,
                'user_id' => $staff->user_id, // 🔥 Log user_id también
                'business_id' => (int)$businessId,
                'error' => $e->getMessage(),
            ]);
        }

        $staff->delete();

        // Sincronizar estado final en Firebase para despejar listeners del front
        try {
            app(StaffNotificationService::class)->markStaffUnlinked($staffSnapshot);
        } catch (\Throwable $e) {
            Log::warning('No se pudo notificar desvinculación en Firebase', [
                'staff_id' => $staffSnapshot->id,
                'user_id' => $staffSnapshot->user_id, // 🔥 Log user_id también
                'business_id' => (int)$businessId,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success([
            'user_id' => (int)$userId, // 🔥 CAMBIO: Devolver user_id en vez de staff_id
            'staff_id' => (int)$staff->id, // Mantener para compatibilidad
            'status' => 'unlinked',
        ], 'Personal eliminado exitosamente');
    }
    
    /**
     * Manejar solicitud de staff (confirm/reject/archive/unarchive)
     * 
     * Endpoint: POST /api/admin/staff/request/{requestId}
     */
    public function handleStaffRequest(Request $request, $requestId)
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required', Rule::in(['confirm', 'reject', 'archive', 'archived', 'unarchive'])],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }
        
        $user = Auth::user();
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $activeBusinessId = $request->business_id;

        // Validación temprana de ID
        if (!is_numeric($requestId)) {
            return $this->error('ID de solicitud inválido', 400, ['request_id' => $requestId]);
        }

        // Si la acción es desarchivar, no requerimos un registro en staff
        if ($request->action === 'unarchive') {
            $archived = ArchivedStaff::where('id', (int)$requestId)
                ->where('business_id', $activeBusinessId)
                ->first();

            if (!$archived) {
                return $this->notFound('Registro archivado no encontrado para desarchivar');
            }

            // Si existe staff con mismo email en el negocio, lo reactivamos
            $existing = null;
            if (!empty($archived->email)) {
                $existing = Staff::where('business_id', $activeBusinessId)
                    ->where('email', $archived->email)
                    ->first();
            }

            // Recuperar datos originales si están presentes
            $original = $archived->original_data;
            if (!is_array($original)) {
                $decoded = json_decode($archived->original_data ?? '', true);
                $original = is_array($decoded) ? $decoded : [];
            }

            // Intentar vincular usuario por email si no viene user_id
            $linkedUserId = null;
            if (empty($archived->user_id) && !empty($archived->email)) {
                $linked = User::where('email', $archived->email)->first();
                if ($linked) { $linkedUserId = $linked->id; }
            }

            $payload = [
                'business_id' => $archived->business_id,
                'user_id' => $archived->user_id ?? ($original['user_id'] ?? $linkedUserId),
                'name' => $archived->name ?? ($original['name'] ?? null),
                'position' => $archived->position ?? ($original['position'] ?? null),
                'email' => $archived->email ?? ($original['email'] ?? null),
                'phone' => $archived->phone ?? ($original['phone'] ?? null),
                'hire_date' => $archived->hire_date ?? ($original['hire_date'] ?? null),
                'status' => $original['status'] ?? 'pending',
                'notes' => $archived->notes ?? ($original['notes'] ?? null),
            ];

            foreach (['birth_date','height','weight','gender','experience_years','seniority_years','education','employment_type','current_schedule','avatar_path'] as $field) {
                if (array_key_exists($field, $original)) {
                    $payload[$field] = $original[$field];
                }
            }

            if ($existing) {
                $existing->fill($payload);
                if ($existing->status === 'rejected') {
                    $existing->status = 'pending';
                }
                $existing->save();
                $restored = $existing;
            } else {
                $restored = Staff::create($payload);
            }

            $archived->delete();

            return $this->success(['staff' => $restored], 'Solicitud desarchivada exitosamente');
        }

        // Para el resto de acciones, se requiere que exista el registro en staff
        $staff = Staff::where('id', (int)$requestId)
            ->where('business_id', $activeBusinessId)
            ->first();

        if (!$staff) {
            return $this->notFound('Solicitud no encontrada para el negocio activo');
        }

        switch ($request->action) {
            case 'confirm':
                $staff->status = 'confirmed';
                $staff->save();

                if ($request->has('create_user') && $request->create_user) {
                    User::create([
                        'name' => $staff->name,
                        'email' => $staff->email,
                        'password' => Hash::make('temporal123'),
                        'role' => 'waiter',
                        'business_id' => $request->business_id,
                    ]);
                }

                // Actualizar Firebase y notificaciones
                try {
                    app(StaffNotificationService::class)->writeStaffRequest($staff, 'confirmed');
                } catch (\Throwable $e) {
                    Log::warning('Failed to update Firebase after confirm', ['error' => $e->getMessage()]);
                }

                // Actualizar notificaciones existentes con el nuevo status
                $this->updateStaffNotificationsStatus($staff->id, 'confirmed');

                return $this->success(['staff' => $staff], 'Solicitud de personal confirmada');

            case 'reject':
                $staff->status = 'rejected';
                $staff->save();

                // Actualizar Firebase y notificaciones
                try {
                    app(StaffNotificationService::class)->writeStaffRequest($staff, 'rejected');
                } catch (\Throwable $e) {
                    Log::warning('Failed to update Firebase after reject', ['error' => $e->getMessage()]);
                }

                // Actualizar notificaciones existentes con el nuevo status
                $this->updateStaffNotificationsStatus($staff->id, 'rejected');

                return $this->success(['staff' => $staff], 'Solicitud de personal rechazada');
                
            case 'archive':
                $originalStatus = $staff->status;
                $originalData = $staff->toArray();
                // Efectos de desvinculación antes de archivar
                try { $this->performWaiterUnlink($staff, (int)$activeBusinessId); } catch (\Throwable $e) { /* noop */ }

                if ($staff->status !== 'unlinked') {
                    $staff->status = 'unlinked';
                    $staff->save();
                    $staff->refresh();
                }
                $staffSnapshot = clone $staff;

                ArchivedStaff::create([
                    'business_id' => $staff->business_id,
                    'staff_id' => $staff->id,
                    'user_id' => $staff->user_id ?? null,
                    'name' => $staff->name,
                    'position' => $staff->position,
                    'email' => $staff->email,
                    'phone' => $staff->phone,
                    'hire_date' => $staff->hire_date,
                    'termination_date' => now(),
                    'termination_reason' => $request->termination_reason ?? null,
                    'last_salary' => $staff->salary ?? null,
                    'status' => $originalStatus,
                    'notes' => $staff->notes,
                    'original_data' => $originalData,
                    'archived_by' => $user->id,
                    'archive_reason' => $request->archive_reason ?? 'Archived from admin panel',
                    'archived_at' => now(),
                ]);

                $staff->delete();

                try {
                    app(StaffNotificationService::class)->markStaffUnlinked($staffSnapshot);
                } catch (\Throwable $e) { /* noop */ }

                return $this->success([], 'Solicitud de personal archivada');

            case 'archived':
                $originalStatus = $staff->status;
                $originalData = $staff->toArray();
                // Efectos de desvinculación antes de archivar (bulk)
                try { $this->performWaiterUnlink($staff, (int)$activeBusinessId); } catch (\Throwable $e) { /* noop */ }

                if ($staff->status !== 'unlinked') {
                    $staff->status = 'unlinked';
                    $staff->save();
                    $staff->refresh();
                }
                $staffSnapshot = clone $staff;

                ArchivedStaff::create([
                    'business_id' => $staff->business_id,
                    'staff_id' => $staff->id,
                    'user_id' => $staff->user_id ?? null,
                    'name' => $staff->name,
                    'position' => $staff->position,
                    'email' => $staff->email,
                    'phone' => $staff->phone,
                    'hire_date' => $staff->hire_date,
                    'termination_date' => now(),
                    'termination_reason' => $request->termination_reason ?? null,
                    'last_salary' => $staff->salary ?? null,
                    'status' => $originalStatus,
                    'notes' => $staff->notes,
                    'original_data' => $originalData,
                    'archived_by' => $user->id,
                    'archive_reason' => $request->archive_reason ?? 'Bulk/archive action',
                    'archived_at' => now(),
                ]);

                $staff->delete();

                try {
                    app(StaffNotificationService::class)->markStaffUnlinked($staffSnapshot);
                } catch (\Throwable $e) { /* noop */ }

                return $this->success([], 'Solicitud de personal archivada');
        }
    }
    
    /**
     * Obtener solicitudes pendientes de staff
     * 
     * Endpoint: GET /api/admin/staff/requests
     */
    public function fetchStaffRequests(Request $request)
    {
        $user = Auth::user();
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $activeBusinessId = $request->business_id;

        if (!Schema::hasTable('staff')) {
            return response()->json([
                'requests' => [],
                'count' => 0,
                'warning' => 'Tabla staff no encontrada. Aplique las migraciones para habilitar esta funcionalidad.'
            ]);
        }

        $pendingRequests = Staff::where('business_id', $activeBusinessId)
            ->where('status', 'pending')
            ->with([
                'user:id,name,email,google_id,google_avatar,created_at,updated_at',
                'user.waiterProfile',
                'user.adminProfile',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // Resolver usuarios faltantes por email en lote
        $missingUserEmails = $pendingRequests->filter(fn($r) => !$r->user && !empty($r->email))
            ->pluck('email')->unique()->values();
        $usersByEmail = collect();
        if ($missingUserEmails->isNotEmpty()) {
            $usersByEmail = User::whereIn('email', $missingUserEmails)
                ->with(['waiterProfile', 'adminProfile'])
                ->get()->keyBy('email');
        }

        return response()->json([
            'requests' => $pendingRequests->map(function ($req) use ($usersByEmail) {
                $user = $req->user ?: ($req->email ? $usersByEmail->get($req->email) : null);

                $userData = $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->google_id,
                    'google_avatar' => $user->google_avatar,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ] : [
                    // Fallback mínimo desde la solicitud de staff
                    'id' => null,
                    'name' => $req->name,
                    'email' => $req->email,
                    'google_id' => null,
                    'google_avatar' => null,
                    'created_at' => null,
                    'updated_at' => null,
                ];

                $profile = $user ? ($user->waiterProfile ?: $user->adminProfile) : null;
                $profileData = $profile ? $profile->toArray() : null;

                if ($profileData) {
                    $avatarUrl = null;
                    if (!empty($profile->avatar_url)) {
                        $avatarUrl = $profile->avatar_url;
                    } elseif (!empty($profileData['avatar'])) {
                        try {
                            $avatarUrl = Storage::disk('public')->url($profileData['avatar']);
                        } catch (\Throwable $e) {
                            $avatarUrl = null;
                        }
                    }
                    if ($avatarUrl) {
                        $avatarUrl = preg_replace('/^http:/i', 'https:', $avatarUrl);
                        $profileData['avatar_url'] = $avatarUrl;
                        $profileData['avatar'] = $avatarUrl;
                    }
                }

                return [
                    'id' => (int) $req->id, // ID del registro staff
                    'status' => $req->status,
                    'position' => $req->position,
                    'created_at' => $req->created_at,
                    'user' => $userData,
                    'user_profile' => $profileData,
                ];
            }),
            'count' => $pendingRequests->count(),
        ]);
    }
    
    /**
     * Obtener solicitudes archivadas de staff
     * 
     * Endpoint: GET /api/admin/staff/archived-requests
     */
    public function fetchArchivedRequests(Request $request)
    {
        $user = Auth::user();
        
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $activeBusinessId = $request->business_id;

        if (!Schema::hasTable('archived_staff')) {
            return response()->json([
                'requests' => [],
                'count' => 0,
                'warning' => 'Tabla archived_staff no encontrada. Aplique las migraciones para habilitar esta funcionalidad.'
            ]);
        }

        $archivedRequests = ArchivedStaff::where('business_id', $activeBusinessId)
            ->orderBy('archived_at', 'desc')
            ->get();

        // Pre-cargar usuarios por user_id y, si falta, por email
        $userIds = $archivedRequests->pluck('user_id')->filter()->unique()->values();
        $usersById = collect();
        if ($userIds->isNotEmpty()) {
            $usersById = User::whereIn('id', $userIds)
                ->with(['waiterProfile', 'adminProfile'])
                ->get()
                ->keyBy('id');
        }

        $emails = $archivedRequests->whereNull('user_id')->pluck('email')->filter()->unique()->values();
        $usersByEmail = collect();
        if ($emails->isNotEmpty()) {
            $usersByEmail = User::whereIn('email', $emails)
                ->with(['waiterProfile', 'adminProfile'])
                ->get()
                ->keyBy('email');
        }

        $out = $archivedRequests->map(function ($row) use ($usersById, $usersByEmail) {
            $user = null;
            if (!empty($row->user_id) && $usersById->has($row->user_id)) {
                $user = $usersById->get($row->user_id);
            } elseif (!empty($row->email) && $usersByEmail->has($row->email)) {
                $user = $usersByEmail->get($row->email);
            }

            $userData = $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'google_id' => $user->google_id,
                'google_avatar' => $user->google_avatar,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ] : (
                // Fallback mínimo con datos archivados si no hay User
                (!empty($row->email) || !empty($row->name)) ? [
                    'id' => null,
                    'name' => $row->name,
                    'email' => $row->email,
                    'google_id' => null,
                    'google_avatar' => null,
                    'created_at' => null,
                    'updated_at' => null,
                ] : null
            );

            $profile = $user ? ($user->waiterProfile ?: $user->adminProfile) : null;
            $profileData = $profile ? $profile->toArray() : null;

            if ($profileData) {
                $avatarUrl = null;
                if (!empty($profile->avatar_url)) {
                    $avatarUrl = $profile->avatar_url;
                } elseif (!empty($profileData['avatar'])) {
                    try {
                        $avatarUrl = Storage::disk('public')->url($profileData['avatar']);
                    } catch (\Throwable $e) {
                        $avatarUrl = null;
                    }
                }
                if ($avatarUrl) {
                    $avatarUrl = preg_replace('/^http:/i', 'https:', $avatarUrl);
                    $profileData['avatar_url'] = $avatarUrl;
                    $profileData['avatar'] = $avatarUrl;
                }
            }

            return [
                'id' => (int) $row->id,
                'user' => $userData,
                'user_profile' => $profileData,
            ];
        });

        return response()->json([
            'requests' => $out,
            'count' => $archivedRequests->count(),
        ]);
    }

    /**
     * Obtener lista de staff del negocio
     * 
     * Endpoint: GET /api/admin/staff
     */
    public function getStaff(Request $request)
    {
        $user = $request->user();
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $activeBusinessId = $request->business_id;
        $query = Staff::where('business_id', $activeBusinessId)
            ->when(!$request->filled('status'), function ($q) {
                // Por defecto, solo personal confirmado (no incluye requests pending/invited)
                $q->where('status', 'confirmed');
            });

        // Agregar búsqueda por nombre o email
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filtrar por estado
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $staff = $query->with(['user.waiterProfile'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'staff' => $staff->map(function($staffMember) {
                $user = $staffMember->user;
                $userData = $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->google_id,
                    'google_avatar' => $user->google_avatar,
                ] : null;

                $profile = $user ? $user->waiterProfile : null; // Para staff list, usamos perfil de mozo
                $profileData = null;
                if ($profile) {
                    $profileData = $profile->toArray();
                    if (isset($profile->birth_date) && $profile->birth_date) {
                        $profileData['birth_date'] = $profile->birth_date->format('d-m-Y');
                    }
                    $avatarUrl = $profile->avatar_url;
                    if ($avatarUrl) {
                        $avatarUrl = preg_replace('/^http:/i', 'https:', $avatarUrl);
                    }
                    $profileData['avatar'] = $avatarUrl;
                    $profileData['avatar_url'] = $avatarUrl;
                    unset($profileData['display_name']); // Nombre canónico está en user.name
                }

                // Anidar perfil dentro de user para mantener consistencia con otros endpoints
                if ($userData !== null) {
                    $userData['user_profile'] = $profileData;
                }

                return [
                    'id' => $staffMember->id, // ID del registro staff
                    'user_id' => $staffMember->user_id, // 🔥 PUNTO 10: ID del usuario (usado por frontend)
                    'status' => $staffMember->status,
                    'position' => $staffMember->position,
                    'hire_date' => $staffMember->hire_date,
                    'user' => $userData,
                ];
            }),
            'search' => $request->search,
            'total' => $staff->count(),
        ]);
    }

    /**
     * 🔥 PUNTO 10: Obtener staff member por user_id
     * 
     * El frontend envía user_id en la ruta, no staff.id
     * Endpoint: GET /api/admin/staff/{userId}
     */
    public function getStaffMember(Request $request, $id)
    {
        $user = $request->user();
        // Permitir fijar explícitamente el negocio vía query param para evitar 404 por scope
        $requestedBusinessId = $request->query('business_id');
        $activeBusinessId = null;

        if ($requestedBusinessId && is_numeric($requestedBusinessId)) {
            $requestedBusinessId = (int) $requestedBusinessId;
            // Verificar que el admin tenga acceso a ese negocio (si existe la relación)
            $hasAccess = true;
            try {
                if (method_exists($user, 'businessesAsAdmin')) {
                    $hasAccess = $user->businessesAsAdmin()->where('business_id', $requestedBusinessId)->exists();
                }
            } catch (\Throwable $e) { /* noop */ }

            if (!$hasAccess) {
                return response()->json([
                    'message' => 'No tienes acceso a este negocio',
                    'requested_business_id' => $requestedBusinessId,
                ], 403);
            }
            $activeBusinessId = $requestedBusinessId;
        } else {
            // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
            $activeBusinessId = $request->business_id;
        }

        if (!is_numeric($id)) {
            return response()->json([
                'message' => 'ID de staff inválido',
                'user_id' => $id, // 🔥 CAMBIO: Referencia como user_id
            ], 400);
        }

        // Permitir consultar no confirmados sólo si include_unconfirmed=true
        $includeUnconfirmed = filter_var($request->query('include_unconfirmed', false), FILTER_VALIDATE_BOOLEAN);

        // 🔥 CAMBIO: Buscar por user_id en vez de id
        $query = Staff::with('reviews')
            ->where('user_id', (int)$id)
            ->where('business_id', $activeBusinessId);

        if (!$includeUnconfirmed) {
            $query->where('status', 'confirmed');
        }

        $staff = $query->first();

        if (!$staff) {
            return response()->json([
                'message' => 'Staff no encontrado o no confirmado para el negocio activo',
                'user_id' => (int)$id, // 🔥 CAMBIO: Referencia como user_id
                'active_business_id' => (int)$activeBusinessId,
                'include_unconfirmed' => $includeUnconfirmed,
            ], 404);
        }

        return response()->json([
            'staff' => $staff,
            'effective_business_id' => (int)$activeBusinessId,
        ]);
    }

    /**
     * 🔥 PUNTO 10: Actualizar staff member por user_id
     * 
     * El frontend envía user_id en la ruta, no staff.id
     * Endpoint: PUT/PATCH /api/admin/staff/{userId}
     */
    public function updateStaffMember(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'position' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'sometimes|string|max:20',
            'hire_date' => 'sometimes|date',
            'salary' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string|max:50',
            'notes' => 'sometimes|string',
            'birth_date' => 'sometimes|date',
            'height' => 'sometimes|numeric|min:0',
            'weight' => 'sometimes|numeric|min:0',
            'gender' => 'sometimes|string|max:10',
            'experience_years' => 'sometimes|integer|min:0',
            'seniority_years' => 'sometimes|integer|min:0',
            'education' => 'sometimes|string|max:255',
            'employment_type' => 'sometimes|string|max:50',
            'current_schedule' => 'sometimes|string|max:255',
            'avatar' => 'sometimes',
        ]);

        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $activeBusinessId = $request->business_id;
        
        // 🔥 CAMBIO: Buscar por user_id en vez de id
        $staff = Staff::where('user_id', $id)
            ->where('business_id', $activeBusinessId)
            ->firstOrFail();

        $input = $request->only($staff->getFillable());
        foreach ($input as $key => $value) {
            if ($value === '') {
                $input[$key] = null;
            }
        }

        $staff->fill($input);
        if ($request->has('avatar')) {
            if ($request->file('avatar')) {
                $path = $request->file('avatar')->store('avatars/' . $activeBusinessId, 'public');
            } elseif (Str::startsWith($request->avatar, 'data:image')) {
                $path = $this->storeBase64Image($request->avatar, $activeBusinessId);
            } else {
                return response()->json(['message' => 'Formato de avatar no soportado'], 422);
            }
            $staff->avatar_path = $path;
        }
        $staff->save();

        return response()->json([
            'message' => 'Personal actualizado exitosamente',
            'staff' => $staff,
        ]);
    }

    /**
     * Invitar nuevo staff member
     * 
     * Endpoint: POST /api/admin/staff/invite
     */
    public function inviteStaff(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'required|email|max:255|unique:staff,email',
            'role' => 'sometimes|string|max:50',
            'position' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'birth_date' => 'sometimes|date',
            'height' => 'sometimes|numeric|min:0',
            'weight' => 'sometimes|numeric|min:0',
            'gender' => 'sometimes|string|max:10',
            'experience_years' => 'sometimes|integer|min:0',
            'seniority_years' => 'sometimes|integer|min:0',
            'education' => 'sometimes|string|max:255',
            'employment_type' => 'sometimes|string|max:50',
            'current_schedule' => 'sometimes|string|max:255',
            'avatar' => 'sometimes',
        ]);

        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $activeBusinessId = $request->business_id;

        // Derivar nombre en caso de no ser provisto (obligatorio en DB)
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            $email = (string) $request->input('email', '');
            if ($email !== '' && str_contains($email, '@')) {
                $local = explode('@', $email)[0];
                $local = preg_replace('/[._-]+/', ' ', $local);
                $name = ucwords(trim($local));
            }
        }
        if ($name === '') {
            $name = 'Invitado';
        }

        // Mapear role -> position si no llega position explícito
        $position = $request->input('position');
        if (!$position) {
            $role = strtolower((string) $request->input('role', ''));
            if ($role !== '') {
                $roleMap = [
                    'waiter' => 'Mozo',
                    'camarero' => 'Mozo',
                    'mozo' => 'Mozo',
                    'cook' => 'Cocinero',
                    'cocinero' => 'Cocinero',
                    'bartender' => 'Barman',
                    'barman' => 'Barman',
                    'host' => 'Host',
                    'manager' => 'Manager',
                ];
                $position = $roleMap[$role] ?? ucfirst($role);
            }
        }

        $avatarPath = null;
        if ($request->has('avatar')) {
            if ($request->file('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars/' . $activeBusinessId, 'public');
            } elseif (Str::startsWith($request->avatar, 'data:image')) {
                $avatarPath = $this->storeBase64Image($request->avatar, $activeBusinessId);
            } else {
                return response()->json(['message' => 'Formato de avatar no soportado'], 422);
            }
        }

        $staff = Staff::create([
            'business_id' => $activeBusinessId,
            'name' => $name,
            'position' => $position,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => 'invited',
            'hire_date' => now(),
            'birth_date' => $request->birth_date,
            'height' => $request->height,
            'weight' => $request->weight,
            'gender' => $request->gender,
            'experience_years' => $request->experience_years,
            'seniority_years' => $request->seniority_years,
            'education' => $request->education,
            'employment_type' => $request->employment_type,
            'current_schedule' => $request->current_schedule,
            'avatar_path' => $avatarPath,
            // Generar token y timestamp de invitación (si luego se usa para email/web)
            'invitation_token' => Str::random(40),
            'invitation_sent_at' => now(),
        ]);

        return response()->json([
            'message' => 'Invitación enviada exitosamente',
            'staff' => $staff,
        ], 201);
    }

    /**
     * 🔥 PUNTO 10: Agregar review por user_id
     * 
     * Endpoint: POST /api/admin/staff/{userId}/reviews
     */
    public function addReview(Request $request, $userId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'sometimes|string',
        ]);

        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        // 🔥 CAMBIO: Buscar por user_id
        $staff = Staff::where('user_id', $userId)
            ->where('business_id', $request->business_id)
            ->firstOrFail();

        $review = Review::create([
            'business_id' => $request->business_id,
            'staff_id' => $staff->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Reseña añadida exitosamente',
            'review' => $review,
        ], 201);
    }

    /**
     * 🔥 PUNTO 10: Eliminar review por user_id
     * 
     * Endpoint: DELETE /api/admin/staff/{userId}/reviews/{id}
     */
    public function deleteReview(Request $request, $userId, $id)
    {
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        // 🔥 CAMBIO: Buscar por user_id
        $staff = Staff::where('user_id', $userId)
            ->where('business_id', $request->business_id)
            ->firstOrFail();

        $review = Review::where('id', $id)
            ->where('staff_id', $staff->id)
            ->where('business_id', $request->business_id)
            ->firstOrFail();

        $review->delete();

        return response()->json([
            'message' => 'Reseña eliminada exitosamente',
        ]);
    }

    /**
     * Procesar todas las solicitudes pendientes de una vez
     * 
     * Endpoint: POST /api/admin/staff/bulk-process
     */
    public function bulkProcessRequests(Request $request)
    {
        $request->validate([
            'action' => ['required', Rule::in(['confirm_all', 'archive_all'])],
        ]);
        
        $user = Auth::user();
        
        $pendingRequests = Staff::where('business_id', $request->business_id)
            ->where('status', 'pending')
            ->get();

        if ($pendingRequests->isEmpty()) {
            return response()->json([
                'message' => 'No hay solicitudes pendientes para procesar'
            ], 404);
        }

        $processedCount = 0;
        
        foreach ($pendingRequests as $staff) {
            if ($request->action === 'confirm_all') {
                $staff->status = 'confirmed';
                $staff->hire_date = now();
                $staff->save();
            } else { // archive_all
                // Mover a tabla archived_staff
                ArchivedStaff::create([
                    'business_id' => $staff->business_id,
                    'user_id' => $staff->user_id,
                    'name' => $staff->name,
                    'email' => $staff->email,
                    'position' => $staff->position,
                    'original_data' => $staff->toArray(),
                    'archived_at' => now(),
                    'archived_by' => $user->id,
                    'archive_reason' => 'Bulk archive operation'
                ]);
                
                $staff->delete();
            }
            $processedCount++;
        }

        $actionMessage = $request->action === 'confirm_all' 
            ? 'confirmadas' 
            : 'archivadas';

        return response()->json([
            'message' => "{$processedCount} solicitudes {$actionMessage} exitosamente",
            'processed_count' => $processedCount
        ]);
    }

    /**
     * Helper privado: Guardar imagen base64 en storage
     */
    private function storeBase64Image(string $base64Image, int $businessId): string
    {
        if (!preg_match('/data:image\/(\w+);base64,/', $base64Image, $matches)) {
            throw new \Exception('Formato base64 no válido');
        }
        $extension = $matches[1];
        $imageData = substr($base64Image, strpos($base64Image, ',') + 1);
        $imageData = base64_decode($imageData);
        $filename = Str::uuid() . '.' . $extension;
        $path = 'avatars/' . $businessId . '/' . $filename;
        Storage::disk('public')->put($path, $imageData);
        return $path;
    }

    /**
     * Actualizar el status en todas las notificaciones relacionadas con un staff
     */
    private function updateStaffNotificationsStatus(int $staffId, string $newStatus): void
    {
        try {
            $notifications = DB::table('notifications')
                ->whereRaw("JSON_EXTRACT(data, '$.staff_id') = ?", [(string)$staffId])
                ->get();

            foreach ($notifications as $notification) {
                $data = json_decode($notification->data, true);
                $data['status'] = $newStatus;

                DB::table('notifications')
                    ->where('id', $notification->id)
                    ->update(['data' => json_encode($data)]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to update notifications status', [
                'staff_id' => $staffId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Desvinculación completa de un mozo del negocio: desasignar mesas, cancelar llamados,
     * limpiar pivote waiter y notificar al usuario afectado.
     */
    private function performWaiterUnlink(Staff $staff, int $businessId): void
    {
        // Desasignar mesas y cancelar llamadas pendientes
        try {
            $tables = Table::where('business_id', $businessId)
                ->where('active_waiter_id', $staff->user_id)
                ->get();
            foreach ($tables as $table) {
                try { $table->pendingCalls()->update(['status' => 'cancelled']); } catch (\Throwable $e) { /* noop */ }
                try { $table->unassignWaiter(); } catch (\Throwable $e) {
                    $table->active_waiter_id = null; $table->waiter_assigned_at = null; $table->save();
                }
            }

            // Cancelar llamadas pendientes del mozo en ese negocio
            WaiterCall::where('waiter_id', $staff->user_id)
                ->where('status', 'pending')
                ->whereHas('table', function ($q) use ($businessId) {
                    $q->where('business_id', $businessId);
                })
                ->update(['status' => 'cancelled']);
        } catch (\Throwable $e) {
            Log::warning('No se pudieron limpiar mesas/llamados al desvincular mozo', [
                'staff_id' => $staff->id,
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);
        }

        // Revocar relación en pivot business_waiters si existe
        try {
            if (Schema::hasTable('business_waiters') && $staff->user_id) {
                DB::table('business_waiters')
                    ->where('business_id', $businessId)
                    ->where('user_id', $staff->user_id)
                    ->update(['employment_status' => 'inactive', 'updated_at' => now()]);
            }
        } catch (\Throwable $e) { /* noop */ }

        // Notificar al mozo afectado (DB + FCM si hay tokens)
        try {
            if ($staff->user_id && ($waiter = User::find($staff->user_id))) {
                $payload = [
                    'type' => 'waiter_unlinked',
                    'event_type' => 'unlinked',
                    'staff_id' => (string)$staff->id,
                    'business_id' => (string)$businessId,
                    'user_id' => (string)$waiter->id,
                    'title' => 'Has sido desvinculado del negocio',
                    'body' => 'Ya no puedes administrar mesas en este negocio.',
                    'notification_key' => 'waiter_unlinked_' . $businessId,
                    'key' => 'waiter_unlinked_' . $businessId,
                ];
                // DB
                $waiter->notify(new GenericDataNotification($payload));
                // FCM opcional
                try {
                    app(FirebaseService::class)->sendToUser(
                        $waiter->id,
                        $payload['title'],
                        $payload['body'],
                        [
                            'type' => 'unified',
                            'event_type' => 'waiter_unlinked',
                            'notification_key' => $payload['notification_key'],
                            'key' => $payload['key'],
                            'business_id' => (string)$businessId,
                            'staff_id' => (string)$staff->id,
                        ],
                        'normal'
                    );
                } catch (\Throwable $e) { /* noop */ }
            }
        } catch (\Throwable $e) { /* noop */ }
    }
}

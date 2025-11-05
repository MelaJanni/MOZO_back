<?php

namespace App\Http\Controllers;

use App\Models\ArchivedStaff;
use App\Models\Business;
use App\Models\Menu;
use App\Models\QrCode;
use App\Models\Staff;
use App\Models\Table;
use App\Models\User;
use App\Models\Review;
use App\Models\WaiterCall;
use App\Notifications\GenericDataNotification;
use App\Services\FirebaseService;
use App\Services\StaffNotificationService;
use App\Services\UnifiedFirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Concerns\ResolvesActiveBusiness;
use App\Http\Controllers\Concerns\JsonResponses;

class AdminController extends Controller
{
    use ResolvesActiveBusiness, JsonResponses;
    public function getBusinessInfo(Request $request)
    {
        $user = $request->user();

        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $activeBusinessId = $request->business_id;

        // Si aún no hay negocio activo, significa que es un admin nuevo
        if (!$activeBusinessId) {
            return $this->success([
                'requires_business_setup' => true,
                'active_business_id' => null,
                'available_businesses' => [],
                'business' => null,
                'tables_count' => 0,
                'menus_count' => 0,
                'qr_codes_count' => 0,
                'invitation_code' => null,
                'invitation_url' => null,
                'setup_options' => [
                    'create_business' => true,
                    'join_business' => true
                ]
            ], 'No businesses found. Admin needs to create or join a business.');
        }

        // 🔧 FIX: Asegurar que exista el registro en user_active_roles
        // Si el usuario tiene un business activo pero no tiene registro en user_active_roles, crearlo
        try {
            $existingRole = $user->activeRoles()
                ->where('business_id', $activeBusinessId)
                ->where('active_role', 'admin')
                ->first();
            
            if (!$existingRole) {
                // Crear el registro para persistir la sesión
                $user->activeRoles()->updateOrCreate(
                    [
                        'business_id' => $activeBusinessId,
                    ],
                    [
                        'active_role' => 'admin',
                        'switched_at' => now()
                    ]
                );
                
                \Log::info('Auto-created user_active_role for persistent session', [
                    'user_id' => $user->id,
                    'business_id' => $activeBusinessId,
                    'role' => 'admin'
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to create user_active_role', [
                'user_id' => $user->id,
                'business_id' => $activeBusinessId,
                'error' => $e->getMessage()
            ]);
        }

    // Eager load solo si las tablas existen para evitar 500 en entornos sin migraciones completas
    $with = [];
    if (Schema::hasTable('tables')) { $with[] = 'tables'; }
    if (Schema::hasTable('menus')) { $with[] = 'menus'; }
    if (Schema::hasTable('qr_codes')) { $with[] = 'qrCodes'; }

    $business = Business::when(!empty($with), function ($q) use ($with) {
        return $q->with($with);
        })
        ->findOrFail($activeBusinessId);

        // Construir listado de negocios disponibles para este admin
        $availableBusinesses = [];
        try {
            if (method_exists($user, 'businessesAsAdmin')) {
                $adminBusinesses = $user->businessesAsAdmin()
                    ->select('businesses.id', 'businesses.name')
                    ->get();
                $availableBusinesses = $adminBusinesses->map(function ($b) use ($activeBusinessId) {
                    return [
                        'id' => $b->id,
                        'name' => $b->name,
                        'slug' => $b->slug ?? null,
                        'is_active' => (int)$b->id === (int)$activeBusinessId,
                    ];
                });
            } elseif (!empty($request->business_id)) {
                $b = Business::find($request->business_id);
                if ($b) {
                    $availableBusinesses = [[
                        'id' => $b->id,
                        'name' => $b->name,
                        'slug' => $b->slug ?? null,
                        'is_active' => (int)$b->id === (int)$activeBusinessId,
                    ]];
                }
            }
        } catch (\Throwable $e) { /* noop */ }

        return $this->success([
            'business' => $business,
            'active_business_id' => (int)$activeBusinessId,
            'tables_count' => Schema::hasTable('tables') ? $business->tables->count() : 0,
            'menus_count' => Schema::hasTable('menus') ? ($business->relationLoaded('menus') ? $business->menus->count() : \App\Models\Menu::where('business_id', $business->id)->count()) : 0,
            'qr_codes_count' => Schema::hasTable('qr_codes') ? ($business->relationLoaded('qrCodes') ? $business->qrCodes->count() : \App\Models\QrCode::where('business_id', $business->id)->count()) : 0,
            'invitation_code' => $business->invitation_code,
            'invitation_url' => config('app.frontend_url') . '/join-business?code=' . $business->invitation_code,
            'available_businesses' => $availableBusinesses,
        ]);
    }

    public function createBusiness(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:255',
            'description' => 'sometimes|string|max:500',
        ]);

        $user = $request->user();

        // Crear el negocio
        $business = Business::create([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'description' => $request->description,
            'is_active' => true,
        ]);

        // Asignar al usuario como administrador del negocio
        $user->businessesAsAdmin()->attach($business->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Establecer este negocio como activo para el usuario
        if (method_exists($user, 'activeRoles')) {
            $user->activeRoles()->updateOrCreate(
                ['user_id' => $user->id, 'business_id' => $business->id],
                [
                    'active_role' => 'admin',
                    'switched_at' => now(),
                ]
            );
        }

        return $this->created([
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'address' => $business->address,
                'phone' => $business->phone,
                'email' => $business->email,
                'description' => $business->description,
                'invitation_code' => $business->invitation_code,
                'slug' => $business->slug,
                'is_active' => $business->is_active,
                'created_at' => $business->created_at,
                'updated_at' => $business->updated_at,
            ],
            'invitation_url' => config('app.frontend_url') . '/join-business?code=' . $business->invitation_code,
            'admin_assigned' => true,
            'active_business_set' => true,
        ], 'Negocio creado exitosamente');
    }

    public function regenerateInvitationCode(Request $request)
    {
        $user = $request->user();
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $businessId = $request->business_id;
        $business = Business::findOrFail($businessId);
        
        $business->regenerateInvitationCode();
        
        return $this->success([
            'invitation_code' => $business->invitation_code,
            'invitation_url' => config('app.frontend_url') . '/join-business?code=' . $business->invitation_code,
        ], 'Código de invitación regenerado exitosamente');
    }

    /**
     * Eliminar un negocio y sus entidades relacionadas (solo admins de ese negocio)
     */
    public function deleteBusiness(Request $request, int $businessId)
    {
        $user = $request->user();

        // Verificar que el usuario sea admin de este negocio
        $isAdmin = method_exists($user, 'businessesAsAdmin')
            ? $user->businessesAsAdmin()->where('business_id', $businessId)->exists()
            : false;
        if (!$isAdmin) {
            return $this->forbidden('No tienes permisos para eliminar este negocio');
        }

        $business = Business::find($businessId);
        if (!$business) {
            return $this->notFound('Negocio no encontrado');
        }

        \DB::beginTransaction();
        try {
            // 🔥 PUNTO 9: Eliminar datos de Firebase ANTES de eliminar de BBDD
            // Esto se hace primero para tener acceso a las relaciones (staff, tables, etc)
            $firebaseCleanup = null;
            try {
                $firebaseService = app(UnifiedFirebaseService::class);
                $firebaseCleanup = $firebaseService->deleteBusinessData($businessId);
                
                \Log::info('Firebase cleanup result', [
                    'business_id' => $businessId,
                    'cleanup_result' => $firebaseCleanup
                ]);
            } catch (\Throwable $firebaseError) {
                // ⚠️ NO FALLAR la eliminación de BBDD si Firebase falla
                \Log::error('Firebase cleanup failed but continuing with BBDD deletion', [
                    'business_id' => $businessId,
                    'firebase_error' => $firebaseError->getMessage(),
                    'trace' => $firebaseError->getTraceAsString()
                ]);
            }

            // Eliminar dependencias conocidas
            if (\Schema::hasTable('tables')) {
                Table::where('business_id', $businessId)->each(function ($table) {
                    // Eliminar QRs asociados a la mesa
                    if (method_exists($table, 'qrCodes')) {
                        $table->qrCodes()->delete();
                    }
                    // Eliminar llamadas de mozo
                    if (method_exists($table, 'waiterCalls')) {
                        $table->waiterCalls()->delete();
                    }
                    $table->delete();
                });
            }

            if (\Schema::hasTable('menus')) {
                Menu::where('business_id', $businessId)->each(function ($menu) {
                    if (!empty($menu->file_path) && \Storage::disk('public')->exists($menu->file_path)) {
                        \Storage::disk('public')->delete($menu->file_path);
                    }
                    $menu->delete();
                });
            }
            if (\Schema::hasTable('qr_codes')) {
                QrCode::where('business_id', $businessId)->delete();
            }
            if (\Schema::hasTable('staff')) {
                Staff::where('business_id', $businessId)->delete();
            }
            if (\Schema::hasTable('business_admins')) {
                \DB::table('business_admins')->where('business_id', $businessId)->delete();
            }
            if (\Schema::hasTable('business_waiters')) {
                \DB::table('business_waiters')->where('business_id', $businessId)->delete();
            }
            if (\Schema::hasTable('user_active_roles')) {
                \DB::table('user_active_roles')->where('business_id', $businessId)->delete();
            }

            // Finalmente eliminar el negocio
            $business->delete();

            \DB::commit();
            
            // Preparar respuesta con información de limpieza de Firebase
            $response = [
                'message' => 'Negocio eliminado correctamente'
            ];
            
            if ($firebaseCleanup && $firebaseCleanup['success']) {
                $response['firebase_cleanup'] = [
                    'status' => 'success',
                    'deleted_paths' => $firebaseCleanup['summary']['total_deleted'],
                    'errors' => $firebaseCleanup['summary']['total_errors']
                ];
            } elseif ($firebaseCleanup && !$firebaseCleanup['success']) {
                $response['firebase_cleanup'] = [
                    'status' => 'partial_failure',
                    'deleted_paths' => $firebaseCleanup['summary']['total_deleted'],
                    'errors' => $firebaseCleanup['summary']['total_errors'],
                    'warning' => 'Algunos datos de Firebase no se pudieron eliminar'
                ];
            } else {
                $response['firebase_cleanup'] = [
                    'status' => 'failed',
                    'warning' => 'No se pudo limpiar Firebase pero la BBDD se eliminó correctamente'
                ];
            }
            
            return $this->success($response);
        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error('Error eliminando negocio', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Error interno al eliminar el negocio', 500);
        }
    }

    public function switchView(Request $request)
    {
        $request->validate([
            'view' => ['required', Rule::in(['admin', 'waiter'])],
        ]);

        $user = $request->user();
        
        return $this->success([
            'view' => $request->view,
            'token' => $user->createToken('api-token', ['role:' . $request->view])->plainTextToken,
        ], 'Vista cambiada exitosamente');
    }

    /**
     * Lista negocios del admin y marca el activo
     */
    public function getBusinesses(Request $request)
    {
        $user = $request->user();

        // Detectar activo
        $activeBusinessId = null;
        try {
            if (method_exists($user, 'activeRoles')) {
                $active = $user->activeRoles()
                    ->latest('switched_at')
                    ->first();
                if ($active) { $activeBusinessId = $active->business_id; }
            }
        } catch (\Throwable $e) { /* noop */ }
        if (!$activeBusinessId && !empty($request->business_id)) {
            $activeBusinessId = $request->business_id;
        }

        // Obtener lista sin pluck del pivot ni columnas inexistentes
        $businesses = [];
        if (method_exists($user, 'businessesAsAdmin')) {
            $adminBusinesses = $user->businessesAsAdmin()
                ->select('businesses.id', 'businesses.name')
                ->get();
            $businesses = $adminBusinesses->map(function ($b) use ($activeBusinessId) {
                return [
                    'id' => $b->id,
                    'name' => $b->name,
                    'slug' => $b->slug ?? null,
                    'is_active' => (int)$b->id === (int)$activeBusinessId,
                ];
            });
        } elseif (!empty($request->business_id)) {
            $b = Business::find($request->business_id);
            if ($b) {
                $businesses = [[
                    'id' => $b->id,
                    'name' => $b->name,
                    'slug' => $b->slug ?? null,
                    'is_active' => (int)$b->id === (int)$activeBusinessId,
                ]];
            }
        }

        return $this->success([
            'active_business_id' => $activeBusinessId ? (int)$activeBusinessId : null,
            'businesses' => $businesses,
            'count' => is_countable($businesses) ? count($businesses) : 0,
        ]);
    }

    public function listMenus(Request $request)
    {
        $user = $request->user();
        
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $menus = Menu::where('business_id', $request->business_id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return $this->success(['menus' => $menus]);
    }

    public function uploadMenu(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'is_default' => 'boolean',
        ]);
        
        $user = $request->user();
        
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $path = $request->file('file')->store('menus/' . $request->business_id, 'public');
        
        if ($request->is_default) {
            Menu::where('business_id', $request->business_id)
                ->update(['is_default' => false]);
        }
        
        $menu = Menu::create([
            'business_id' => $request->business_id,
            'name' => $request->name,
            'file_path' => $path,
            'is_default' => $request->is_default ?? false,
        ]);
        
        return $this->created(['menu' => $menu], 'Menú subido exitosamente');
    }

    public function setDefaultMenu(Request $request)
    {
        $request->validate([
            'menu_id' => 'required|exists:menus,id',
        ]);
        
        $menu = Menu::where('id', $request->menu_id)
            ->where('business_id', $request->business_id)
            ->firstOrFail();
        
        Menu::where('business_id', $request->business_id)
            ->update(['is_default' => false]);
        
        $menu->is_default = true;
        $menu->save();
        
        return $this->updated(['menu' => $menu], 'Menú establecido como predeterminado');
    }

    public function createQR(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:tables,id',
        ]);
        
        $table = Table::where('id', $request->table_id)
            ->where('business_id', $request->business_id)
            ->firstOrFail();
        
        $qrCode = QrCode::create([
            'business_id' => $request->business_id,
            'table_id' => $table->id,
            'code' => uniqid('qr_', true),
        ]);
        
        return $this->created(['qr_code' => $qrCode], 'Código QR creado exitosamente');
    }

    public function exportQR(Request $request)
    {
        $request->validate([
            'qr_ids' => 'required|array',
            'qr_ids.*' => 'exists:qr_codes,id',
            'format' => ['required', Rule::in(['pdf', 'png'])],
        ]);
        
        $qrCodes = QrCode::whereIn('id', $request->qr_ids)
            ->where('business_id', $request->business_id)
            ->with('table')
            ->get();
        
        if ($qrCodes->count() !== count($request->qr_ids)) {
            return $this->forbidden('Algunos códigos QR no pertenecen a tu negocio');
        }
        
        return $this->success([
            'format' => $request->format,
            'qr_codes' => $qrCodes,
            'download_url' => 'https://example.com/downloads/' . uniqid() . '.' . $request->format,
        ], 'Códigos QR exportados exitosamente');
    }

    
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
            \Log::warning('Fallo en efectos colaterales al desvincular staff', [
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
            \Log::warning('No se pudo notificar desvinculación en Firebase', [
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
                    app(\App\Services\StaffNotificationService::class)->writeStaffRequest($staff, 'confirmed');
                } catch (\Throwable $e) {
                    \Log::warning('Failed to update Firebase after confirm', ['error' => $e->getMessage()]);
                }

                // Actualizar notificaciones existentes con el nuevo status
                $this->updateStaffNotificationsStatus($staff->id, 'confirmed');

                return $this->success(['staff' => $staff], 'Solicitud de personal confirmada');

            case 'reject':
                $staff->status = 'rejected';
                $staff->save();

                // Actualizar Firebase y notificaciones
                try {
                    app(\App\Services\StaffNotificationService::class)->writeStaffRequest($staff, 'rejected');
                } catch (\Throwable $e) {
                    \Log::warning('Failed to update Firebase after reject', ['error' => $e->getMessage()]);
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

            case 'unarchive':
                // Permitir desarchivar desde archived_staff
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

                $payload = [
                    'business_id' => $archived->business_id,
                    'user_id' => $archived->user_id ?? ($original['user_id'] ?? null),
                    'name' => $archived->name ?? ($original['name'] ?? null),
                    'position' => $archived->position ?? ($original['position'] ?? null),
                    'email' => $archived->email ?? ($original['email'] ?? null),
                    'phone' => $archived->phone ?? ($original['phone'] ?? null),
                    'hire_date' => $archived->hire_date ?? ($original['hire_date'] ?? null),
                    'status' => $original['status'] ?? 'pending',
                    'notes' => $archived->notes ?? ($original['notes'] ?? null),
                ];

                // Campos extendidos si existen en original_data
                foreach (['birth_date','height','weight','gender','experience_years','seniority_years','education','employment_type','current_schedule','avatar_path'] as $field) {
                    if (array_key_exists($field, $original)) {
                        $payload[$field] = $original[$field];
                    }
                }

                if ($existing) {
                    $existing->fill($payload);
                    // Si estaba rechazado, volver a pending
                    if ($existing->status === 'rejected') {
                        $existing->status = 'pending';
                    }
                    $existing->save();
                    $restored = $existing;
                } else {
                    $restored = Staff::create($payload);
                }

                // Eliminar el registro archivado
                $archived->delete();

                return response()->json([
                    'message' => 'Solicitud desarchivada exitosamente',
                    'staff' => $restored,
                ]);
        }
    }
    
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
                            $avatarUrl = \Storage::disk('public')->url($profileData['avatar']);
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
                        $avatarUrl = \Storage::disk('public')->url($profileData['avatar']);
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
        // 🔥 CAMBIO: Buscar por user_id en vez de id
        $staff = Staff::where('user_id', $id)
            ->where('business_id', $request->business_id)
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

    public function getSettings(Request $request)
    {
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $business = Business::findOrFail($request->business_id);
        
        return response()->json([
            'business' => $business,
            'settings' => [
                'name' => $business->name,
                'address' => $business->address,
                'phone' => $business->phone,
                'email' => $business->email,
                'logo' => $business->logo,
                'working_hours' => $business->working_hours,
                'notification_preferences' => $business->notification_preferences,
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        // Validar tanto en nivel raíz como anidado bajo 'business' o 'settings'
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'business.name' => 'sometimes|string|max:255',
            'settings.name' => 'sometimes|string|max:255',

            'address' => 'sometimes|string|max:255',
            'business.address' => 'sometimes|string|max:255',
            'settings.address' => 'sometimes|string|max:255',

            'phone' => 'sometimes|string|max:20',
            'business.phone' => 'sometimes|string|max:20',
            'settings.phone' => 'sometimes|string|max:20',

            'email' => 'sometimes|email|max:255',
            'business.email' => 'sometimes|email|max:255',
            'settings.email' => 'sometimes|email|max:255',

            'description' => 'sometimes|string|max:500',
            'business.description' => 'sometimes|string|max:500',
            'settings.description' => 'sometimes|string|max:500',

            'logo' => 'sometimes|file|image|max:2048',
            'logo_base64' => 'sometimes|string',

            'working_hours' => 'sometimes|array',
            'settings.working_hours' => 'sometimes|array',

            'notification_preferences' => 'sometimes|array',
            'settings.notification_preferences' => 'sometimes|array',
        ]);

        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $business = Business::findOrFail($request->business_id);

        // Helper para extraer valor desde raíz, business.* o settings.*
        $getVal = function (string $key) use ($request) {
            if ($request->has($key)) return $request->input($key);
            if ($request->has("business.$key")) return $request->input("business.$key");
            if ($request->has("settings.$key")) return $request->input("settings.$key");
            return null;
        };

        foreach (['name', 'address', 'phone', 'email', 'description'] as $field) {
            $val = $getVal($field);
            if ($val !== null) {
                $business->$field = $val;
            }
        }

        // Logo por archivo
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $business->logo = $path;
        }
        // Logo por base64
        if ($request->filled('logo_base64')) {
            try {
                $business->logo = $this->storeBase64Image($request->input('logo_base64'), $business->id);
            } catch (\Throwable $e) {
                // Ignorar error de logo base64 inválido; no bloquea el resto de cambios
            }
        }

        // Campos JSON de configuración
        $wh = $getVal('working_hours');
        if ($wh !== null) {
            $business->working_hours = $wh;
        }
        $np = $getVal('notification_preferences');
        if ($np !== null) {
            $business->notification_preferences = $np;
        }

        $business->save();
        $business->refresh();

        return response()->json([
            'message' => 'Configuración actualizada exitosamente',
            'business' => $business,
        ], 200, [], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }

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
            $notifications = \DB::table('notifications')
                ->whereRaw("JSON_EXTRACT(data, '$.staff_id') = ?", [(string)$staffId])
                ->get();

            foreach ($notifications as $notification) {
                $data = json_decode($notification->data, true);
                $data['status'] = $newStatus;

                \DB::table('notifications')
                    ->where('id', $notification->id)
                    ->update(['data' => json_encode($data)]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to update notifications status', [
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
            \Log::warning('No se pudieron limpiar mesas/llamados al desvincular mozo', [
                'staff_id' => $staff->id,
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);
        }

        // Revocar relación en pivot business_waiters si existe
        try {
            if (\Schema::hasTable('business_waiters') && $staff->user_id) {
                \DB::table('business_waiters')
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

    public function getStatistics(Request $request)
    {
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $businessId = $request->business_id;

        $warnings = [];

        $tablesCount = 0;
        if (Schema::hasTable('tables')) {
            $tablesCount = Table::where('business_id', $businessId)->count();
        } else {
            $warnings[] = 'Tabla tables no encontrada';
        }

        $menusCount = 0;
        if (Schema::hasTable('menus')) {
            $menusCount = Menu::where('business_id', $businessId)->count();
        } else {
            $warnings[] = 'Tabla menus no encontrada';
        }

        $staffCount = 0;
        $pendingRequests = 0;
        if (Schema::hasTable('staff')) {
            $staffCount = Staff::where('business_id', $businessId)->count();
            $pendingRequests = Staff::where('business_id', $businessId)
                ->where('status', 'pending')->count();
        } else {
            $warnings[] = 'Tabla staff no encontrada';
        }

        $qrCodesCount = 0;
        if (Schema::hasTable('qr_codes')) {
            $qrCodesCount = QrCode::where('business_id', $businessId)->count();
        } else {
            $warnings[] = 'Tabla qr_codes no encontrada';
        }

        $archivedStaffCount = 0;
        if (Schema::hasTable('archived_staff')) {
            $archivedStaffCount = ArchivedStaff::where('business_id', $businessId)->count();
        } else {
            $warnings[] = 'Tabla archived_staff no encontrada';
        }

        return response()->json([
            'active_business_id' => $businessId ? (int)$businessId : null,
            'tables_count' => $tablesCount,
            'menus_count' => $menusCount,
            'staff_count' => $staffCount,
            'pending_requests_count' => $pendingRequests,
            'qr_codes_count' => $qrCodesCount,
            'archived_staff_count' => $archivedStaffCount,
            'warnings' => $warnings,
        ]);
    }

    public function sendTestNotification(Request $request)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string|max:500',
        ]);
        
        $users = User::where('business_id', $request->business_id)->get();

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No hay usuarios en este negocio para enviar la notificación de prueba'
            ], 404);
        }

        $title = $request->title ?? 'Notificación de Prueba';
        $body = $request->body ?? 'Esta es una notificación de prueba del sistema';

        $notificationCount = 0;

        foreach ($users as $targetUser) {
            try {
                $targetUser->notify(new \App\Notifications\TestNotification($title, $body));
                $notificationCount++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return response()->json([
            'message' => "Notificación de prueba enviada exitosamente a {$notificationCount} usuarios",
            'users_notified' => $notificationCount,
            'total_users' => $users->count(),
        ]);
    }

    public function sendNotificationToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:500',
            'data' => 'sometimes|array',
        ]);

        $targetUser = User::find($request->user_id);

        if (!$targetUser) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        if ($targetUser->business_id !== $request->business_id) {
            return response()->json(['message' => 'No tienes permisos para enviar notificaciones a este usuario'], 403);
        }

        try {
            $targetUser->notify(new \App\Notifications\UserSpecificNotification(
                $request->title,
                $request->body,
                $request->data ?? []
            ));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al enviar la notificación',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Notificación enviada exitosamente al usuario ' . $targetUser->name,
            'sent_to' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
            ]
        ]);
    }

    /**
     * Procesar todas las solicitudes pendientes de una vez
     */
    public function bulkProcessRequests(Request $request)
    {
        $request->validate([
            'action' => ['required', Rule::in(['confirm_all', 'archive_all'])],
        ]);
        
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
     * 🔥 PUNTO 10: Generar enlace directo a WhatsApp para contactar al mozo (por user_id)
     */
    public function getWhatsAppLink(Request $request, $userId)
    {
        // 🔥 CAMBIO: Buscar por user_id
        $staff = Staff::where('user_id', $userId)
            ->where('business_id', $request->business_id)
            ->firstOrFail();

        if (!$staff->phone) {
            return response()->json([
                'error' => 'Este empleado no tiene número de teléfono registrado'
            ], 422);
        }

        // Limpiar número de teléfono
        $phone = preg_replace('/[^0-9]/', '', $staff->phone);
        
        // Agregar código de país si no lo tiene (asumiendo Argentina +54)
        if (!str_starts_with($phone, '54') && strlen($phone) === 10) {
            $phone = '54' . $phone;
        }

        $businessName = $user->activeBusiness ? $user->activeBusiness->name : 'mi negocio';
        $message = urlencode("Hola {$staff->name}, me comunico desde {$businessName}.");
        $whatsappUrl = "https://wa.me/{$phone}?text={$message}";

        return response()->json([
            'whatsapp_url' => $whatsappUrl,
            'phone' => $staff->phone,
            'formatted_phone' => "+{$phone}"
        ]);
    }

    /**
     * Obtener perfil del admin actual
     */
    public function getAdminProfile(Request $request)
    {
        $user = $request->user();
        // Asegurar existencia de adminProfile (global) sin forzar campos
        $adminProfile = $user->adminProfile ?: $user->adminProfile()->first();

        $avatarUrl = null;
        $phoneValue = null;
        $position = null;
        if ($adminProfile) {
            $avatarUrl = $adminProfile->avatar
                ? asset('storage/' . $adminProfile->avatar)
                : 'https://ui-avatars.com/api/?name=' . urlencode($adminProfile->display_name ?? $user->name) . '&color=DC2626&background=FEE2E2';
            $phoneValue = $adminProfile->corporate_phone; // almacenado internamente como corporate_phone
            $position = $adminProfile->position;
        }

        return response()->json([
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $avatarUrl,
                'phone' => $phoneValue,
                'position' => $position,
                'business' => $user->activeBusiness ? [
                    'id' => $user->activeBusiness->id,
                    'name' => $user->activeBusiness->name,
                ] : null,
                'created_at' => $user->created_at,
            ]
        ]);
    }

    /**
     * Actualizar perfil del admin
     */
    public function updateAdminProfile(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'sometimes|string|max:20',
            'corporate_phone' => 'sometimes|string|max:20', // alias aceptado
            'position' => 'sometimes|string|max:255',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Actualizar datos del usuario
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        
        $user->save();

        // Actualizar o crear adminProfile
        $adminProfile = $user->adminProfile ?: $user->adminProfile()->create([]);

        // Aceptar 'phone' como campo principal (se guarda en corporate_phone)
        if ($request->has('phone') || $request->has('corporate_phone')) {
            $adminProfile->corporate_phone = $request->get('phone', $request->get('corporate_phone'));
        }
        if ($request->has('position')) {
            $adminProfile->position = $request->position;
        }

        // Manejar subida de avatar
        if ($request->hasFile('avatar')) {
            if ($adminProfile->avatar) {
                Storage::disk('public')->delete($adminProfile->avatar);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $adminProfile->avatar = $avatarPath;
        }

        $adminProfile->save();

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $adminProfile->corporate_phone,
                'position' => $adminProfile->position,
                'avatar_url' => $adminProfile->avatar
                    ? asset('storage/' . $adminProfile->avatar)
                    : 'https://ui-avatars.com/api/?name=' . urlencode($adminProfile->display_name ?? $user->name) . '&color=DC2626&background=FEE2E2',
            ]
        ]);
    }

} 
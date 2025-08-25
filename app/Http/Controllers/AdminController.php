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

class AdminController extends Controller
{
    use ResolvesActiveBusiness;
    public function getBusinessInfo(Request $request)
    {
        $user = $request->user();

        // Determinar negocio activo para el rol admin
        $activeBusinessId = null;
        try {
            if (method_exists($user, 'activeRoles')) {
                $active = $user->activeRoles()
                    ->where('active_role', 'admin')
                    ->latest('switched_at')
                    ->first();
                if ($active) { $activeBusinessId = $active->business_id; }
            }
        } catch (\Throwable $e) { /* noop */ }

        // Fallbacks
        if (!$activeBusinessId && !empty($user->business_id)) {
            $activeBusinessId = $user->business_id;
        }
        if (!$activeBusinessId && method_exists($user, 'businessesAsAdmin')) {
            $adminBizIds = $user->businessesAsAdmin()->pluck('business_id');
            if ($adminBizIds->count() === 1) {
                $activeBusinessId = (int)$adminBizIds->first();
            }
        }

        // Último fallback: si aún no hay, seleccionar el primero de la lista del usuario
        if (!$activeBusinessId && method_exists($user, 'businessesAsAdmin')) {
            $activeBusinessId = optional($user->businessesAsAdmin()->first())->business_id;
        }

        // Si aún no hay negocio activo, significa que es un admin nuevo
        if (!$activeBusinessId) {
            return response()->json([
                'message' => 'No businesses found. Admin needs to create or join a business.',
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
            ], 200);
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
            } elseif (!empty($user->business_id)) {
                $b = Business::find($user->business_id);
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

        return response()->json([
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

        return response()->json([
            'message' => 'Negocio creado exitosamente',
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
        ], 201);
    }

    public function regenerateInvitationCode(Request $request)
    {
        $user = $request->user();
        $businessId = $this->activeBusinessId($user, 'admin');
        $business = Business::findOrFail($businessId);
        
        $business->regenerateInvitationCode();
        
        return response()->json([
            'message' => 'Código de invitación regenerado exitosamente',
            'invitation_code' => $business->invitation_code,
            'invitation_url' => config('app.frontend_url') . '/join-business?code=' . $business->invitation_code,
        ]);
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
            return response()->json([
                'message' => 'No tienes permisos para eliminar este negocio'
            ], 403);
        }

        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'message' => 'Negocio no encontrado'
            ], 404);
        }

        \DB::beginTransaction();
        try {
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
            return response()->json([
                'message' => 'Negocio eliminado correctamente'
            ]);
        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error('Error eliminando negocio', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Error interno al eliminar el negocio'
            ], 500);
        }
    }

    public function switchView(Request $request)
    {
        $request->validate([
            'view' => ['required', Rule::in(['admin', 'waiter'])],
        ]);

        $user = $request->user();
        
        return response()->json([
            'message' => 'Vista cambiada exitosamente',
            'view' => $request->view,
            'token' => $user->createToken('api-token', ['role:' . $request->view])->plainTextToken,
        ]);
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
        if (!$activeBusinessId && !empty($user->business_id)) {
            $activeBusinessId = $user->business_id;
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
        } elseif (!empty($user->business_id)) {
            $b = Business::find($user->business_id);
            if ($b) {
                $businesses = [[
                    'id' => $b->id,
                    'name' => $b->name,
                    'slug' => $b->slug ?? null,
                    'is_active' => (int)$b->id === (int)$activeBusinessId,
                ]];
            }
        }

        return response()->json([
            'active_business_id' => $activeBusinessId ? (int)$activeBusinessId : null,
            'businesses' => $businesses,
            'count' => is_countable($businesses) ? count($businesses) : 0,
        ]);
    }

    public function listMenus(Request $request)
    {
        $user = $request->user();
        
        $menus = Menu::where('business_id', $user->business_id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'menus' => $menus,
        ]);
    }

    public function uploadMenu(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'is_default' => 'boolean',
        ]);
        
        $user = $request->user();
        
        $path = $request->file('file')->store('menus/' . $user->business_id, 'public');
        
        if ($request->is_default) {
            Menu::where('business_id', $user->business_id)
                ->update(['is_default' => false]);
        }
        
        $menu = Menu::create([
            'business_id' => $user->business_id,
            'name' => $request->name,
            'file_path' => $path,
            'is_default' => $request->is_default ?? false,
        ]);
        
        return response()->json([
            'message' => 'Menú subido exitosamente',
            'menu' => $menu,
        ], 201);
    }

    public function setDefaultMenu(Request $request)
    {
        $request->validate([
            'menu_id' => 'required|exists:menus,id',
        ]);
        
        $user = $request->user();
        
        $menu = Menu::where('id', $request->menu_id)
            ->where('business_id', $user->business_id)
            ->firstOrFail();
        
        Menu::where('business_id', $user->business_id)
            ->update(['is_default' => false]);
        
        $menu->is_default = true;
        $menu->save();
        
        return response()->json([
            'message' => 'Menú establecido como predeterminado',
            'menu' => $menu,
        ]);
    }

    public function createQR(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:tables,id',
        ]);
        
        $user = $request->user();
        
        $table = Table::where('id', $request->table_id)
            ->where('business_id', $user->business_id)
            ->firstOrFail();
        
        $qrCode = QrCode::create([
            'business_id' => $user->business_id,
            'table_id' => $table->id,
            'code' => uniqid('qr_', true),
        ]);
        
        return response()->json([
            'message' => 'Código QR creado exitosamente',
            'qr_code' => $qrCode,
        ], 201);
    }

    public function exportQR(Request $request)
    {
        $request->validate([
            'qr_ids' => 'required|array',
            'qr_ids.*' => 'exists:qr_codes,id',
            'format' => ['required', Rule::in(['pdf', 'png'])],
        ]);
        
        $user = $request->user();
        
        $qrCodes = QrCode::whereIn('id', $request->qr_ids)
            ->where('business_id', $user->business_id)
            ->with('table')
            ->get();
        
        if ($qrCodes->count() !== count($request->qr_ids)) {
            return response()->json([
                'message' => 'Algunos códigos QR no pertenecen a tu negocio',
            ], 403);
        }
        
        return response()->json([
            'message' => 'Códigos QR exportados exitosamente',
            'format' => $request->format,
            'qr_codes' => $qrCodes,
            'download_url' => 'https://example.com/downloads/' . uniqid() . '.' . $request->format,
        ]);
    }

    
    public function removeStaff($staffId)
    {
        $user = Auth::user();
        $businessId = $this->activeBusinessId($user, 'admin');

        $staff = Staff::where('id', $staffId)
            ->where('business_id', $businessId)
            ->first();

        if (!$staff) {
            return response()->json([
                'message' => 'El miembro de personal no existe o no pertenece a tu negocio activo',
                'staff_id' => (int)$staffId,
                'active_business_id' => (int)$businessId,
            ], 404);
        }

        $staff->delete();

        return response()->json([
            'message' => 'Personal eliminado exitosamente',
            'staff_id' => (int)$staffId
        ]);
    }
    
    public function handleStaffRequest(Request $request, $requestId)
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required', Rule::in(['confirm', 'reject', 'archive', 'archived', 'unarchive'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        $activeBusinessId = $this->activeBusinessId($user, 'admin');

        // Validación temprana de ID
        if (!is_numeric($requestId)) {
            return response()->json([
                'message' => 'ID de solicitud inválido',
                'request_id' => $requestId,
            ], 400);
        }

        // Si la acción es desarchivar, no requerimos un registro en staff
        if ($request->action === 'unarchive') {
            $archived = ArchivedStaff::where('id', (int)$requestId)
                ->where('business_id', $activeBusinessId)
                ->first();

            if (!$archived) {
                return response()->json([
                    'message' => 'Registro archivado no encontrado para desarchivar',
                    'archived_id' => (int)$requestId,
                    'active_business_id' => (int)$activeBusinessId,
                ], 404);
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

            return response()->json([
                'message' => 'Solicitud desarchivada exitosamente',
                'staff' => $restored,
            ]);
        }

        // Para el resto de acciones, se requiere que exista el registro en staff
        $staff = Staff::where('id', (int)$requestId)
            ->where('business_id', $activeBusinessId)
            ->first();

        if (!$staff) {
            return response()->json([
                'message' => 'Solicitud no encontrada para el negocio activo',
                'request_id' => (int)$requestId,
                'active_business_id' => (int)$activeBusinessId,
            ], 404);
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
                        'business_id' => $user->business_id,
                    ]);
                }
                
                return response()->json([
                    'message' => 'Solicitud de personal confirmada',
                    'staff' => $staff
                ]);
                
            case 'reject':
                $staff->status = 'rejected';
                $staff->save();
                
                return response()->json([
                    'message' => 'Solicitud de personal rechazada',
                    'staff' => $staff
                ]);
                
            case 'archive':
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
                    'status' => $staff->status,
                    'notes' => $staff->notes,
                    'original_data' => $staff->toArray(),
                    'archived_by' => $user->id,
                    'archive_reason' => $request->archive_reason ?? 'Archived from admin panel',
                    'archived_at' => now(),
                ]);

                $staff->delete();

                return response()->json([
                    'message' => 'Solicitud de personal archivada',
                ]);

            case 'archived':
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
                    'status' => $staff->status,
                    'notes' => $staff->notes,
                    'original_data' => $staff->toArray(),
                    'archived_by' => $user->id,
                    'archive_reason' => $request->archive_reason ?? 'Bulk/archive action',
                    'archived_at' => now(),
                ]);

                $staff->delete();

                return response()->json([
                    'message' => 'Solicitud de personal archivada',
                ]);

            case 'unarchive':
                // Permitir desarchivar desde archived_staff
                $archived = ArchivedStaff::where('id', (int)$requestId)
                    ->where('business_id', $activeBusinessId)
                    ->first();

                if (!$archived) {
                    return response()->json([
                        'message' => 'Registro archivado no encontrado para desarchivar',
                        'archived_id' => (int)$requestId,
                        'active_business_id' => (int)$activeBusinessId,
                    ], 404);
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
    
    public function fetchStaffRequests()
    {
        $user = Auth::user();
        $activeBusinessId = $this->activeBusinessId($user, 'admin');

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

        return response()->json([
            'requests' => $pendingRequests->map(function ($req) {
                $user = $req->user;

                $userData = $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->google_id,
                    'google_avatar' => $user->google_avatar,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ] : null;

                $profile = $user ? ($user->waiterProfile ?: $user->adminProfile) : null;
                $profileData = $profile ? $profile->toArray() : null;

                // Normalizar avatar a URL pública con HTTPS
                if ($profileData) {
                    $avatarUrl = null;
                    // Si el modelo expone avatar_url úsalo como preferido
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
                        // Expone avatar_url y también sustituye avatar por la URL completa para el front
                        $profileData['avatar_url'] = $avatarUrl;
                        $profileData['avatar'] = $avatarUrl;
                    }
                }

                return [
                    'id' => (int) $req->id,
                    'user' => $userData,
                    'user_profile' => $profileData,
                ];
            }),
            'count' => $pendingRequests->count(),
        ]);
    }
    
    public function fetchArchivedRequests()
    {
        $user = Auth::user();
        
        $activeBusinessId = $this->activeBusinessId($user, 'admin');

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
    $activeBusinessId = $this->activeBusinessId($user, 'admin');
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
                    $profileData['avatar'] = $profile->avatar_url;
                    unset($profileData['display_name']); // Nombre canónico está en user.name
                }

                return [
                    'user' => $userData,
                    'profile_data' => $profileData,
                ];
            }),
            'search' => $request->search,
            'total' => $staff->count(),
        ]);
    }

    public function getStaffMember(Request $request, $id)
    {
        $user = $request->user();
        $activeBusinessId = $this->activeBusinessId($user, 'admin');

        if (!is_numeric($id)) {
            return response()->json([
                'message' => 'ID de staff inválido',
                'staff_id' => $id,
            ], 400);
        }

        $staff = Staff::with('reviews')
            ->where('id', (int)$id)
            ->where('business_id', $activeBusinessId)
            ->where('status', 'confirmed')
            ->first();

        if (!$staff) {
            return response()->json([
                'message' => 'Staff no encontrado o no confirmado para el negocio activo',
                'staff_id' => (int)$id,
                'active_business_id' => (int)$activeBusinessId,
            ], 404);
        }

        return response()->json(['staff' => $staff]);
    }

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

        $user = $request->user();
        $activeBusinessId = $this->activeBusinessId($user, 'admin');
        $staff = Staff::where('id', $id)
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

    public function inviteStaff(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'required|email|max:255|unique:staff,email',
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

    $user = $request->user();
    $activeBusinessId = $this->activeBusinessId($user, 'admin');

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
            'name' => $request->name,
            'position' => $request->position,
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
        ]);


        return response()->json([
            'message' => 'Invitación enviada exitosamente',
            'staff' => $staff,
        ], 201);
    }

    public function addReview(Request $request, $staffId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'sometimes|string',
        ]);

        $user = $request->user();
        $activeBusinessId = $this->activeBusinessId($user, 'admin');
        $staff = Staff::where('id', $staffId)
            ->where('business_id', $activeBusinessId)
            ->firstOrFail();

        $review = Review::create([
            'business_id' => $activeBusinessId,
            'staff_id' => $staff->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Reseña añadida exitosamente',
            'review' => $review,
        ], 201);
    }

    public function deleteReview(Request $request, $staffId, $id)
    {
        $user = $request->user();
        $activeBusinessId = $this->activeBusinessId($user, 'admin');
        $staff = Staff::where('id', $staffId)
            ->where('business_id', $activeBusinessId)
            ->firstOrFail();

        $review = Review::where('id', $id)
            ->where('staff_id', $staff->id)
            ->where('business_id', $activeBusinessId)
            ->firstOrFail();

        $review->delete();

        return response()->json([
            'message' => 'Reseña eliminada exitosamente',
        ]);
    }

    public function getSettings(Request $request)
    {
        $user = $request->user();
        $businessId = $this->activeBusinessId($user, 'admin');
        $business = Business::findOrFail($businessId);
        
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

        $user = $request->user();
        $businessId = $this->activeBusinessId($user, 'admin');
        $business = Business::findOrFail($businessId);

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

    public function getStatistics(Request $request)
    {
        $user = $request->user();
        $businessId = $this->activeBusinessId($user, 'admin');

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

        $user = $request->user();
        
        $users = User::where('business_id', $user->business_id)->get();

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

        $currentUser = $request->user();
        $targetUser = User::find($request->user_id);

        if (!$targetUser) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        if ($targetUser->business_id !== $currentUser->business_id) {
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

        $user = Auth::user();
        
        $pendingRequests = Staff::where('business_id', $user->business_id)
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
     * Generar enlace directo a WhatsApp para contactar al mozo
     */
    public function getWhatsAppLink(Request $request, $staffId)
    {
        $user = Auth::user();
        
        $staff = Staff::where('id', $staffId)
            ->where('business_id', $user->business_id)
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
        
        return response()->json([
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->profile && $user->profile->profile_picture 
                    ? asset('storage/' . $user->profile->profile_picture) 
                    : null,
                'phone' => $user->profile->phone ?? null,
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

        // Actualizar o crear perfil
        $profile = $user->profile()->firstOrCreate([]);
        
        if ($request->has('phone')) {
            $profile->phone = $request->phone;
        }

        // Manejar subida de avatar
        if ($request->hasFile('avatar')) {
            // Eliminar avatar anterior si existe
            if ($profile->profile_picture) {
                Storage::disk('public')->delete($profile->profile_picture);
            }
            
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $profile->profile_picture = $avatarPath;
        }
        
        $profile->save();

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $profile->phone,
                'avatar_url' => $profile->profile_picture 
                    ? asset('storage/' . $profile->profile_picture) 
                    : null,
            ]
        ]);
    }

} 
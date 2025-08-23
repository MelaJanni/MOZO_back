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
        
        $staff = Staff::where('id', $staffId)
            ->where('business_id', $user->business_id)
            ->firstOrFail();
        
        $staff->delete();
        
        return response()->json([
            'message' => 'Personal eliminado exitosamente',
            'staff_id' => $staffId
        ]);
    }
    
    public function handleStaffRequest(Request $request, $requestId)
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required', Rule::in(['confirm', 'reject', 'archive', 'archived'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        $staff = Staff::where('id', $requestId)
            ->where('business_id', $user->business_id)
            ->firstOrFail();
        
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
                    'name' => $staff->name,
                    'position' => $staff->position,
                    'email' => $staff->email,
                    'phone' => $staff->phone,
                    'hire_date' => $staff->hire_date,
                    'last_salary' => $staff->salary,
                    'status' => $staff->status,
                    'notes' => $staff->notes,
                    'termination_date' => now(),
                    'termination_reason' => $request->termination_reason ?? null,
                    'archived_at' => now(),
                    'birth_date' => $staff->birth_date,
                    'height' => $staff->height,
                    'weight' => $staff->weight,
                    'gender' => $staff->gender,
                    'experience_years' => $staff->experience_years,
                    'seniority_years' => $staff->seniority_years,
                    'education' => $staff->education,
                    'employment_type' => $staff->employment_type,
                    'current_schedule' => $staff->current_schedule,
                    'avatar_path' => $staff->avatar_path,
                ]);
                
                $staff->delete();
                
                return response()->json([
                    'message' => 'Solicitud de personal archivada',
                ]);

            case 'archived':
                ArchivedStaff::create([
                    'business_id' => $staff->business_id,
                    'name' => $staff->name,
                    'position' => $staff->position,
                    'email' => $staff->email,
                    'phone' => $staff->phone,
                    'hire_date' => $staff->hire_date,
                    'last_salary' => $staff->salary,
                    'status' => $staff->status,
                    'notes' => $staff->notes,
                    'termination_date' => now(),
                    'termination_reason' => $request->termination_reason ?? null,
                    'archived_at' => now(),
                    'birth_date' => $staff->birth_date,
                    'height' => $staff->height,
                    'weight' => $staff->weight,
                    'gender' => $staff->gender,
                    'experience_years' => $staff->experience_years,
                    'seniority_years' => $staff->seniority_years,
                    'education' => $staff->education,
                    'employment_type' => $staff->employment_type,
                    'current_schedule' => $staff->current_schedule,
                    'avatar_path' => $staff->avatar_path,
                ]);

                $staff->delete();

                return response()->json([
                    'message' => 'Solicitud de personal archivada',
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
            ->with(['user.profile'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'requests' => $pendingRequests->map(function($request) {
                $data = $request->toArray();
                
                // Agregar datos del perfil del usuario si está conectado
                if ($request->user && $request->user->profile) {
                    $data['user_profile'] = $request->user->profile;
                }
                
                return $data;
            }),
            'count' => $pendingRequests->count()
        ]);
    }
    
    public function fetchArchivedRequests()
    {
        $user = Auth::user();
        
        $activeBusinessId = $this->activeBusinessId($user, 'admin');

        if (!Schema::hasTable('archived_staff')) {
            return response()->json([
                'archived_requests' => [],
                'count' => 0,
                'warning' => 'Tabla archived_staff no encontrada. Aplique las migraciones para habilitar esta funcionalidad.'
            ]);
        }

        $archivedRequests = ArchivedStaff::where('business_id', $activeBusinessId)
            ->orderBy('archived_at', 'desc')
            ->get();
        
        return response()->json([
            'archived_requests' => $archivedRequests,
            'count' => $archivedRequests->count()
        ]);
    }


    public function getStaff(Request $request)
    {
    $user = $request->user();
    $activeBusinessId = $this->activeBusinessId($user, 'admin');
    $query = Staff::where('business_id', $activeBusinessId)
            ->whereNotIn('status', ['rejected']);

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

    $staff = $query->with(['user.adminProfile', 'user.waiterProfile', 'reviews'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'staff' => $staff->map(function($staffMember) {
                $data = $staffMember->toArray();
                
                // Agregar datos del perfil del usuario si está conectado
                if ($staffMember->user && $staffMember->user->profile()) {
                    $data['user_profile'] = $staffMember->user->profile();
                }
                
                // Agregar negocios asociados
                if ($staffMember->user) {
                    $data['associated_businesses'] = $staffMember->user->businesses->map(function($business) {
                        return [
                            'id' => $business->id,
                            'name' => $business->name,
                        ];
                    });
                }
                
                return $data;
            }),
            'search' => $request->search,
            'total' => $staff->count(),
        ]);
    }

    public function getStaffMember(Request $request, $id)
    {
        $user = $request->user();
        $activeBusinessId = $this->activeBusinessId($user, 'admin');
        $staff = Staff::with('reviews')
            ->where('id', $id)
            ->where('business_id', $activeBusinessId)
            ->firstOrFail();

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
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:255',
            'logo' => 'sometimes|file|image|max:2048',
            'working_hours' => 'sometimes|array',
            'notification_preferences' => 'sometimes|array',
        ]);
        
        $user = $request->user();
        $businessId = $this->activeBusinessId($user, 'admin');
        $business = Business::findOrFail($businessId);
        
        if ($request->has('name')) {
            $business->name = $request->name;
        }
        
        if ($request->has('address')) {
            $business->address = $request->address;
        }
        
        if ($request->has('phone')) {
            $business->phone = $request->phone;
        }
        
        if ($request->has('email')) {
            $business->email = $request->email;
        }
        
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $business->logo = $path;
        }
        
        if ($request->has('working_hours')) {
            $business->working_hours = $request->working_hours;
        }
        
        if ($request->has('notification_preferences')) {
            $business->notification_preferences = $request->notification_preferences;
        }
        
        $business->save();
        
        return response()->json([
            'message' => 'Configuración actualizada exitosamente',
            'business' => $business,
        ]);
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
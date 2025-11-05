<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\JsonResponses;
use App\Models\Business;
use App\Models\Menu;
use App\Models\QrCode;
use App\Models\Staff;
use App\Models\Table;
use App\Models\WaiterCall;
use App\Services\UnifiedFirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminBusinessController extends Controller
{
    use JsonResponses;

    /**
     * Obtener información completa del negocio activo
     * 
     * Endpoint: GET /api/admin/business
     */
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
                
                Log::info('Auto-created user_active_role for persistent session', [
                    'user_id' => $user->id,
                    'business_id' => $activeBusinessId,
                    'role' => 'admin'
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to create user_active_role', [
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
            'menus_count' => Schema::hasTable('menus') ? ($business->relationLoaded('menus') ? $business->menus->count() : Menu::where('business_id', $business->id)->count()) : 0,
            'qr_codes_count' => Schema::hasTable('qr_codes') ? ($business->relationLoaded('qrCodes') ? $business->qrCodes->count() : QrCode::where('business_id', $business->id)->count()) : 0,
            'invitation_code' => $business->invitation_code,
            'invitation_url' => config('app.frontend_url') . '/join-business?code=' . $business->invitation_code,
            'available_businesses' => $availableBusinesses,
        ]);
    }

    /**
     * Crear nuevo negocio y asignar admin
     * 
     * Endpoint: POST /api/admin/business/create
     */
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

    /**
     * Regenerar código de invitación del negocio
     * 
     * Endpoint: POST /api/admin/business/regenerate-invitation-code
     */
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
     * 
     * Endpoint: DELETE /api/admin/business/{businessId}
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

        DB::beginTransaction();
        try {
            // 🔥 PUNTO 9: Eliminar datos de Firebase ANTES de eliminar de BBDD
            // Esto se hace primero para tener acceso a las relaciones (staff, tables, etc)
            $firebaseCleanup = null;
            try {
                $firebaseService = app(UnifiedFirebaseService::class);
                $firebaseCleanup = $firebaseService->deleteBusinessData($businessId);
                
                Log::info('Firebase cleanup result', [
                    'business_id' => $businessId,
                    'cleanup_result' => $firebaseCleanup
                ]);
            } catch (\Throwable $firebaseError) {
                // ⚠️ NO FALLAR la eliminación de BBDD si Firebase falla
                Log::error('Firebase cleanup failed but continuing with BBDD deletion', [
                    'business_id' => $businessId,
                    'firebase_error' => $firebaseError->getMessage(),
                    'trace' => $firebaseError->getTraceAsString()
                ]);
            }

            // Eliminar dependencias conocidas
            if (Schema::hasTable('tables')) {
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

            if (Schema::hasTable('menus')) {
                Menu::where('business_id', $businessId)->each(function ($menu) {
                    if (!empty($menu->file_path) && Storage::disk('public')->exists($menu->file_path)) {
                        Storage::disk('public')->delete($menu->file_path);
                    }
                    $menu->delete();
                });
            }
            if (Schema::hasTable('qr_codes')) {
                QrCode::where('business_id', $businessId)->delete();
            }
            if (Schema::hasTable('staff')) {
                Staff::where('business_id', $businessId)->delete();
            }
            if (Schema::hasTable('business_admins')) {
                DB::table('business_admins')->where('business_id', $businessId)->delete();
            }
            if (Schema::hasTable('business_waiters')) {
                DB::table('business_waiters')->where('business_id', $businessId)->delete();
            }
            if (Schema::hasTable('user_active_roles')) {
                DB::table('user_active_roles')->where('business_id', $businessId)->delete();
            }

            // Finalmente eliminar el negocio
            $business->delete();

            DB::commit();
            
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
            DB::rollBack();
            Log::error('Error eliminando negocio', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Error interno al eliminar el negocio', 500);
        }
    }

    /**
     * Cambiar vista entre admin y waiter
     * 
     * Endpoint: POST /api/admin/switch-view
     */
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
     * 
     * Endpoint: GET /api/admin/businesses
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
}

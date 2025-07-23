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

class AdminController extends Controller
{
    public function getBusinessInfo(Request $request)
    {
        $user = $request->user();
        
        $business = Business::with(['tables', 'menus', 'qrCodes'])
            ->findOrFail($user->business_id);
        
        return response()->json([
            'business' => $business,
            'tables_count' => $business->tables->count(),
            'menus_count' => $business->menus->count(),
            'qr_codes_count' => $business->qrCodes->count(),
        ]);
    }

    public function switchView(Request $request)
    {
        $request->validate([
            'view' => ['required', Rule::in(['admin', 'waiter'])],
        ]);

        $user = $request->user();
        
        // No hay validación de permisos - cualquier usuario puede cambiar de vista
        
        return response()->json([
            'message' => 'Vista cambiada exitosamente',
            'view' => $request->view,
            'token' => $user->createToken('api-token', ['role:' . $request->view])->plainTextToken,
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
        
        // Si se establece como predeterminado, desmarcar los demás
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
        
        // Verificar que el menú pertenece al negocio
        $menu = Menu::where('id', $request->menu_id)
            ->where('business_id', $user->business_id)
            ->firstOrFail();
        
        // Desmarcar todos los menús como predeterminados
        Menu::where('business_id', $user->business_id)
            ->update(['is_default' => false]);
        
        // Marcar el seleccionado como predeterminado
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
        
        // Verificar que la mesa pertenece al negocio
        $table = Table::where('id', $request->table_id)
            ->where('business_id', $user->business_id)
            ->firstOrFail();
        
        // Generar QR único
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
        
        // Verificar que los códigos QR pertenecen al negocio
        $qrCodes = QrCode::whereIn('id', $request->qr_ids)
            ->where('business_id', $user->business_id)
            ->with('table')
            ->get();
        
        if ($qrCodes->count() !== count($request->qr_ids)) {
            return response()->json([
                'message' => 'Algunos códigos QR no pertenecen a tu negocio',
            ], 403);
        }
        
        // Simulación de exportación (en producción, generar archivos reales)
        return response()->json([
            'message' => 'Códigos QR exportados exitosamente',
            'format' => $request->format,
            'qr_codes' => $qrCodes,
            'download_url' => 'https://example.com/downloads/' . uniqid() . '.' . $request->format,
        ]);
    }

    // ---- Staff Management APIs ----
    
    /**
     * Remove a staff member from the database
     */
    public function removeStaff($staffId)
    {
        $user = Auth::user();
        
        $staff = Staff::where('id', $staffId)
            ->where('business_id', $user->business_id)
            ->firstOrFail();
        
        // Eliminamos el staff
        $staff->delete();
        
        return response()->json([
            'message' => 'Personal eliminado exitosamente',
            'staff_id' => $staffId
        ]);
    }
    
    /**
     * Handle staff request (confirm/reject/archive)
     */
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
                
                // Opcionalmente, crear un usuario para el staff si es necesario
                if ($request->has('create_user') && $request->create_user) {
                    User::create([
                        'name' => $staff->name,
                        'email' => $staff->email,
                        'password' => Hash::make('temporal123'), // Contraseña temporal
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
                // Mover a tabla de archivados
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
                
                // Eliminar el registro original
                $staff->delete();
                
                return response()->json([
                    'message' => 'Solicitud de personal archivada',
                ]);

            case 'archived':
                // Alias: procesar igual que archive
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

                // Eliminar registro original
                $staff->delete();

                return response()->json([
                    'message' => 'Solicitud de personal archivada',
                ]);
        }
    }
    
    /**
     * Fetch pending staff requests
     */
    public function fetchStaffRequests()
    {
        $user = Auth::user();
        
        $pendingRequests = Staff::where('business_id', $user->business_id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'requests' => $pendingRequests,
            'count' => $pendingRequests->count()
        ]);
    }
    
    /**
     * Fetch archived staff requests
     */
    public function fetchArchivedRequests()
    {
        $user = Auth::user();
        
        $archivedRequests = ArchivedStaff::where('business_id', $user->business_id)
            ->orderBy('archived_at', 'desc')
            ->get();
        
        return response()->json([
            'archived_requests' => $archivedRequests,
            'count' => $archivedRequests->count()
        ]);
    }

    // ---- Nuevas APIs de gestión de personal ----

    /**
     * Obtener la lista completa de personal del negocio
     */
    public function getStaff(Request $request)
    {
        $user = $request->user();

        $staff = Staff::where('business_id', $user->business_id)
            ->whereNotIn('status', ['rejected'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'staff' => $staff,
            'count' => $staff->count(),
        ]);
    }

    /**
     * Obtener un miembro del personal concreto
     */
    public function getStaffMember(Request $request, $id)
    {
        $user = $request->user();

        $staff = Staff::with('reviews')
            ->where('id', $id)
            ->where('business_id', $user->business_id)
            ->firstOrFail();

        return response()->json(['staff' => $staff]);
    }

    /**
     * Actualizar la información de un miembro del personal
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

        $user = $request->user();

        $staff = Staff::where('id', $id)
            ->where('business_id', $user->business_id)
            ->firstOrFail();

        // Solo actualizamos los campos presentes en la petición
        $input = $request->only($staff->getFillable());
        // Convertir cadenas vacías a null
        foreach ($input as $key => $value) {
            if ($value === '') {
                $input[$key] = null;
            }
        }

        $staff->fill($input);
        // Procesar avatar (file o base64)
        if ($request->has('avatar')) {
            if ($request->file('avatar')) {
                // Archivo subido normalmente
                $path = $request->file('avatar')->store('avatars/' . $user->business_id, 'public');
            } elseif (Str::startsWith($request->avatar, 'data:image')) {
                // Cadena base64
                $path = $this->storeBase64Image($request->avatar, $user->business_id);
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
     * Invitar nuevo personal al negocio
     */
    public function inviteStaff(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:staff,email',
            'position' => 'required|string|max:255',
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

        $avatarPath = null;
        if ($request->has('avatar')) {
            if ($request->file('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars/' . $user->business_id, 'public');
            } elseif (Str::startsWith($request->avatar, 'data:image')) {
                $avatarPath = $this->storeBase64Image($request->avatar, $user->business_id);
            } else {
                return response()->json(['message' => 'Formato de avatar no soportado'], 422);
            }
        }

        $staff = Staff::create([
            'business_id' => $user->business_id,
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

        // Aquí se podría enviar un correo electrónico real de invitación
        // Mail::to($staff->email)->send(new StaffInvitationMail($staff));

        return response()->json([
            'message' => 'Invitación enviada exitosamente',
            'staff' => $staff,
        ], 201);
    }

    /**
     * Añadir una reseña para un miembro del personal
     */
    public function addReview(Request $request, $staffId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'sometimes|string',
        ]);

        $user = $request->user();

        // Verificar pertenencia del staff
        $staff = Staff::where('id', $staffId)
            ->where('business_id', $user->business_id)
            ->firstOrFail();

        $review = Review::create([
            'business_id' => $user->business_id,
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
     * Eliminar una reseña de un miembro del personal
     */
    public function deleteReview(Request $request, $staffId, $id)
    {
        $user = $request->user();

        // Verificar que el staff pertenece al negocio
        $staff = Staff::where('id', $staffId)
            ->where('business_id', $user->business_id)
            ->firstOrFail();

        $review = Review::where('id', $id)
            ->where('staff_id', $staff->id)
            ->where('business_id', $user->business_id)
            ->firstOrFail();

        $review->delete();

        return response()->json([
            'message' => 'Reseña eliminada exitosamente',
        ]);
    }

    public function getSettings(Request $request)
    {
        $user = $request->user();
        
        $business = Business::findOrFail($user->business_id);
        
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
        
        $business = Business::findOrFail($user->business_id);
        
        // Actualizar campos si están presentes
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
        // data:image/png;base64,AAA...
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
     * Devuelve estadísticas básicas para el dashboard de administrador.
     */
    public function getStatistics(Request $request)
    {
        $user = $request->user();

        $businessId = $user->business_id;

        $tablesCount = Table::where('business_id', $businessId)->count();
        $menusCount = Menu::where('business_id', $businessId)->count();
        $staffCount = Staff::where('business_id', $businessId)->count();
        $pendingRequests = Staff::where('business_id', $businessId)
            ->where('status', 'pending')->count();
        $qrCodesCount = QrCode::where('business_id', $businessId)->count();
        $archivedStaffCount = ArchivedStaff::where('business_id', $businessId)->count();

        return response()->json([
            'tables_count' => $tablesCount,
            'menus_count' => $menusCount,
            'staff_count' => $staffCount,
            'pending_requests_count' => $pendingRequests,
            'qr_codes_count' => $qrCodesCount,
            'archived_staff_count' => $archivedStaffCount,
        ]);
    }

    /**
     * Envía una notificación de prueba a todos los mozos activos del negocio
     */
    public function sendTestNotification(Request $request)
    {
        $user = $request->user();
        
        // No hay validación de permisos - cualquier usuario puede enviar notificaciones de prueba

        // Obtener todos los mozos activos del negocio
        $waiters = User::where('active_business_id', $user->active_business_id)
            ->where('role', 'waiter')
            ->get();

        if ($waiters->isEmpty()) {
            return response()->json([
                'message' => 'No hay mozos activos en este negocio para enviar la notificación de prueba'
            ], 404);
        }

        // Obtener una mesa del negocio para la notificación de prueba
        $table = Table::where('business_id', $user->active_business_id)->first();
        
        if (!$table) {
            return response()->json([
                'message' => 'No hay mesas configuradas en este negocio'
            ], 404);
        }

        $notificationCount = 0;

        // Enviar notificación de prueba a cada mozo
        foreach ($waiters as $waiter) {
            try {
                $waiter->notify(new \App\Notifications\TableCalledNotification($table));
                $notificationCount++;
            } catch (\Exception $e) {
                // Continuar con el siguiente mozo si hay error
                continue;
            }
        }

        return response()->json([
            'message' => "Notificación de prueba enviada exitosamente a {$notificationCount} mozos",
            'waiters_notified' => $notificationCount,
            'total_waiters' => $waiters->count(),
            'test_table' => [
                'id' => $table->id,
                'number' => $table->number
            ]
        ]);
    }
} 
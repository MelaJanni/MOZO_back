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
        
        $pendingRequests = Staff::where('business_id', $user->business_id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'requests' => $pendingRequests,
            'count' => $pendingRequests->count()
        ]);
    }
    
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

    public function getStaffMember(Request $request, $id)
    {
        $user = $request->user();

        $staff = Staff::with('reviews')
            ->where('id', $id)
            ->where('business_id', $user->business_id)
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

        $staff = Staff::where('id', $id)
            ->where('business_id', $user->business_id)
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
                $path = $request->file('avatar')->store('avatars/' . $user->business_id, 'public');
            } elseif (Str::startsWith($request->avatar, 'data:image')) {
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

    public function deleteReview(Request $request, $staffId, $id)
    {
        $user = $request->user();

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

} 
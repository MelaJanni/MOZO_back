<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminProfileController extends Controller
{
    /**
     * Generar link de WhatsApp para contactar a un staff member
     * 
     * Endpoint: GET /api/admin/staff/{userId}/whatsapp
     */
    public function getWhatsAppLink(Request $request, $userId)
    {
        $user = $request->user();
        
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
     * 
     * Endpoint: GET /api/admin/profile
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
     * 
     * Endpoint: POST /api/admin/profile/update
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

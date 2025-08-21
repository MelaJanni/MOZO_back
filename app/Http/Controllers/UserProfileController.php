<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WaiterProfile;
use App\Models\AdminProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    /**
     * 👤 OBTENER PERFIL ACTIVO DEL USUARIO
     */
    public function getActiveProfile(Request $request)
    {
        try {
            $user = Auth::user();
            $businessId = $request->get('business_id') ?: $user->active_business_id;

            if (!$businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay negocio activo seleccionado'
                ], 400);
            }

            $profile = null;

            if ($user->isAdmin()) {
                $profile = $user->adminProfileForBusiness($businessId);
            } elseif ($user->isWaiter()) {
                $profile = $user->waiterProfileForBusiness($businessId);
            }

            if (!$profile) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'No hay perfil configurado para este negocio'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $profile->id,
                    'type' => $user->isAdmin() ? 'admin' : 'waiter',
                    'user_id' => $user->id,
                    'business_id' => $profile->business_id,
                    'avatar' => $profile->avatar,
                    'avatar_url' => $profile->avatar_url,
                    'display_name' => $profile->display_name,
                    'is_complete' => $profile->isComplete(),
                    'profile_data' => $profile->toArray()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📝 ACTUALIZAR PERFIL DE MOZO
     */
    public function updateWaiterProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|exists:businesses,id',
            'display_name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date|before:today',
            'height' => 'nullable|numeric|between:1.0,2.5',
            'weight' => 'nullable|integer|between:30,200',
            'gender' => 'nullable|in:masculino,femenino,otro',
            'experience_years' => 'nullable|integer|between:0,50',
            'employment_type' => 'nullable|in:por horas,tiempo completo,tiempo parcial,solo fines de semana',
            'current_schedule' => 'nullable|in:mañana,tarde,noche,mixto',
            'current_location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'availability_hours' => 'nullable|array',
            'skills' => 'nullable|array',
            'is_available' => 'nullable|boolean',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();

            if (!$user->isWaiter()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los mozos pueden actualizar perfiles de mozo'
                ], 403);
            }

            $data = $request->except(['avatar']);

            // Manejar la subida del avatar
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars/waiters', 'public');
                $data['avatar'] = $avatarPath;
            }

            $profile = $user->waiterProfiles()->updateOrCreate(
                ['business_id' => $request->business_id],
                $data
            );

            return response()->json([
                'success' => true,
                'message' => 'Perfil de mozo actualizado exitosamente',
                'data' => [
                    'id' => $profile->id,
                    'avatar_url' => $profile->avatar_url,
                    'display_name' => $profile->display_name,
                    'is_complete' => $profile->isComplete(),
                    'profile_data' => $profile->fresh()->toArray()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil de mozo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🏢 ACTUALIZAR PERFIL DE ADMINISTRADOR
     */
    public function updateAdminProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|exists:businesses,id',
            'display_name' => 'nullable|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:100',
            'corporate_email' => 'nullable|email|max:255',
            'corporate_phone' => 'nullable|string|max:20',
            'office_extension' => 'nullable|string|max:10',
            'business_description' => 'nullable|string|max:1000',
            'business_website' => 'nullable|url|max:255',
            'social_media' => 'nullable|array',
            'permissions' => 'nullable|array',
            'notify_new_orders' => 'nullable|boolean',
            'notify_staff_requests' => 'nullable|boolean',
            'notify_reviews' => 'nullable|boolean',
            'notify_payments' => 'nullable|boolean',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();

            if (!$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los administradores pueden actualizar perfiles de admin'
                ], 403);
            }

            $data = $request->except(['avatar']);

            // Manejar la subida del avatar
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars/admins', 'public');
                $data['avatar'] = $avatarPath;
            }

            $profile = $user->adminProfiles()->updateOrCreate(
                ['business_id' => $request->business_id],
                $data
            );

            // Actualizar última actividad
            $profile->updateLastActive();

            return response()->json([
                'success' => true,
                'message' => 'Perfil de administrador actualizado exitosamente',
                'data' => [
                    'id' => $profile->id,
                    'avatar_url' => $profile->avatar_url,
                    'display_name' => $profile->display_name,
                    'is_complete' => $profile->isComplete(),
                    'profile_data' => $profile->fresh()->toArray()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil de administrador',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📋 OBTENER TODOS LOS PERFILES DE UN USUARIO
     */
    public function getAllProfiles(Request $request)
    {
        try {
            $user = Auth::user();

            $waiterProfiles = $user->waiterProfiles()->with('business')->get()->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'type' => 'waiter',
                    'business_id' => $profile->business_id,
                    'business_name' => $profile->business->name,
                    'avatar_url' => $profile->avatar_url,
                    'display_name' => $profile->display_name,
                    'is_complete' => $profile->isComplete(),
                    'is_active' => $profile->is_active,
                    'is_available' => $profile->is_available
                ];
            });

            $adminProfiles = $user->adminProfiles()->with('business')->get()->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'type' => 'admin',
                    'business_id' => $profile->business_id,
                    'business_name' => $profile->business->name,
                    'avatar_url' => $profile->avatar_url,
                    'display_name' => $profile->display_name,
                    'is_complete' => $profile->isComplete(),
                    'is_primary_admin' => $profile->is_primary_admin,
                    'position' => $profile->position
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'waiter_profiles' => $waiterProfiles,
                    'admin_profiles' => $adminProfiles,
                    'total_profiles' => $waiterProfiles->count() + $adminProfiles->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los perfiles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🗑️ ELIMINAR AVATAR DEL PERFIL
     */
    public function deleteAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|exists:businesses,id',
            'profile_type' => 'required|in:waiter,admin'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $businessId = $request->business_id;
            $profileType = $request->profile_type;

            if ($profileType === 'waiter' && !$user->isWaiter()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar este avatar'
                ], 403);
            }

            if ($profileType === 'admin' && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar este avatar'
                ], 403);
            }

            $profile = null;
            if ($profileType === 'waiter') {
                $profile = $user->waiterProfileForBusiness($businessId);
            } else {
                $profile = $user->adminProfileForBusiness($businessId);
            }

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil no encontrado'
                ], 404);
            }

            // Eliminar archivo del storage si existe
            if ($profile->avatar && Storage::disk('public')->exists($profile->avatar)) {
                Storage::disk('public')->delete($profile->avatar);
            }

            $profile->update(['avatar' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Avatar eliminado exitosamente',
                'data' => [
                    'avatar_url' => $profile->avatar_url
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el avatar',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
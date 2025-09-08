<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserBusinessController extends Controller
{
    /**
     * Cambia el negocio activo del usuario autenticado (admin o waiter).
     */
    public function setActive(Request $request): JsonResponse
    {
        $request->validate([
            'business_id' => 'required|integer|exists:businesses,id',
        ]);

        $user = $request->user();
        $businessId = (int) $request->business_id;

        try {
            // Verificar membresía como admin activo
            $isAdmin = method_exists($user, 'businessesAsAdmin')
                ? $user->businessesAsAdmin()
                    ->where('business_admins.is_active', true)
                    ->where('business_admins.business_id', $businessId)
                    ->exists()
                : false;

            // Verificar membresía como waiter confirmado
            $isWaiter = Staff::where('user_id', $user->id)
                ->where('business_id', $businessId)
                ->where('status', 'confirmed')
                ->exists();

            if (!$isAdmin && !$isWaiter) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este negocio',
                ], 403);
            }

            // Guardar negocio activo a nivel de usuario
            $user->business_id = $businessId;
            $user->save();

            $business = Business::find($businessId);

            return response()->json([
                'success' => true,
                'message' => 'Negocio activo cambiado exitosamente',
                'active_business' => [
                    'id' => $business->id,
                    'name' => $business->name,
                    'slug' => $business->slug ?? null,
                    'invitation_code' => $business->invitation_code ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error cambiando negocio activo', [
                'user_id' => $user->id,
                'business_id' => $businessId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error cambiando el negocio activo',
            ], 500);
        }
    }
}

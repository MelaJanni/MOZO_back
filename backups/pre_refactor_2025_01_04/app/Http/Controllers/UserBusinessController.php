<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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

            // Guardar negocio activo a nivel de usuario, detectando columna disponible
            $updatedColumn = null;
            if (Schema::hasColumn('users', 'active_business_id')) {
                $user->active_business_id = $businessId;
                $updatedColumn = 'active_business_id';
            } elseif (Schema::hasColumn('users', 'business_id')) {
                $user->business_id = $businessId;
                $updatedColumn = 'business_id';
            }

            if ($updatedColumn) {
                $user->save();
            } else {
                Log::warning('No se encontró columna para negocio activo en users', [
                    'user_id' => $user->id,
                    'business_id' => $businessId,
                ]);
            }

            // Sincronizar también user_active_roles para admins (fuente usada por /api/admin/business)
            try {
                if ($isAdmin && method_exists($user, 'activeRoles')) {
                    $user->activeRoles()->updateOrCreate(
                        ['business_id' => $businessId],
                        [
                            'active_role' => 'admin',
                            'switched_at' => now(),
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo sincronizar user_active_roles en set-active', [
                    'user_id' => $user->id,
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
            }

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
                // Campo auxiliar para FE si necesita saber qué quedó grabado
                'active_business_id' => $businessId,
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

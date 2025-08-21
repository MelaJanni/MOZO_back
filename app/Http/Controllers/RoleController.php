<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    public function selectRole(Request $request)
    {
        $request->validate([
            'role' => 'required|in:admin,waiter',
            'business_id' => 'sometimes|integer|exists:businesses,id',
        ]);

        $user = $request->user();
        $newRole = $request->role;

        // Determinar business_id para fijar el rol activo
        $businessId = $request->integer('business_id');
        if (!$businessId) {
            $adminBiz = $user->businessesAsAdmin()->pluck('business_id');
            $waiterBiz = $user->businessesAsWaiter()->pluck('business_id');
            if ($adminBiz->count() === 1) {
                $businessId = (int)$adminBiz->first();
            } elseif ($waiterBiz->count() === 1) {
                $businessId = (int)$waiterBiz->first();
            }
        }

        if (!$businessId) {
            return response()->json([
                'message' => 'business_id es requerido cuando perteneces a múltiples negocios',
            ], 422);
        }

        // Guardar rol activo por negocio (solo efecto UI)
        $user->activeRoles()->updateOrCreate(
            ['business_id' => $businessId],
            ['active_role' => $newRole, 'switched_at' => now()]
        );

        // Crear nuevo token de acceso con permiso de rol (opcional)
        $tokenResult = $user->createToken('api-token', ['role:' . $newRole]);

        return response()->json([
            'message' => 'Rol seleccionado exitosamente',
            'business_id' => $businessId,
            'active_role' => $newRole,
            'token' => $tokenResult->plainTextToken,
        ]);
    }
} 
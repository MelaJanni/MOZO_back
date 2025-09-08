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
        ]);

        $user = $request->user();
        $newRole = $request->role;

        // Crear nuevo token de acceso con permiso de rol (opcional)
        $tokenResult = $user->createToken('api-token', ['role:' . $newRole]);

        return response()->json([
            'message' => 'Rol seleccionado exitosamente',
            'view' => $newRole,
            'active_role' => $newRole, // compat
            'token' => $tokenResult->plainTextToken,
        ]);
    }
} 
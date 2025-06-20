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

        if ($request->role === 'admin' && !$user->isAdmin()) {
            throw ValidationException::withMessages([
                'role' => ['No tienes permisos para seleccionar el rol de administrador.'],
            ]);
        }

        // Creamos un nuevo token con el claim de rol
        $user->tokens()->delete(); // Eliminamos tokens anteriores
        $token = $user->createToken('api-token', ['role:' . $request->role]);

        return response()->json([
            'message' => 'Rol seleccionado exitosamente',
            'token' => $token->plainTextToken,
        ]);
    }
} 
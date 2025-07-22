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

        // Eliminamos la validación de permisos - cualquier usuario puede seleccionar cualquier rol
        // ya que solo cambia la vista del dashboard

        // Creamos un nuevo token con el claim de rol
        $user->tokens()->delete(); // Eliminamos tokens anteriores
        $token = $user->createToken('api-token', ['role:' . $request->role]);

        return response()->json([
            'message' => 'Rol seleccionado exitosamente',
            'token' => $token->plainTextToken,
        ]);
    }
} 
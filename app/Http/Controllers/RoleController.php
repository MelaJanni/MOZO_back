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


        $user->tokens()->delete();
        $token = $user->createToken('api-token', ['role:' . $request->role]);

        return response()->json([
            'message' => 'Rol seleccionado exitosamente',
            'token' => $token->plainTextToken,
        ]);
    }
} 
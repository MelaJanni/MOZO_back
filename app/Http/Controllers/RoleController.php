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
        $oldRole = $user->role;

        // Actualizar rol en usuario
        $user->role = $newRole;
        $user->save();

        // Si el usuario deja de ser mozo -> desactivar mesas asignadas y borrar device tokens
        if ($oldRole === 'waiter' && $newRole !== 'waiter') {
            // Desactivar mesas igual que en logout
            try {
                $deactivatedTables = \App\Models\Table::where('active_waiter_id', $user->id)
                    ->whereNotNull('active_waiter_id')
                    ->get();

                foreach ($deactivatedTables as $table) {
                    try { $table->pendingCalls()->update(['status' => 'cancelled']); } catch (\Exception $e) { }
                    try { if (method_exists($table, 'unassignWaiter')) { $table->unassignWaiter(); } else { $table->active_waiter_id = null; $table->save(); } } catch (\Exception $e) { }
                }
                \Log::info('Role change: deactivated ' . $deactivatedTables->count() . ' tables for user ' . $user->id);
            } catch (\Exception $e) {
                \Log::warning('Role change: error deactivating tables for user ' . $user->id . ': ' . $e->getMessage());
            }

            // Eliminar device tokens para que no reciba notificaciones como mozo
            try {
                $count = $user->deviceTokens()->count();
                if ($count > 0) {
                    $user->deviceTokens()->delete();
                    \Log::info("Role change: deleted {$count} device tokens for user {$user->id}");
                }
            } catch (\Exception $e) {
                \Log::warning('Role change: could not delete device tokens for user ' . $user->id . ': ' . $e->getMessage());
            }
        }

        // Crear nuevo token de acceso con permiso de rol
        $tokenResult = $user->createToken('api-token', ['role:' . $newRole]);

        return response()->json([
            'message' => 'Rol seleccionado exitosamente',
            'token' => $tokenResult->plainTextToken,
        ]);
    }
} 
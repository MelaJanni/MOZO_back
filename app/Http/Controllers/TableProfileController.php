<?php

namespace App\Http\Controllers;

use App\Models\TableProfile;
use App\Models\Table;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class TableProfileController extends Controller
{
    // ✨ Método eliminado: EnsureActiveBusiness middleware ya maneja la lógica de business_id

    public function index(Request $request): JsonResponse
    {
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $businessId = $request->business_id;

        // Parámetro opcional: include=tables o with_tables=1
        $include = $request->query('include');
        $withTables = $request->boolean('with_tables');
        if ($include) {
            $withTables = $withTables || collect(explode(',', $include))
                ->map(fn ($s) => trim($s))
                ->contains('tables');
        }

        $query = TableProfile::withCount('tables')
            ->where('business_id', $businessId)
            ->orderBy('name');
        if ($withTables) {
            $query->with('tables:id,number,name');
        }
        $profiles = $query->get();
        return response()->json(['success' => true, 'profiles' => $profiles]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'table_ids' => 'sometimes|array',
            'table_ids.*' => 'integer|exists:tables,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $user = Auth::user();
        $businessId = $this->ensureBusinessId($user);
        if (!$businessId) {
            return response()->json(['success' => false, 'message' => 'No hay negocio activo'], 400);
        }

        // Verificar staff confirmado
        $isConfirmedStaff = Staff::where('user_id', $user->id)->where('business_id', $businessId)->where('status', 'confirmed')->exists();
        if (!$isConfirmedStaff) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $profile = TableProfile::create([
            'business_id' => $businessId,
            'user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);
        if ($request->filled('table_ids')) {
            $tables = Table::whereIn('id', $request->table_ids)->where('business_id', $businessId)->pluck('id');
            $profile->tables()->sync($tables);
        }
        return response()->json(['success' => true, 'profile' => $profile->loadCount('tables')->load('tables:id,number')], 201);
    }

    public function update(Request $request, TableProfile $profile): JsonResponse
    {
        $user = Auth::user();
        $businessId = $this->ensureBusinessId($user);
        if ($profile->business_id !== $businessId) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'table_ids' => 'sometimes|array',
            'table_ids.*' => 'integer|exists:tables,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        if ($request->filled('name')) $profile->name = $request->name;
        if ($request->has('description')) $profile->description = $request->description;
        $profile->save();
        if ($request->filled('table_ids')) {
            $tables = Table::whereIn('id', $request->table_ids)->where('business_id', $businessId)->pluck('id');
            $profile->tables()->sync($tables);
        }
        return response()->json(['success' => true, 'profile' => $profile->loadCount('tables')->load('tables:id,number')]);
    }

    public function destroy(TableProfile $profile): JsonResponse
    {
        $user = Auth::user();
        $businessId = $this->ensureBusinessId($user);
        if ($profile->business_id !== $businessId) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }
        $profile->delete();
        return response()->json(['success' => true, 'message' => 'Perfil eliminado']);
    }

    public function show(TableProfile $profile): JsonResponse
    {
        $user = Auth::user();
        $businessId = $this->ensureBusinessId($user);
        if ($profile->business_id !== $businessId) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }
        $profile->load('tables:id,number,name');
        return response()->json([
            'success' => true,
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'description' => $profile->description,
                'tables' => $profile->tables->map(fn($t) => [
                    'id' => $t->id,
                    'number' => $t->number,
                    'name' => $t->name,
                ])->values(),
            ]
        ]);
    }

    public function activate(TableProfile $profile): JsonResponse
    {
        $user = Auth::user();
        $businessId = $this->ensureBusinessId($user);
        if ($profile->business_id !== $businessId) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }
        $tables = $profile->tables()->where('business_id', $businessId)->get();
        $results = [];
        foreach ($tables as $table) {
            if ($table->active_waiter_id) {
                $results[] = ['table_id' => $table->id, 'table_number' => $table->number, 'status' => 'skipped_assigned'];
                continue;
            }
            $update = ['active_waiter_id' => $user->id];
            if (Schema::hasColumn('tables', 'waiter_assigned_at')) {
                $update['waiter_assigned_at'] = now();
            }
            $table->update($update);
            $results[] = ['table_id' => $table->id, 'table_number' => $table->number, 'status' => 'activated'];
        }
        return response()->json(['success' => true, 'profile_id' => $profile->id, 'activated' => $results]);
    }

    public function deactivate(TableProfile $profile): JsonResponse
    {
        $user = Auth::user();
        $businessId = $this->ensureBusinessId($user);
        if ($profile->business_id !== $businessId) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }
        $tables = $profile->tables()->where('business_id', $businessId)->where('active_waiter_id', $user->id)->get();
        $results = [];
        foreach ($tables as $table) {
            $update = ['active_waiter_id' => null];
            if (Schema::hasColumn('tables', 'waiter_assigned_at')) {
                $update['waiter_assigned_at'] = null;
            }
            $table->update($update);
            $results[] = ['table_id' => $table->id, 'table_number' => $table->number, 'status' => 'deactivated'];
        }
        return response()->json(['success' => true, 'profile_id' => $profile->id, 'deactivated' => $results]);
    }
}
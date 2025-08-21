<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\Concerns\ResolvesActiveBusiness;

class TableController extends Controller
{
    use ResolvesActiveBusiness;
    public function index(Request $request)
    {
        $businessId = $request->query('business_id');
        
        if ($businessId) {
            $tables = Table::where('business_id', $businessId)->get();
        } else {
            $tables = Table::all();
        }
        
        return response()->json($tables);
    }

    public function store(Request $request)
    {
        $request->validate([
            'number' => 'required|integer|min:1',
            'business_id' => 'required|exists:businesses,id',
            'notifications_enabled' => 'boolean',
        ]);

        $exists = Table::where('business_id', $request->business_id)
                    ->where('number', $request->number)
                    ->exists();
        
        if ($exists) {
            return response()->json(['message' => 'Este número de mesa ya existe para este negocio'], 422);
        }

        $table = Table::create([
            'number' => $request->number,
            'business_id' => $request->business_id,
            'notifications_enabled' => $request->notifications_enabled ?? false,
        ]);

        return response()->json($table, 201);
    }

    public function show(Table $table)
    {
        return response()->json($table);
    }

    public function update(Request $request, Table $table)
    {
        $request->validate([
            'number' => 'integer|min:1',
            'business_id' => 'exists:businesses,id',
            'notifications_enabled' => 'boolean',
        ]);

        if ($request->has('number') && $request->number != $table->number) {
            $exists = Table::where('business_id', $table->business_id)
                        ->where('number', $request->number)
                        ->exists();
            
            if ($exists) {
                return response()->json(['message' => 'Este número de mesa ya existe para este negocio'], 422);
            }
        }

        $table->update($request->only(['number', 'business_id', 'notifications_enabled']));

        return response()->json($table);
    }

    public function destroy(Table $table)
    {
        $table->delete();
        return response()->json(null, 204);
    }

    public function fetchTables()
    {
    $user = Auth::user();
    $activeBusinessId = $this->activeBusinessId($user, 'admin');
    $tables = Table::where('business_id', $activeBusinessId)
            ->with('qrCode')
            ->orderBy('number', 'asc')
            ->get();
        
        return response()->json([
            'tables' => $tables,
            'count' => $tables->count()
        ]);
    }
    
    public function createTable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|integer|min:1',
            'capacity' => 'sometimes|integer|min:1',
            'location' => 'sometimes|string|max:50',
            'status' => 'sometimes|string|in:available,occupied,reserved,maintenance',
            'notifications_enabled' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
    $user = Auth::user();
    $activeBusinessId = $this->activeBusinessId($user, 'admin');
    $existingTable = Table::where('business_id', $activeBusinessId)
            ->where('number', $request->number)
            ->first();
            
        if ($existingTable) {
            return response()->json([
                'message' => 'Ya existe una mesa con ese número en tu negocio'
            ], 422);
        }
        
        $table = Table::create([
            'business_id' => $activeBusinessId,
            'number' => $request->number,
            'capacity' => $request->capacity ?? 4,
            'location' => $request->location ?? 'General',
            'status' => $request->status ?? 'available',
            'notifications_enabled' => $request->notifications_enabled ?? true,
        ]);

        $table->load('qrCode');
        
        return response()->json([
            'message' => 'Mesa creada exitosamente',
            'table' => $table
        ], 201);
    }
    
    public function updateTable(Request $request, $tableId)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'sometimes|integer|min:1',
            'capacity' => 'sometimes|integer|min:1',
            'location' => 'sometimes|string|max:50',
            'status' => 'sometimes|string|in:available,occupied,reserved,maintenance',
            'notifications_enabled' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        $activeBusinessId = $this->activeBusinessId($user, 'admin');
        $table = Table::where('id', $tableId)
            ->where('business_id', $activeBusinessId)
            ->firstOrFail();
        
        if ($request->has('number') && $request->number != $table->number) {
            $existingTable = Table::where('business_id', $activeBusinessId)
                ->where('number', $request->number)
                ->where('id', '!=', $tableId)
                ->first();
                
            if ($existingTable) {
                return response()->json([
                    'message' => 'Ya existe otra mesa con ese número en tu negocio'
                ], 422);
            }
            
            $table->number = $request->number;
        }
        
        if ($request->has('capacity')) {
            $table->capacity = $request->capacity;
        }
        
        if ($request->has('location')) {
            $table->location = $request->location;
        }
        
        if ($request->has('status')) {
            $table->status = $request->status;
        }
        
        if ($request->has('notifications_enabled')) {
            $table->notifications_enabled = $request->notifications_enabled;
        }
        
        $table->save();
        
        return response()->json([
            'message' => 'Mesa actualizada exitosamente',
            'table' => $table
        ]);
    }
    
    public function deleteTable($tableId)
    {
        $user = Auth::user();
        $activeBusinessId = $this->activeBusinessId($user, 'admin');
        $table = Table::where('id', $tableId)
            ->where('business_id', $activeBusinessId)
            ->firstOrFail();
        
        if ($table->qrCodes()->count() > 0) {
            $table->qrCodes()->delete();
        }
        
        $table->delete();
        
        return response()->json([
            'message' => 'Mesa eliminada exitosamente',
            'table_id' => $tableId
        ]);
    }

    public function cloneTable(Request $request, $tableId)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        $sourceTable = Table::where('id', $tableId)
            ->where('business_id', $user->business_id)
            ->firstOrFail();

        if (Table::where('business_id', $user->business_id)->where('number', $request->number)->exists()) {
            return response()->json(['message' => 'Ya existe una mesa con ese número'], 422);
        }

        $newTable = Table::create([
            'business_id' => $user->business_id,
            'number' => $request->number,
            'capacity' => $request->capacity ?? $sourceTable->capacity,
            'location' => $request->location ?? $sourceTable->location,
            'status' => $request->status ?? $sourceTable->status,
            'notifications_enabled' => $request->notifications_enabled ?? $sourceTable->notifications_enabled,
        ]);

    // Sistema legacy de profiles eliminado: no se copian asignaciones de perfiles

        return response()->json([
            'message' => 'Mesa clonada exitosamente',
            'table' => $newTable,
        ], 201);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\QrCodeController;

class TableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'number' => 'required|integer|min:1',
            'business_id' => 'required|exists:businesses,id',
            'notifications_enabled' => 'boolean',
        ]);

        // Verificar que el número de mesa sea único para este negocio
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

    /**
     * Display the specified resource.
     */
    public function show(Table $table)
    {
        return response()->json($table);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Table $table)
    {
        $request->validate([
            'number' => 'integer|min:1',
            'business_id' => 'exists:businesses,id',
            'notifications_enabled' => 'boolean',
        ]);

        // Si cambia el número, verificar que sea único
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Table $table)
    {
        $table->delete();
        return response()->json(null, 204);
    }

    /**
     * Fetch all tables for the business
     */
    public function fetchTables()
    {
        $user = Auth::user();
        
        $tables = Table::where('business_id', $user->business_id)
            ->with('qrCode')
            ->orderBy('number', 'asc')
            ->get();
        
        return response()->json([
            'tables' => $tables,
            'count' => $tables->count()
        ]);
    }
    
    /**
     * Create a new table
     */
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
        
        // Verificar que no exista otra mesa con el mismo número en el negocio
        $existingTable = Table::where('business_id', $user->business_id)
            ->where('number', $request->number)
            ->first();
            
        if ($existingTable) {
            return response()->json([
                'message' => 'Ya existe una mesa con ese número en tu negocio'
            ], 422);
        }
        
        $table = Table::create([
            'business_id' => $user->business_id,
            'number' => $request->number,
            'capacity' => $request->capacity ?? 4,
            'location' => $request->location ?? 'General',
            'status' => $request->status ?? 'available',
            'notifications_enabled' => $request->notifications_enabled ?? true,
        ]);

        // El QR se genera automáticamente a través del TableObserver.
        // Lo cargamos para devolverlo en la respuesta.
        $table->load('qrCode');
        
        return response()->json([
            'message' => 'Mesa creada exitosamente',
            'table' => $table
        ], 201);
    }
    
    /**
     * Update an existing table
     */
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
        
        $table = Table::where('id', $tableId)
            ->where('business_id', $user->business_id)
            ->firstOrFail();
        
        // Si se está cambiando el número, verificar que no exista otra mesa con ese número
        if ($request->has('number') && $request->number != $table->number) {
            $existingTable = Table::where('business_id', $user->business_id)
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
        
        // Actualizar los demás campos si están presentes
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
    
    /**
     * Delete a table
     */
    public function deleteTable($tableId)
    {
        $user = Auth::user();
        
        $table = Table::where('id', $tableId)
            ->where('business_id', $user->business_id)
            ->firstOrFail();
        
        // Verificar si la mesa tiene asociaciones antes de eliminar
        if ($table->qrCodes()->count() > 0) {
            // Eliminamos también los códigos QR asociados
            $table->qrCodes()->delete();
        }
        
        // Eliminar las asociaciones con perfiles
        $table->profiles()->detach();
        
        // Eliminar la mesa
        $table->delete();
        
        return response()->json([
            'message' => 'Mesa eliminada exitosamente',
            'table_id' => $tableId
        ]);
    }

    /**
     * Clonar la configuración de una mesa existente
     * Recibe en el body "number" (requerido) y opcionalmente otros campos para sobrescribir.
     */
    public function cloneTable(Request $request, $tableId)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Mesa a clonar
        $sourceTable = Table::where('id', $tableId)
            ->where('business_id', $user->business_id)
            ->firstOrFail();

        // Verificar que el nuevo número no exista
        if (Table::where('business_id', $user->business_id)->where('number', $request->number)->exists()) {
            return response()->json(['message' => 'Ya existe una mesa con ese número'], 422);
        }

        // Crear la nueva mesa copiando los campos configurables
        $newTable = Table::create([
            'business_id' => $user->business_id,
            'number' => $request->number,
            'capacity' => $request->capacity ?? $sourceTable->capacity,
            'location' => $request->location ?? $sourceTable->location,
            'status' => $request->status ?? $sourceTable->status,
            'notifications_enabled' => $request->notifications_enabled ?? $sourceTable->notifications_enabled,
        ]);

        // Clonar perfiles asociados si existen
        if ($sourceTable->profiles()->count() > 0) {
            $newTable->profiles()->sync($sourceTable->profiles->pluck('id')->toArray());
        }

        return response()->json([
            'message' => 'Mesa clonada exitosamente',
            'table' => $newTable,
        ], 201);
    }
}

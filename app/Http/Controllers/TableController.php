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
use App\Services\QrCodeService;
use Illuminate\Validation\Rule;

class TableController extends Controller
{
    use ResolvesActiveBusiness;
    
    protected QrCodeService $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }
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
            'name' => 'sometimes|string|max:100',
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
            'name' => $request->name ?? null,
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
            'name' => 'sometimes|string|max:100',
            'number' => 'integer|min:1',
            'business_id' => 'exists:businesses,id',
            'notifications_enabled' => 'boolean',
            'status' => 'sometimes|string|in:available,occupied,reserved,maintenance,out_of_service',
        ]);

        if ($request->has('number') && $request->number != $table->number) {
            $exists = Table::where('business_id', $table->business_id)
                        ->where('number', $request->number)
                        ->exists();
            
            if ($exists) {
                return response()->json(['message' => 'Este número de mesa ya existe para este negocio'], 422);
            }
        }

        // Handle status synonym mapping
        $data = $request->only(['name', 'number', 'business_id', 'notifications_enabled', 'status']);
        if (isset($data['status']) && $data['status'] === 'maintenance') {
            $data['status'] = 'out_of_service';
        }
        $table->update($data);

        return response()->json($table);
    }

    public function destroy(Table $table)
    {
        $table->delete();
        return response()->json(null, 204);
    }

    public function fetchTables(Request $request)
    {
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        if (!\Schema::hasTable('tables')) {
            return response()->json([
                'tables' => [],
                'count' => 0,
                'warning' => 'Tabla tables no encontrada. Aplique migraciones.'
            ]);
        }
        $tables = Table::where('business_id', $request->business_id)
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
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('tables', 'number')->where(function ($q) use ($request) {
                    return $q->where('business_id', $request->business_id);
                }),
            ],
            'capacity' => 'sometimes|integer|min:1',
            'location' => 'sometimes|string|max:50',
            'status' => 'sometimes|string|in:available,occupied,reserved,maintenance,out_of_service',
            'notifications_enabled' => 'sometimes|boolean',
        ], [
            'number.unique' => 'Ya existe una mesa con ese número en tu negocio',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $table = Table::create([
            'business_id' => $request->business_id,
            'name' => $request->name ?? null,
            'number' => $request->number,
            'capacity' => $request->capacity ?? 4,
            'location' => $request->location ?? 'General',
            'status' => ($request->status ?? 'available') === 'maintenance' ? 'out_of_service' : ($request->status ?? 'available'),
            'notifications_enabled' => $request->notifications_enabled ?? true,
        ]);

        // Generar QR automáticamente
        try {
            $qrCode = $this->qrCodeService->generateForTable($table);
            $table->load('qrCode');
        } catch (\Exception $e) {
            // Log error but don't fail table creation
            \Log::warning('Failed to generate QR code for table', [
                'table_id' => $table->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return response()->json([
            'message' => 'Mesa creada exitosamente',
            'table' => $table,
            'qr_generated' => isset($qrCode)
        ], 201);
    }
    
    public function updateTable(Request $request, $tableId)
    {
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $table = Table::where('id', $tableId)
            ->where('business_id', $request->business_id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'number' => [
                'sometimes',
                'integer',
                'min:1',
                Rule::unique('tables', 'number')
                    ->where(fn($q) => $q->where('business_id', $request->business_id))
                    ->ignore($tableId),
            ],
            'capacity' => 'sometimes|integer|min:1',
            'location' => 'sometimes|string|max:50',
            'status' => 'sometimes|string|in:available,occupied,reserved,maintenance,out_of_service',
            'notifications_enabled' => 'sometimes|boolean',
        ], [
            'number.unique' => 'Ya existe otra mesa con ese número en tu negocio',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('number')) {
            $table->number = $request->number;
        }
        
        if ($request->has('capacity')) {
            $table->capacity = $request->capacity;
        }
        
        if ($request->has('name')) {
            $table->name = $request->name;
        }

        if ($request->has('location')) {
            $table->location = $request->location;
        }
        
        if ($request->has('status')) {
            $table->status = $request->status === 'maintenance' ? 'out_of_service' : $request->status;
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
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $table = Table::where('id', $tableId)
            ->where('business_id', $request->business_id)
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

        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $sourceTable = Table::where('id', $tableId)
            ->where('business_id', $request->business_id)
            ->firstOrFail();

        if (Table::where('business_id', $request->business_id)->where('number', $request->number)->exists()) {
            return response()->json(['message' => 'Ya existe una mesa con ese número'], 422);
        }

        $newTable = Table::create([
            'business_id' => $request->business_id,
            'number' => $request->number,
            'capacity' => $request->capacity ?? $sourceTable->capacity,
            'location' => $request->location ?? $sourceTable->location,
            'status' => $request->status ?? $sourceTable->status,
            'notifications_enabled' => $request->notifications_enabled ?? $sourceTable->notifications_enabled,
        ]);

        // Generar QR automáticamente para la mesa clonada
        try {
            $qrCode = $this->qrCodeService->generateForTable($newTable);
            $newTable->load('qrCode');
        } catch (\Exception $e) {
            \Log::warning('Failed to generate QR code for cloned table', [
                'table_id' => $newTable->id,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'message' => 'Mesa clonada exitosamente',
            'table' => $newTable,
            'qr_generated' => isset($qrCode)
        ], 201);
    }
}

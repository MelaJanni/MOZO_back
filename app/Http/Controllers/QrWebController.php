<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Business;
use App\Models\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\BusinessSlugAlias;

class QrWebController extends Controller
{
    public function showTablePage(Request $request, $restaurantSlug, $tableCode)
    {
        // Debug: Log what we're looking for
        \Log::info('QR Page Request', [
            'restaurantSlug' => $restaurantSlug,
            'tableCode' => $tableCode,
            'client_ip' => $request->ip()
        ]);

        // Normalizar el slug recibido y buscar por múltiples variantes (slug/name/code)
        $needle = Str::slug($restaurantSlug);
        $matchType = null; // 'slug' | 'name' | 'code' | 'no-spaces' | 'alias'
        $business = Business::all()->first(function($b) use ($restaurantSlug, $needle, &$matchType) {
            // 1) Coincidencia por columna slug (normalizada)
            if (!empty($b->slug) && Str::slug($b->slug) === $needle) { $matchType = 'slug'; return true; }
            // 2) Coincidencia por nombre del negocio normalizado
            if (!empty($b->name) && Str::slug($b->name) === $needle) { $matchType = 'name'; return true; }
            // 3) Coincidencia por código del negocio (texto plano)
            if (!empty($b->code) && strtolower($b->code) === strtolower($restaurantSlug)) { $matchType = 'code'; return true; }
            // 4) Coincidencia por nombre sin espacios/guiones (fallback legacy)
            $nameNoSpaces = strtolower(str_replace([' ', '-'], '', (string)$b->name));
            $slugNoSpaces = strtolower(str_replace([' ', '-'], '', (string)$restaurantSlug));
            if ($nameNoSpaces !== '' && $nameNoSpaces === $slugNoSpaces) { $matchType = 'no-spaces'; return true; }
            return false;
        });

        // 5) Resolver por alias histórico si no se encontró directo (solo si existe la tabla)
        if (!$business && Schema::hasTable('business_slug_aliases')) {
            $alias = BusinessSlugAlias::where('slug', $needle)->first();
            if ($alias) { $business = $alias->business; $matchType = 'alias'; }
        }
        
        \Log::info('Business Search Result', [
            'found' => $business ? 'yes' : 'no',
            'business_id' => $business ? $business->id : null,
            'business_name' => $business ? $business->name : null
        ]);
        
        if (!$business) {
            // Debug: Show available businesses
            $availableBusinesses = Business::select('id', 'name')->get();
            \Log::error('Business not found', [
                'searched_for' => $restaurantSlug,
                'available_businesses' => $availableBusinesses->toArray()
            ]);
            // 🔎 Fallback extra: deducir negocio por el código de mesa o por registro QR
            $tableProbe = Table::where('code', $tableCode)->first();
            if ($tableProbe) {
                $business = Business::find($tableProbe->business_id);
                $matchType = 'tableCode';
            } else if (\Schema::hasTable('qr_codes')) {
                $qrProbe = \App\Models\QrCode::where('code', $tableCode)->first();
                if ($qrProbe) {
                    $business = Business::find($qrProbe->business_id);
                    $matchType = 'qr';
                }
            }
            if (!$business) {
                abort(404, 'Business not found: ' . $restaurantSlug);
            }
        }

        // Si encontramos el negocio por alias u otra variante, redirigir a la URL canónica con el slug actual
        $canonicalSlug = Str::slug($business->name);
        if ($canonicalSlug !== $needle) {
            $qs = $request->getQueryString();
            $target = route('qr.table.page', ['restaurantSlug' => $canonicalSlug, 'tableCode' => $tableCode]);
            if ($qs) { $target .= '?' . $qs; }
            return redirect()->to($target, 302);
        }

        // Buscar mesa por código
        $table = Table::where('code', $tableCode)
                     ->where('business_id', $business->id)
                     ->first();
        
        \Log::info('Table Search Result', [
            'found' => $table ? 'yes' : 'no',
            'table_id' => $table ? $table->id : null,
            'table_number' => $table ? $table->number : null
        ]);
        
        if (!$table) {
            // Debug: Show available tables for this business
            $availableTables = Table::where('business_id', $business->id)
                                   ->select('id', 'number', 'code', 'name')
                                   ->get();
            \Log::error('Table not found', [
                'searched_for' => $tableCode,
                'business_id' => $business->id,
                'business_name' => $business->name,
                'available_tables' => $availableTables->toArray()
            ]);
            abort(404, 'Table not found: ' . $tableCode . ' for business: ' . $business->name);
        }

        // Obtener URL del frontend desde configuración
        $frontendUrl = config('app.frontend_url', 'https://mozoqr.com');
        
        // 🚀 OBTENER MENÚ PREDETERMINADO DEL BUSINESS
        $defaultMenu = \App\Models\Menu::where('business_id', $business->id)
            ->where('is_default', true)
            ->first();
            
        // Si no hay menú predeterminado, obtener el primero disponible
        if (!$defaultMenu) {
            $defaultMenu = \App\Models\Menu::where('business_id', $business->id)
                ->orderBy('created_at', 'desc')
                ->first();
        }
        
        \Log::info('Menu search for QR', [
            'business_id' => $business->id,
            'business_name' => $business->name,
            'default_menu_found' => $defaultMenu ? 'yes' : 'no',
            'menu_id' => $defaultMenu ? $defaultMenu->id : null,
            'menu_path' => $defaultMenu ? $defaultMenu->file_path : null
        ]);
        
        // Obtener IP del cliente para mostrar en la página
        $clientIp = $request->ip();
        
        return view('qr.table-page', compact('business', 'table', 'frontendUrl', 'defaultMenu', 'clientIp'));
    }

    public function testQr()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'QR System is working!',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Crear menú de prueba para McDonalds
     */
    public function createTestMenu()
    {
        try {
            $mcdonalds = \App\Models\Business::where('code', 'mcdonalds')
                ->orWhere('name', 'McDonalds')
                ->first();
                
            if (!$mcdonalds) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business McDonalds no encontrado'
                ], 404);
            }

            // Crear un menú de prueba con contenido base64 simple
            $menuContent = '%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj

2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj

3 0 obj
<<
/Type /Page
/Parent 2 0 R
/Resources <<
  /Font <<
    /F1 4 0 R
  >>
>>
/MediaBox [0 0 612 792]
/Contents 5 0 R
>>
endobj

4 0 obj
<<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
endobj

5 0 obj
<<
/Length 44
>>
stream
BT
/F1 24 Tf
100 700 Td
(MENU McDONALDS) Tj
ET
endstream
endobj

xref
0 6
0000000000 65535 f 
0000000010 00000 n 
0000000053 00000 n 
0000000110 00000 n 
0000000271 00000 n 
0000000348 00000 n 
trailer
<<
/Size 6
/Root 1 0 R
>>
startxref
442
%%EOF';

            // Guardar archivo
            $fileName = 'mcdonalds-menu-test.pdf';
            $filePath = 'menus/' . $mcdonalds->id . '/' . $fileName;
            
            \Storage::disk('public')->put($filePath, $menuContent);

            // Crear registro en la tabla Menu
            $menu = \App\Models\Menu::create([
                'business_id' => $mcdonalds->id,
                'name' => 'Menú Principal McDonalds',
                'file_path' => $filePath,
                'is_default' => true,
                'category' => 'Principal',
                'description' => 'Menú principal de prueba para McDonalds',
                'display_order' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Menú de prueba creado para McDonalds',
                'menu' => [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'file_path' => $menu->file_path,
                    'view_url' => asset('storage/' . $menu->file_path),
                    'business_name' => $mcdonalds->name
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creating test menu for McDonalds', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating test menu: ' . $e->getMessage()
            ], 500);
        }
    }

    public function debugData()
    {
        $businesses = Business::all(['id', 'name', 'code', 'invitation_code']);
        $tables = Table::all(['id', 'business_id', 'number', 'code', 'name']);
        
        return response()->json([
            'businesses' => $businesses,
            'tables' => $tables,
            'test_lookup' => [
                'mcdonalds_business' => Business::where('name', 'McDonalds')->first(),
                'table_JoA4vw' => Table::where('code', 'JoA4vw')->first()
            ]
        ]);
    }

    public function setupTestData()
    {
        try {
            $results = [];
            
            // Update existing table ID 1 to have the QR code
            $table = Table::where('id', 1)->where('business_id', 1)->first();
            if ($table) {
                $table->update([
                    'code' => 'JoA4vw',
                    'name' => 'Mesa 1'
                ]);
                $results[] = "✅ Mesa JoA4vw actualizada";
            }

            // Create missing table mDWlbd if it doesn't exist
            $tableMDWlbd = Table::where('code', 'mDWlbd')->first();
            if (!$tableMDWlbd) {
                $mcdonalds = Business::where('code', 'mcdonalds')->first();
                if ($mcdonalds) {
                    $nextNumber = Table::where('business_id', $mcdonalds->id)->max('number') + 1;
                    $tableMDWlbd = Table::create([
                        'business_id' => $mcdonalds->id,
                        'number' => $nextNumber,
                        'code' => 'mDWlbd',
                        'name' => "Mesa {$nextNumber}",
                        'capacity' => 4,
                        'location' => 'Principal',
                        'notifications_enabled' => true,
                    ]);
                    $results[] = "✅ Mesa mDWlbd creada: Mesa #{$nextNumber}";
                }
            } else {
                $results[] = "✅ Mesa mDWlbd ya existe: Mesa #{$tableMDWlbd->number}";
            }

            // 🔥 NUEVA FUNCIONALIDAD: Asignar mozos a las mesas QR
            $mcdonalds = Business::where('code', 'mcdonalds')->first();
            
            // Buscar un mozo disponible
            $availableWaiter = \App\Models\User::where('role', 'waiter')
                ->where('active_business_id', $mcdonalds->id)
                ->first();
                
            // Si no hay mozo, crear uno de prueba
            if (!$availableWaiter) {
                $availableWaiter = \App\Models\User::create([
                    'name' => 'Mozo Test McDonalds',
                    'email' => 'mozo.test@mcdonalds.com',
                    'password' => bcrypt('password123'),
                    'role' => 'waiter',
                    'active_business_id' => $mcdonalds->id,
                    'phone' => '+5491123456789',
                ]);
                
                // Asociar al negocio
                $availableWaiter->businesses()->attach($mcdonalds->id, [
                    'joined_at' => now(),
                    'status' => 'active',
                    'role' => 'waiter'
                ]);
                
                $results[] = "✅ Mozo de prueba creado: {$availableWaiter->name}";
            }
            
            // Asignar mozo a ambas mesas QR si no lo tienen
            $qrTables = ['JoA4vw', 'mDWlbd'];
            foreach ($qrTables as $code) {
                $qrTable = Table::where('code', $code)->first();
                if ($qrTable) {
                    if (!$qrTable->active_waiter_id) {
                        $qrTable->update([
                            'active_waiter_id' => $availableWaiter->id,
                            'waiter_assigned_at' => now(),
                            'notifications_enabled' => true
                        ]);
                        $results[] = "✅ Mozo {$availableWaiter->name} asignado a Mesa {$code}";
                    } else {
                        $currentWaiter = $qrTable->activeWaiter->name ?? 'Desconocido';
                        $results[] = "ℹ️ Mesa {$code} ya tiene mozo: {$currentWaiter}";
                    }
                }
            }

            // Recargar datos actualizados
            $table = $table->fresh();
            $tableMDWlbd = $tableMDWlbd ? $tableMDWlbd->fresh() : Table::where('code', 'mDWlbd')->first();

            return response()->json([
                'status' => 'success',
                'message' => 'Test data updated successfully',
                'results' => $results,
                'business' => $mcdonalds,
                'table' => $table,
                'table_mDWlbd' => $tableMDWlbd,
                'waiter_assignments' => [
                    'JoA4vw' => [
                        'has_waiter' => $table ? (bool)$table->active_waiter_id : false,
                        'waiter_name' => $table && $table->activeWaiter ? $table->activeWaiter->name : null
                    ],
                    'mDWlbd' => [
                        'has_waiter' => $tableMDWlbd ? (bool)$tableMDWlbd->active_waiter_id : false,
                        'waiter_name' => $tableMDWlbd && $tableMDWlbd->activeWaiter ? $tableMDWlbd->activeWaiter->name : null
                    ]
                ],
                'qr_url' => url("/QR/mcdonalds/JoA4vw"),
                'qr_url_mDWlbd' => $tableMDWlbd ? url("/QR/mcdonalds/mDWlbd") : null,
                'debug_lookup' => [
                    'business_by_code' => Business::where('code', 'mcdonalds')->first(),
                    'table_by_code' => Table::where('code', 'JoA4vw')->first(),
                    'table_mDWlbd_by_code' => Table::where('code', 'mDWlbd')->first()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function fixQrIssues()
    {
        try {
            $results = [];
            
            // 1. Verificar que existe el negocio McDonalds
            $mcdonalds = Business::where('name', 'McDonalds')
                ->first();
                
            if (!$mcdonalds) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se encontró el negocio McDonalds'
                ], 404);
            }
            
            $results[] = "✅ Negocio encontrado: {$mcdonalds->name} (ID: {$mcdonalds->id})";
            
            // 2. Crear mesa mDWlbd si no existe
            $tableMDWlbd = Table::where('code', 'mDWlbd')->first();
            if (!$tableMDWlbd) {
                // Buscar el siguiente número de mesa disponible
                $nextNumber = Table::where('business_id', $mcdonalds->id)->max('number') + 1;
                
                $tableMDWlbd = Table::create([
                    'business_id' => $mcdonalds->id,
                    'number' => $nextNumber,
                    'code' => 'mDWlbd',
                    'name' => "Mesa {$nextNumber}",
                    'capacity' => 4,
                    'location' => 'Principal',
                    'notifications_enabled' => true,
                ]);
                
                $results[] = "✅ Mesa mDWlbd creada: Mesa #{$nextNumber} (ID: {$tableMDWlbd->id})";
            } else {
                $results[] = "✅ Mesa mDWlbd ya existe: Mesa #{$tableMDWlbd->number}";
            }
            
            // 3. Verificar mesa JoA4vw
            $tableJoA4vw = Table::where('code', 'JoA4vw')->first();
            if (!$tableJoA4vw) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Mesa JoA4vw no encontrada'
                ], 404);
            }
            
            $results[] = "✅ Mesa JoA4vw encontrada: Mesa #{$tableJoA4vw->number}";
            
            // 4. Verificar/crear mozos de prueba
            $results[] = "🔍 Verificando mozos disponibles...";
            
            $waiters = \App\Models\User::where('role', 'waiter')->get();
            if ($waiters->isEmpty()) {
                $results[] = "⚠️ No hay mozos registrados, creando mozo de prueba...";
                
                // Crear mozo de prueba
                $testWaiter = \App\Models\User::create([
                    'name' => 'Mozo Test McDonalds',
                    'email' => 'mozo.test@mcdonalds.com',
                    'password' => bcrypt('password123'),
                    'role' => 'waiter',
                    'active_business_id' => $mcdonalds->id,
                    'phone' => '+5491123456789',
                ]);
                
                // Asociar el mozo al negocio
                $testWaiter->businesses()->attach($mcdonalds->id, [
                    'joined_at' => now(),
                    'status' => 'active',
                    'role' => 'waiter'
                ]);
                
                $results[] = "✅ Mozo de prueba creado: {$testWaiter->name} (ID: {$testWaiter->id})";
                $waiters = collect([$testWaiter]);
            }
            
            $availableWaiter = $waiters->first();
            
            // 5. Asignar mozo a las mesas si no lo tienen
            $tables = [$tableMDWlbd, $tableJoA4vw];
            
            foreach ($tables as $table) {
                if (!$table->active_waiter_id) {
                    $table->update([
                        'active_waiter_id' => $availableWaiter->id,
                        'waiter_assigned_at' => now(),
                        'notifications_enabled' => true
                    ]);
                    
                    $results[] = "✅ Mozo {$availableWaiter->name} asignado a Mesa {$table->code}";
                } else {
                    $waiterName = $table->activeWaiter->name ?? 'Desconocido';
                    $results[] = "✅ Mesa {$table->code} ya tiene mozo: {$waiterName}";
                }
            }
            
            // Recargar las mesas para obtener datos actualizados
            $tableMDWlbd->load('activeWaiter');
            $tableJoA4vw->load('activeWaiter');
            
            return response()->json([
                'status' => 'success',
                'message' => '🎉 ¡Reparación completada!',
                'results' => $results,
                'summary' => [
                    'business' => $mcdonalds->name,
                    'tables' => [
                        'mDWlbd' => [
                            'number' => $tableMDWlbd->number,
                            'waiter' => $tableMDWlbd->activeWaiter->name ?? 'Sin asignar',
                            'notifications_enabled' => $tableMDWlbd->notifications_enabled
                        ],
                        'JoA4vw' => [
                            'number' => $tableJoA4vw->number,
                            'waiter' => $tableJoA4vw->activeWaiter->name ?? 'Sin asignar',
                            'notifications_enabled' => $tableJoA4vw->notifications_enabled
                        ]
                    ]
                ],
                'test_urls' => [
                    'mDWlbd' => url("/QR/mcdonalds/mDWlbd"),
                    'JoA4vw' => url("/QR/mcdonalds/JoA4vw")
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function assignWaiterToTable($tableCode)
    {
        try {
            // Buscar la mesa por código
            $table = Table::where('code', $tableCode)->first();
            
            if (!$table) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Mesa con código {$tableCode} no encontrada"
                ], 404);
            }

            // Si ya tiene mozo asignado
            if ($table->active_waiter_id) {
                $waiterName = $table->activeWaiter->name ?? 'Desconocido';
                return response()->json([
                    'status' => 'info',
                    'message' => "Mesa {$tableCode} ya tiene mozo asignado",
                    'table' => [
                        'code' => $table->code,
                        'number' => $table->number,
                        'waiter_name' => $waiterName,
                        'assigned_at' => $table->waiter_assigned_at
                    ]
                ]);
            }

            // Buscar un mozo disponible (preferir el que ya está trabajando)
            $availableWaiter = \App\Models\User::where('role', 'waiter')
                ->where('active_business_id', $table->business_id)
                ->first();

            // Si no encuentra uno con ese negocio activo, buscar cualquier mozo
            if (!$availableWaiter) {
                $availableWaiter = \App\Models\User::where('role', 'waiter')
                    ->whereHas('businesses', function($query) use ($table) {
                        $query->where('businesses.id', $table->business_id);
                    })
                    ->first();
            }

            if (!$availableWaiter) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No hay mozos disponibles para este negocio'
                ], 404);
            }

            // Asignar el mozo a la mesa
            $table->update([
                'active_waiter_id' => $availableWaiter->id,
                'waiter_assigned_at' => now(),
                'notifications_enabled' => true
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Mozo {$availableWaiter->name} asignado exitosamente a Mesa {$tableCode}",
                'table' => [
                    'code' => $table->code,
                    'number' => $table->number,
                    'name' => $table->name,
                    'waiter_id' => $availableWaiter->id,
                    'waiter_name' => $availableWaiter->name,
                    'assigned_at' => $table->waiter_assigned_at,
                    'notifications_enabled' => $table->notifications_enabled
                ],
                'test_url' => url("/QR/mcdonalds/{$tableCode}")
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function cleanOrphanTables()
    {
        try {
            $results = [];
            $cleaned = 0;
            $kept = 0;

            // Buscar todas las mesas con mozo asignado
            $assignedTables = Table::whereNotNull('active_waiter_id')->get();

            foreach ($assignedTables as $table) {
                // Verificar si el mozo asignado existe
                $waiterExists = \App\Models\User::where('id', $table->active_waiter_id)->exists();
                
                if (!$waiterExists) {
                    // El mozo no existe, limpiar la asignación
                    $table->update([
                        'active_waiter_id' => null,
                        'waiter_assigned_at' => null
                    ]);
                    
                    $results[] = "🧹 Mesa {$table->number} (ID: {$table->id}) - Mozo ID {$table->active_waiter_id} no existe, limpiada";
                    $cleaned++;
                } else {
                    $waiter = \App\Models\User::find($table->active_waiter_id);
                    $results[] = "✅ Mesa {$table->number} (ID: {$table->id}) - Mozo {$waiter->name} (ID: {$waiter->id}) existe";
                    $kept++;
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "Limpieza completada: {$cleaned} mesas limpiadas, {$kept} mantenidas",
                'summary' => [
                    'total_assigned_tables' => $assignedTables->count(),
                    'cleaned' => $cleaned,
                    'kept' => $kept
                ],
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function forceAssignTable($tableId, $waiterId)
    {
        try {
            $table = Table::find($tableId);
            $waiter = \App\Models\User::find($waiterId);

            if (!$table) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Mesa ID {$tableId} no encontrada"
                ], 404);
            }

            if (!$waiter) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Mozo ID {$waiterId} no encontrado"
                ], 404);
            }

            // Información del estado anterior
            $previousWaiter = $table->active_waiter_id ? 
                \App\Models\User::find($table->active_waiter_id) : null;

            // Forzar la asignación
            $table->update([
                'active_waiter_id' => $waiter->id,
                'waiter_assigned_at' => now(),
                'notifications_enabled' => true
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Mesa {$table->number} reasignada exitosamente a {$waiter->name}",
                'table' => [
                    'id' => $table->id,
                    'number' => $table->number,
                    'name' => $table->name,
                    'previous_waiter' => $previousWaiter ? $previousWaiter->name : 'Sin asignar',
                    'new_waiter' => $waiter->name,
                    'assigned_at' => $table->waiter_assigned_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Handle waiter call from QR page form
     */
    public function callWaiter(Request $request)
    {
        try {
            $request->validate([
                'restaurant_id' => 'required|integer|exists:businesses,id',
                'table_id' => 'required|integer|exists:tables,id',
                'message' => 'required|string|max:255'
            ]);

            // Get the table and business for redirect
            $table = Table::with(['activeWaiter', 'business'])->findOrFail($request->table_id);
            $business = Business::findOrFail($request->restaurant_id);

            // 🛡️ Verificar si la IP está bloqueada SILENCIOSAMENTE
            $clientIp = $request->ip();
            $isBlocked = false;
            try {
                if (Schema::hasTable('ip_blocks')) {
                    $isBlocked = \App\Models\IpBlock::isIpBlocked($clientIp, $business->id);
                } else {
                    Log::notice('Skipping IP block check: ip_blocks table missing');
                }
            } catch (\Throwable $t) {
                Log::warning('IP block check failed, continuing without blocking', [
                    'error' => $t->getMessage()
                ]);
            }
            if ($isBlocked) {
                // Respuesta de "éxito" para no alertar al spammer
                $fakeMessage = 'Mozo llamado exitosamente. Esperando confirmación...';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => $fakeMessage,
                        'notification_id' => fake()->randomNumber(),
                        'blocked' => true, // Para que el frontend no imprima la notificación
                        'blocked_ip' => true, // Solo para debug interno
                        'data' => [
                            'id' => fake()->randomNumber(),
                            'table_number' => $table->number,
                            'waiter_name' => $table->activeWaiter->name ?? 'Mozo',
                            'called_at' => now(),
                            'status' => 'pending',
                            'blocked' => true
                        ]
                    ]);
                }
                return redirect()->back()->with('success', $fakeMessage);
            }

            // Verificar si la mesa tiene notificaciones habilitadas
            if (!$table->notifications_enabled) {
                $errorMessage = 'Las notificaciones están desactivadas para esta mesa.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $errorMessage], 400);
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            // Verificar si la mesa tiene un mozo activo asignado
            if (!$table->active_waiter_id) {
                $errorMessage = "Esta mesa no tiene un mozo asignado actualmente.\nPor favor, llame manualmente al mozo.";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $errorMessage], 422);
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            // Verificar si la mesa está silenciada (si existe la tabla)
            $activeSilence = null;
            try {
                if (Schema::hasTable('table_silences')) {
                    $activeSilence = \App\Models\TableSilence::where('table_id', $table->id)
                        ->active()
                        ->first();
                } else {
                    Log::notice('Skipping table silence check: table_silences table missing');
                }
            } catch (\Throwable $t) {
                Log::warning('Table silence check failed, continuing without silencing', [
                    'error' => $t->getMessage()
                ]);
            }

            if ($activeSilence && $activeSilence->isActive()) {
                $message = 'Solicitud procesada exitosamente.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => true, 'message' => $message]);
                }
                return redirect()->back()->with('success', $message);
            }

            // Normalizar mensaje (evitar vacío o solo espacios)
            $rawMessage = (string)$request->input('message', '');
            $normalizedMessage = trim($rawMessage);
            if ($normalizedMessage === '') {
                $normalizedMessage = 'Llamada desde mesa ' . $table->number;
            }

            // Crear la llamada usando el sistema unificado
            $call = \App\Models\WaiterCall::create([
                'table_id' => $table->id,
                'waiter_id' => $table->active_waiter_id,
                'status' => 'pending',
                'message' => $normalizedMessage,
                'called_at' => now(),
                'metadata' => [
                    'urgency' => 'high', // QR calls are always high priority
                    'source' => 'qr_page',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'referer' => $request->headers->get('referer'),
                    'ajax_call' => $request->expectsJson() || $request->ajax()
                ]
            ]);

            // Usar el servicio unificado de Firebase
            $unifiedService = app(\App\Services\UnifiedFirebaseService::class);
            $firebaseWritten = $unifiedService->writeCall($call, 'created');

            // Notificaciones push: ahora se envían dentro de writeCall() via UnifiedFirebaseService

            // Si es una petición AJAX, responder con JSON
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mozo llamado exitosamente. Esperando confirmación...',
                    'notification_id' => $call->id,
                    'firebase_written' => $firebaseWritten,
                    'data' => [
                        'id' => $call->id,
                        'table_number' => $table->number,
                        'waiter_name' => $table->activeWaiter->name ?? 'Mozo',
                        'called_at' => $call->called_at,
                        'status' => 'pending',
                        // 🛡️ Info de seguridad para debug frontend
                        'client_info' => [
                            'ip_address' => $call->metadata['ip_address'] ?? null,
                            'user_agent' => $call->metadata['user_agent'] ?? null,
                            'source' => $call->metadata['source'] ?? 'qr_page'
                        ]
                    ]
                ]);
            }

            // Si es una petición normal (form submit), redirigir como antes
            return redirect()->back()
                ->with('success', 'Mozo llamado exitosamente. Esperando confirmación...')
                ->with('notification_id', $call->id)
                ->with('firebase_written', $firebaseWritten);

        } catch (\Exception $e) {
            \Log::error('Error calling waiter from QR', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Error al llamar al mozo. Inténtalo nuevamente.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }
            return redirect()->back()->with('error', $errorMessage);
        }
    }
}
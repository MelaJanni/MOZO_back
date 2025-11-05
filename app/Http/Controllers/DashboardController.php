<?php

namespace App\Http\Controllers;

use App\Models\WaiterCall;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Controlador para dashboard y estadísticas del mozo
 * 
 * Responsabilidades:
 * - Dashboard con métricas del mozo
 * - Estado de todas las mesas
 * - Cálculos de eficiencia y rendimiento
 * - Tiempos de respuesta promedio
 */
class DashboardController extends Controller
{
    /**
     * Dashboard completo del mozo con estadísticas
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            $waiter = Auth::user();
            
            // Obtener el business_id activo del mozo
            $businessId = $waiter->active_business_id ?? $waiter->business_id ?? null;
            
            // Si el mozo no tiene business_id asignado, devolver dashboard vacío
            if (!$businessId) {
                return response()->json([
                    'needs_business' => true,
                    'message' => 'No tienes un negocio asignado',
                    'available_businesses' => $waiter->staffMemberships()
                        ->where('status', 'confirmed')
                        ->with('business:id,name,code')
                        ->get()
                        ->map(fn($staff) => [
                            'id' => $staff->business_id,
                            'name' => $staff->business->name,
                            'code' => $staff->business->code
                        ])
                ]);
            }

            $today = Carbon::today();
            
            // Estadísticas de llamadas del día
            $todayCalls = WaiterCall::forWaiter($waiter->id)
                ->whereDate('created_at', $today)
                ->get();
            
            $completedToday = $todayCalls->where('status', 'completed')->count();
            $pendingToday = $todayCalls->where('status', 'pending')->count();
            
            // Estadísticas de última hora
            $lastHour = Carbon::now()->subHour();
            $callsLastHour = WaiterCall::forWaiter($waiter->id)
                ->where('created_at', '>=', $lastHour)
                ->count();
            
            $completedLastHour = WaiterCall::forWaiter($waiter->id)
                ->where('created_at', '>=', $lastHour)
                ->where('status', 'completed')
                ->count();
            
            // Información de mesas
            $assignedTables = Table::where('active_waiter_id', $waiter->id)
                ->where('business_id', $businessId)
                ->get();
            
            $tablesWithPendingCalls = $assignedTables->filter(function($table) {
                return $table->pendingCalls()->exists();
            })->count();
            
            // Mesas silenciadas (si existe la tabla)
            $silencedTables = 0;
            try {
                if (DB::getSchemaBuilder()->hasTable('table_silences')) {
                    $silencedTables = $assignedTables->filter(function($table) {
                        return $table->activeSilence()->exists();
                    })->count();
                }
            } catch (\Exception $e) {
                // Tabla no existe aún, silencedTables = 0
            }
            
            $availableToAssign = Table::whereNull('active_waiter_id')
                ->where('business_id', $businessId)
                ->count();
            
            // Llamadas pendientes (top 10)
            $pendingCalls = WaiterCall::forWaiter($waiter->id)
                ->where('status', 'pending')
                ->with(['table'])
                ->orderBy('called_at', 'asc')
                ->limit(10)
                ->get()
                ->map(function($call) {
                    $waitTime = Carbon::parse($call->called_at)->diffInMinutes(Carbon::now());
                    return [
                        'id' => $call->id,
                        'table' => [
                            'id' => $call->table->id,
                            'number' => $call->table->number,
                            'name' => $call->table->name
                        ],
                        'called_at' => $call->called_at,
                        'wait_time_minutes' => $waitTime,
                        'urgency' => $call->urgency_level ?? 'normal',
                        'is_urgent' => $waitTime > 5
                    ];
                });
            
            // Estado de mesas asignadas
            $assignedTablesStatus = $assignedTables->map(function($table) {
                $pendingCount = $table->pendingCalls()->count();
                $silenceInfo = null;
                
                try {
                    if (DB::getSchemaBuilder()->hasTable('table_silences')) {
                        $activeSilence = $table->activeSilence()->first();
                        if ($activeSilence) {
                            $silenceInfo = [
                                'is_silenced' => true,
                                'reason' => $activeSilence->reason,
                                'remaining_minutes' => Carbon::parse($activeSilence->silenced_until)->diffInMinutes(Carbon::now())
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // Ignorar si tabla no existe
                }
                
                return [
                    'id' => $table->id,
                    'number' => $table->number,
                    'name' => $table->name,
                    'pending_calls' => $pendingCount,
                    'silence_info' => $silenceInfo,
                    'status' => $pendingCount > 0 ? 'has_calls' : 'normal'
                ];
            });
            
            // Métricas de rendimiento
            $avgResponseTime = $this->getAverageResponseTime($waiter->id, $today);
            
            $stats = [
                'total_calls' => $todayCalls->count(),
                'completed_calls' => $completedToday,
                'pending_calls' => $pendingToday
            ];
            
            $efficiencyScore = $this->calculateEfficiencyScore($stats);
            $responseGrade = $this->getResponseGrade($avgResponseTime);
            
            return response()->json([
                'waiter' => [
                    'id' => $waiter->id,
                    'name' => $waiter->name,
                    'business_id' => $businessId
                ],
                'statistics' => [
                    'today' => [
                        'total_calls' => $todayCalls->count(),
                        'completed_calls' => $completedToday,
                        'pending_calls' => $pendingToday,
                        'average_response_time' => $avgResponseTime
                    ],
                    'last_hour' => [
                        'calls_last_hour' => $callsLastHour,
                        'completed_last_hour' => $completedLastHour
                    ],
                    'tables' => [
                        'total_assigned' => $assignedTables->count(),
                        'with_pending_calls' => $tablesWithPendingCalls,
                        'silenced' => $silencedTables,
                        'available_to_assign' => $availableToAssign
                    ]
                ],
                'pending_calls' => $pendingCalls,
                'assigned_tables' => $assignedTablesStatus,
                'performance' => [
                    'efficiency_score' => $efficiencyScore,
                    'response_grade' => $responseGrade,
                    'average_response_time' => $avgResponseTime
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en getDashboard: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener dashboard',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estado de todas las mesas del negocio
     */
    public function getTablesStatus(Request $request): JsonResponse
    {
        try {
            $waiter = Auth::user();
            $businessId = $waiter->active_business_id ?? $waiter->business_id ?? null;
            
            if (!$businessId) {
                return response()->json([
                    'error' => 'No tienes un negocio asignado'
                ], 400);
            }
            
            $includeAvailable = $request->boolean('include_available', false);
            
            // Obtener mesas asignadas al mozo
            $assignedTables = Table::where('active_waiter_id', $waiter->id)
                ->where('business_id', $businessId)
                ->with(['pendingCalls', 'activeSilence', 'business'])
                ->get();
            
            // Opcionalmente incluir mesas disponibles
            $availableTables = collect();
            if ($includeAvailable) {
                $availableTables = Table::whereNull('active_waiter_id')
                    ->where('business_id', $businessId)
                    ->with(['business'])
                    ->get();
            }
            
            $allTables = $assignedTables->merge($availableTables);
            
            // Calcular estado de cada mesa
            $tablesStatus = $allTables->map(function($table) use ($waiter) {
                $isAssignedToMe = $table->active_waiter_id == $waiter->id;
                $pendingCalls = $table->pendingCalls;
                $pendingCount = $pendingCalls->count();
                
                // Información de llamadas
                $latestCall = $pendingCalls->first();
                $callsToday = $table->waiterCalls()
                    ->whereDate('created_at', Carbon::today())
                    ->count();
                
                // Información de silenciamiento
                $isSilenced = false;
                $silenceInfo = null;
                try {
                    if (DB::getSchemaBuilder()->hasTable('table_silences')) {
                        $activeSilence = $table->activeSilence;
                        if ($activeSilence) {
                            $isSilenced = true;
                            $silenceInfo = [
                                'reason' => $activeSilence->reason,
                                'silenced_until' => $activeSilence->silenced_until,
                                'remaining_minutes' => Carbon::parse($activeSilence->silenced_until)->diffInMinutes(Carbon::now()),
                                'silenced_by' => $activeSilence->silenced_by
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // Tabla no existe
                }
                
                // Calcular prioridad
                $priority = $this->calculateTablePriority($table, $pendingCalls);
                
                // Tiempo de asignación
                $hoursAssigned = null;
                if ($table->active_waiter_id && $table->assigned_at) {
                    $hoursAssigned = Carbon::parse($table->assigned_at)->diffInHours(Carbon::now());
                }
                
                return [
                    'table_id' => $table->id,
                    'table_number' => $table->number,
                    'table_name' => $table->name,
                    'status' => [
                        'is_assigned_to_me' => $isAssignedToMe,
                        'assigned_waiter_id' => $table->active_waiter_id,
                        'hours_assigned' => $hoursAssigned
                    ],
                    'calls' => [
                        'pending_count' => $pendingCount,
                        'total_today' => $callsToday,
                        'latest_call' => $latestCall ? [
                            'id' => $latestCall->id,
                            'called_at' => $latestCall->called_at,
                            'minutes_waiting' => Carbon::parse($latestCall->called_at)->diffInMinutes(Carbon::now()),
                            'urgency' => $latestCall->urgency_level ?? 'normal'
                        ] : null
                    ],
                    'silence' => [
                        'is_silenced' => $isSilenced,
                        'silence_info' => $silenceInfo
                    ],
                    'priority' => $priority
                ];
            });
            
            // Ordenar por prioridad (más urgente primero)
            $tablesStatus = $tablesStatus->sortByDesc('priority')->values();
            
            // Resumen
            $summary = [
                'total_assigned' => $assignedTables->count(),
                'with_pending_calls' => $assignedTables->filter(fn($t) => $t->pendingCalls->count() > 0)->count(),
                'silenced' => $assignedTables->filter(fn($t) => $t->activeSilence()->exists())->count(),
                'available' => $includeAvailable ? $availableTables->count() : 0,
                'high_priority' => $tablesStatus->filter(fn($t) => $t['priority'] >= 7)->count()
            ];
            
            return response()->json([
                'tables' => $tablesStatus,
                'summary' => $summary
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en getTablesStatus: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener estado de mesas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcula tiempo de respuesta promedio del mozo (privado)
     */
    private function getAverageResponseTime(int $waiterId, Carbon $date): ?float
    {
        $avgMinutes = WaiterCall::forWaiter($waiterId)
            ->whereDate('created_at', $date)
            ->whereNotNull('acknowledged_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, called_at, acknowledged_at)) as avg_minutes')
            ->value('avg_minutes');
        
        return $avgMinutes ? round($avgMinutes, 1) : null;
    }

    /**
     * Calcula score de eficiencia del mozo (privado)
     */
    private function calculateEfficiencyScore(array $stats): int
    {
        $totalCalls = $stats['total_calls'] ?? 0;
        $completedCalls = $stats['completed_calls'] ?? 0;
        $pendingCalls = $stats['pending_calls'] ?? 0;
        
        if ($totalCalls == 0) return 100;
        
        $completionRate = ($completedCalls / $totalCalls) * 100;
        $penalty = min($pendingCalls * 5, 30);
        
        return max(0, min(100, round($completionRate - $penalty)));
    }

    /**
     * Calcula calificación basada en tiempo de respuesta (privado)
     */
    private function getResponseGrade(?float $avgResponseTime): string
    {
        if ($avgResponseTime === null) return 'N/A';
        
        if ($avgResponseTime <= 2) return 'Excelente';
        if ($avgResponseTime <= 5) return 'Bueno';
        if ($avgResponseTime <= 10) return 'Regular';
        
        return 'Necesita mejorar';
    }

    /**
     * Calcula prioridad de una mesa (privado)
     */
    private function calculateTablePriority(Table $table, $pendingCalls): int
    {
        $pendingCount = $pendingCalls->count();
        
        if ($pendingCount == 0) return 0;
        
        // Calcular minutos de espera del llamado más antiguo
        $oldestCall = $pendingCalls->sortBy('called_at')->first();
        $minutesWaiting = $oldestCall ? Carbon::parse($oldestCall->called_at)->diffInMinutes(Carbon::now()) : 0;
        
        // Nivel de urgencia
        $urgencyLevel = $oldestCall->urgency_level ?? 'normal';
        $urgencyBonus = match($urgencyLevel) {
            'high' => 5,
            'low' => -2,
            default => 0
        };
        
        // Fórmula de prioridad: (llamadas pendientes * 3) + (minutos esperando / 2) + bonus urgencia
        $priority = ($pendingCount * 3) + ($minutesWaiting / 2) + $urgencyBonus;
        
        // Normalizar a escala 0-10
        return max(0, min(10, round($priority)));
    }

    /**
     * Diagnóstico de usuario (debug endpoint)
     * Verifica y auto-corrige business_id si falta
     * 
     * Migrado desde WaiterController en FASE 3.2
     */
    public function diagnoseUser(Request $request): JsonResponse
    {
        $waiter = Auth::user();
        
        // Buscar registros de staff para este usuario
        $staffRecords = Staff::where('user_id', $waiter->id)
            ->with('business')
            ->get();

        // Si tiene registros staff pero no business_id, fijar el primero
        $fixed = false;
        if ($staffRecords->isNotEmpty() && !$waiter->business_id) {
            $firstBusiness = $staffRecords->first()->business;
            $waiter->update(['business_id' => $firstBusiness->id]);
            $fixed = true;
            
            Log::info('Auto-fixed missing business_id', [
                'waiter_id' => $waiter->id,
                'business_id' => $firstBusiness->id,
                'business_name' => $firstBusiness->name
            ]);
        }

        return response()->json([
            'user_id' => $waiter->id,
            'user_name' => $waiter->name,
            'current_business_id' => $waiter->business_id,
            'staff_records' => $staffRecords->map(function($staff) {
                return [
                    'business_id' => $staff->business_id,
                    'business_name' => $staff->business->name,
                    'status' => $staff->status,
                    'position' => $staff->position
                ];
            }),
            'staff_count' => $staffRecords->count(),
            'needs_business_assignment' => $staffRecords->isNotEmpty() && !$waiter->business_id,
            'fixed_automatically' => $fixed
        ]);
    }
}

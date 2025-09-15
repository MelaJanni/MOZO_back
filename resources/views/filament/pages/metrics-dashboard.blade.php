<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Widgets -->
        <div>
            {{ $this->getHeaderWidgets() }}
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Actividad Reciente</h3>
            </div>
            <div class="p-6">
                @php
                    $recentLogs = \App\Models\AuditLog::with(['user', 'auditable'])
                                    ->latest()
                                    ->limit(10)
                                    ->get();
                @endphp

                @if($recentLogs->count() > 0)
                    <div class="space-y-4">
                        @foreach($recentLogs as $log)
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    @if($log->event === 'created')
                                        <div class="w-2 h-2 bg-green-400 rounded-full mt-2"></div>
                                    @elseif($log->event === 'updated')
                                        <div class="w-2 h-2 bg-blue-400 rounded-full mt-2"></div>
                                    @elseif($log->event === 'deleted')
                                        <div class="w-2 h-2 bg-red-400 rounded-full mt-2"></div>
                                    @else
                                        <div class="w-2 h-2 bg-gray-400 rounded-full mt-2"></div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900">
                                        <span class="font-medium">{{ $log->user?->name ?? 'Sistema' }}</span>
                                        {{ $log->event }}
                                        <span class="font-medium">{{ class_basename($log->auditable_type) }}</span>
                                        #{{ $log->auditable_id }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $log->created_at->diffForHumans() }}
                                        @if($log->ip_address)
                                            • IP: {{ $log->ip_address }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">No hay actividad reciente</p>
                @endif
            </div>
        </div>

        <!-- System Health -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Webhook Status -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Estado de Webhooks</h3>
                </div>
                <div class="p-6">
                    @php
                        $webhookStats = \App\Models\WebhookLog::selectRaw('
                            provider,
                            status,
                            COUNT(*) as count
                        ')
                        ->where('created_at', '>=', now()->subDays(7))
                        ->groupBy(['provider', 'status'])
                        ->get()
                        ->groupBy('provider');
                    @endphp

                    @if($webhookStats->count() > 0)
                        <div class="space-y-4">
                            @foreach($webhookStats as $provider => $statuses)
                                <div>
                                    <h4 class="font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $provider) }}</h4>
                                    <div class="mt-2 flex space-x-4">
                                        @foreach($statuses as $status)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($status->status === 'processed') bg-green-100 text-green-800
                                                @elseif($status->status === 'failed') bg-red-100 text-red-800
                                                @else bg-gray-100 text-gray-800
                                                @endif
                                            ">
                                                {{ ucfirst($status->status) }}: {{ $status->count }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">No hay actividad de webhooks en los últimos 7 días</p>
                    @endif
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Métodos de Pago</h3>
                </div>
                <div class="p-6">
                    @php
                        $paymentMethods = \App\Models\Payment::selectRaw('
                            payment_method,
                            COUNT(*) as count,
                            SUM(amount) as total_amount
                        ')
                        ->where('status', 'completed')
                        ->where('created_at', '>=', now()->subDays(30))
                        ->groupBy('payment_method')
                        ->get();
                    @endphp

                    @if($paymentMethods->count() > 0)
                        <div class="space-y-3">
                            @foreach($paymentMethods as $method)
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900 capitalize">
                                            {{ str_replace('_', ' ', $method->payment_method) }}
                                        </p>
                                        <p class="text-sm text-gray-500">{{ $method->count }} transacciones</p>
                                    </div>
                                    <p class="font-medium text-gray-900">
                                        ${{ number_format($method->total_amount, 0, ',', '.') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">No hay pagos completados en los últimos 30 días</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
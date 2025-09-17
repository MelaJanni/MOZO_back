<div class="fi-section-content-ctn rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    @if(isset($error))
        <div class="p-4 text-red-600 dark:text-red-400">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium">Error al cargar pagos:</span>
            </div>
            <p class="mt-1 text-sm">{{ $error }}</p>
        </div>
    @elseif($payments->isEmpty())
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
            <div class="flex flex-col items-center gap-3">
                <svg class="h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <div>
                    <h3 class="font-medium">Sin pagos registrados</h3>
                    <p class="text-sm">Este usuario aún no tiene historial de pagos.</p>
                </div>
            </div>
        </div>
    @else
        <div class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Fecha
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Monto
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Proveedor
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Suscripción
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                ID Transacción
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                        @foreach($payments as $payment)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    <div>
                                        <div class="font-medium">
                                            {{ $payment->paid_at ? $payment->paid_at->format('d/m/Y') : $payment->created_at->format('d/m/Y') }}
                                        </div>
                                        <div class="text-gray-500 dark:text-gray-400 text-xs">
                                            {{ $payment->paid_at ? $payment->paid_at->format('H:i') : $payment->created_at->format('H:i') }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                        ${{ number_format($payment->amount_cents / 100, 2) }}
                                    </div>
                                    <div class="text-gray-500 dark:text-gray-400 text-xs">
                                        {{ $payment->currency ?? 'USD' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @php
                                        $providerColors = [
                                            'mp' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            'paypal' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'stripe' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                            'manual' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                        ];
                                        $providerNames = [
                                            'mp' => 'Mercado Pago',
                                            'paypal' => 'PayPal',
                                            'stripe' => 'Stripe',
                                            'manual' => 'Manual',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $providerColors[$payment->provider] ?? $providerColors['manual'] }}">
                                        {{ $providerNames[$payment->provider] ?? ucfirst($payment->provider) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @php
                                        $statusColors = [
                                            'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            'refunded' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                        ];
                                        $statusNames = [
                                            'paid' => 'Pagado',
                                            'pending' => 'Pendiente',
                                            'failed' => 'Fallido',
                                            'refunded' => 'Reembolsado',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$payment->status] ?? $statusColors['pending'] }}">
                                        {{ $statusNames[$payment->status] ?? ucfirst($payment->status) }}
                                    </span>
                                    @if($payment->failure_reason)
                                        <div class="text-xs text-red-600 dark:text-red-400 mt-1" title="{{ $payment->failure_reason }}">
                                            {{ Str::limit($payment->failure_reason, 30) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    @if($payment->subscription)
                                        <div>
                                            <div class="font-medium">
                                                {{ $payment->subscription->plan->name ?? 'Plan eliminado' }}
                                            </div>
                                            <div class="text-gray-500 dark:text-gray-400 text-xs">
                                                ID: {{ $payment->subscription_id }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 text-xs">Sin suscripción</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($payment->provider_payment_id)
                                        <div class="font-mono text-xs text-gray-600 dark:text-gray-400">
                                            {{ Str::limit($payment->provider_payment_id, 20) }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 text-xs">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($payments->count() >= 10)
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-center text-sm text-gray-500 dark:text-gray-400">
                    Mostrando los últimos 10 pagos. Para ver el historial completo, visita la sección de Pagos.
                </div>
            @endif
        </div>
    @endif
</div>
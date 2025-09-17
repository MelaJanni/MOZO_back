<div class="fi-section-content-ctn rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    @if(isset($error))
        <div class="p-4 text-red-600 dark:text-red-400">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium">Error al cargar suscripciones:</span>
            </div>
            <p class="mt-1 text-sm">{{ $error }}</p>
        </div>
    @elseif($subscriptions->isEmpty())
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
            <div class="flex flex-col items-center gap-3">
                <svg class="h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <div>
                    <h3 class="font-medium">Sin suscripciones registradas</h3>
                    <p class="text-sm">Este usuario aún no tiene historial de suscripciones.</p>
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
                                Plan
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Período
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Proveedor
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Renovación
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Cupón
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                        @foreach($subscriptions as $subscription)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 {{ in_array($subscription->status, ['active', 'in_trial']) ? 'bg-green-50 dark:bg-green-900/10' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $subscription->plan->name ?? 'Plan eliminado' }}
                                        </div>
                                        <div class="text-gray-500 dark:text-gray-400 text-xs">
                                            ID: {{ $subscription->id }}
                                        </div>
                                        @if($subscription->plan)
                                            <div class="text-gray-500 dark:text-gray-400 text-xs">
                                                ${{ number_format($subscription->plan->price_cents / 100, 2) }}/{{ $subscription->plan->interval }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @php
                                        $statusColors = [
                                            'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'in_trial' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'canceled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            'expired' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                        ];
                                        $statusNames = [
                                            'active' => 'Activa',
                                            'in_trial' => 'En Prueba',
                                            'pending' => 'Pendiente',
                                            'canceled' => 'Cancelada',
                                            'expired' => 'Expirada',
                                        ];
                                    @endphp
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$subscription->status] ?? $statusColors['pending'] }}">
                                            {{ $statusNames[$subscription->status] ?? ucfirst($subscription->status) }}
                                        </span>
                                        @if(in_array($subscription->status, ['active', 'in_trial']))
                                            @php
                                                $daysRemaining = $subscription->getDaysRemaining();
                                            @endphp
                                            @if($daysRemaining !== null)
                                                <span class="text-xs {{ $daysRemaining <= 7 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                                                    {{ $daysRemaining > 0 ? $daysRemaining . ' días restantes' : 'Vencida' }}
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    <div class="flex flex-col gap-1">
                                        <div>
                                            <span class="font-medium">Inicio:</span>
                                            {{ $subscription->created_at->format('d/m/Y') }}
                                        </div>
                                        @if($subscription->status === 'in_trial' && $subscription->trial_ends_at)
                                            <div>
                                                <span class="font-medium">Prueba hasta:</span>
                                                {{ $subscription->trial_ends_at->format('d/m/Y') }}
                                            </div>
                                        @elseif($subscription->current_period_end)
                                            <div>
                                                <span class="font-medium">Vence:</span>
                                                {{ $subscription->current_period_end->format('d/m/Y') }}
                                            </div>
                                        @endif
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
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $providerColors[$subscription->provider] ?? $providerColors['manual'] }}">
                                            {{ $providerNames[$subscription->provider] ?? ucfirst($subscription->provider) }}
                                        </span>
                                        @if($subscription->provider_subscription_id)
                                            <div class="font-mono text-xs text-gray-500 dark:text-gray-400">
                                                {{ Str::limit($subscription->provider_subscription_id, 15) }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    @if($subscription->auto_renew)
                                        <div class="flex items-center justify-center">
                                            <div class="flex items-center">
                                                <svg class="h-4 w-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <span class="ml-1 text-green-600 dark:text-green-400 text-xs font-medium">Sí</span>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center">
                                            <div class="flex items-center">
                                                <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                <span class="ml-1 text-red-600 dark:text-red-400 text-xs font-medium">No</span>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($subscription->coupon)
                                        <div class="flex flex-col gap-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                {{ $subscription->coupon->code }}
                                            </span>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $subscription->coupon->discount_percent }}% OFF
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 text-xs">Sin cupón</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
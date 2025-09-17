<div class="fi-section-content-ctn rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    @if(isset($error))
        <div class="p-4 text-red-600 dark:text-red-400">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium">Error al cargar actividad:</span>
            </div>
            <p class="mt-1 text-sm">{{ $error }}</p>
        </div>
    @elseif($events->isEmpty())
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
            <div class="flex flex-col items-center gap-3">
                <svg class="h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="font-medium">Sin actividad registrada</h3>
                    <p class="text-sm">No hay eventos de actividad para mostrar.</p>
                </div>
            </div>
        </div>
    @else
        <div class="p-6">
            <div class="flow-root">
                <ul role="list" class="-mb-8">
                    @foreach($events as $index => $event)
                        <li>
                            <div class="relative pb-8">
                                @if(!$loop->last)
                                    <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex space-x-3">
                                    <div>
                                        @php
                                            $iconClasses = [
                                                'blue' => 'bg-blue-500',
                                                'green' => 'bg-green-500',
                                                'purple' => 'bg-purple-500',
                                                'yellow' => 'bg-yellow-500',
                                                'red' => 'bg-red-500',
                                                'gray' => 'bg-gray-500',
                                            ];
                                            $colorClass = $iconClasses[$event['color']] ?? $iconClasses['gray'];
                                        @endphp
                                        <span class="h-8 w-8 rounded-full {{ $colorClass }} flex items-center justify-center ring-8 ring-white dark:ring-gray-900">
                                            @switch($event['icon'])
                                                @case('user-plus')
                                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                                    </svg>
                                                    @break
                                                @case('check-circle')
                                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    @break
                                                @case('credit-card')
                                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                                    </svg>
                                                    @break
                                                @case('banknotes')
                                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    @break
                                                @default
                                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                            @endswitch
                                        </span>
                                    </div>
                                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $event['title'] }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $event['description'] }}
                                            </p>
                                        </div>
                                        <div class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                            <div>{{ $event['date']->format('d/m/Y') }}</div>
                                            <div class="text-xs">{{ $event['date']->format('H:i') }}</div>
                                            <div class="text-xs">{{ $event['date']->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if($events->count() >= 10)
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 text-center text-sm text-gray-500 dark:text-gray-400">
                    Mostrando los últimos 10 eventos. Historial completo disponible en cada sección específica.
                </div>
            @endif
        </div>
    @endif
</div>
<x-filament-panels::page>
    <div class="space-y-6">

        {{-- System Information --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                📊 Información del Sistema
            </h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($this->getSystemInfo() as $key => $value)
                    <div class="bg-gray-50 p-3 rounded">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ str_replace('_', ' ', $key) }}</div>
                        <div class="font-mono text-sm">
                            @if(is_bool($value))
                                <span class="px-2 py-1 rounded text-xs {{ $value ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $value ? 'TRUE' : 'FALSE' }}
                                </span>
                            @elseif($key === 'memory_usage' || $key === 'memory_peak')
                                {{ number_format($value / 1024 / 1024, 2) }} MB
                            @else
                                {{ $value }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Recent Errors --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                🚨 Errores Recientes (Últimos 20)
            </h3>

            @php $errors = $this->getRecentErrors() @endphp

            @if(empty($errors))
                <div class="text-green-600 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    No se encontraron errores recientes
                </div>
            @else
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($errors as $error)
                        <div class="bg-red-50 border-l-4 border-red-400 p-3">
                            <pre class="text-xs font-mono text-red-800 whitespace-pre-wrap">{{ $error }}</pre>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Log Content --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                📝 Logs del Sistema (Últimas 100 líneas)
            </h3>

            @php $logData = $this->getLogContent() @endphp

            <div class="mb-4 flex items-center space-x-4 text-sm text-gray-600">
                <span>📄 Líneas totales: {{ $logData['totalLines'] ?? 0 }}</span>
                <span>💾 Tamaño: {{ number_format(($logData['size'] ?? 0) / 1024, 2) }} KB</span>
                @if($logData['modified'] ?? false)
                    <span>🕒 Modificado: {{ date('Y-m-d H:i:s', $logData['modified']) }}</span>
                @endif
            </div>

            @if($logData['exists'])
                <div class="bg-black text-green-400 p-4 rounded-lg font-mono text-xs overflow-x-auto max-h-96 overflow-y-auto">
                    @foreach($logData['lines'] as $line)
                        <div class="mb-1 {{
                            str_contains($line, 'ERROR') ? 'text-red-400' :
                            (str_contains($line, 'WARNING') ? 'text-yellow-400' :
                            (str_contains($line, 'INFO') ? 'text-blue-400' : 'text-green-400'))
                        }}">
                            {{ trim($line) }}
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-gray-500 text-center py-8">
                    📭 No se encontró el archivo de logs
                </div>
            @endif
        </div>

        {{-- Debug Actions --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                🛠️ Enlaces de Debug
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="/debug-502-test" target="_blank"
                   class="bg-blue-50 border border-blue-200 rounded-lg p-4 hover:bg-blue-100 transition-colors">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        <div>
                            <div class="font-semibold text-blue-700">Test 502 Debug</div>
                            <div class="text-sm text-blue-600">Probar endpoint sin CSRF</div>
                        </div>
                    </div>
                </a>

                <a href="/debug/live-logs" target="_blank"
                   class="bg-green-50 border border-green-200 rounded-lg p-4 hover:bg-green-100 transition-colors">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <div>
                            <div class="font-semibold text-green-700">Live Logs JSON</div>
                            <div class="text-sm text-green-600">Ver logs formato JSON</div>
                        </div>
                    </div>
                </a>

                <a href="/debug-monitor.html" target="_blank"
                   class="bg-purple-50 border border-purple-200 rounded-lg p-4 hover:bg-purple-100 transition-colors">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <div>
                            <div class="font-semibold text-purple-700">Monitor Externo</div>
                            <div class="text-sm text-purple-600">Página independiente</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

    </div>

    {{-- Auto-refresh script --}}
    <script>
        // Auto-refresh cada 30 segundos
        setInterval(function() {
            if (!document.hidden) {
                location.reload();
            }
        }, 30000);
    </script>
</x-filament-panels::page>
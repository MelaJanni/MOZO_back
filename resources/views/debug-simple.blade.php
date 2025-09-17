<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Debug Panel - MOZO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta http-equiv="refresh" content="30">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="bg-white shadow rounded-lg mb-6 p-6">
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-gray-900">🔍 Debug Panel - MOZO</h1>
                    <div class="flex space-x-4">
                        <a href="/admin" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            ← Volver al Admin
                        </a>
                        <button onclick="location.reload()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                            🔄 Refrescar
                        </button>
                    </div>
                </div>
                <p class="text-gray-600 mt-2">Auto-refresh cada 30 segundos | Última actualización: {{ now()->format('Y-m-d H:i:s') }}</p>
            </div>

            <!-- System Info -->
            <div class="bg-white shadow rounded-lg mb-6 p-6">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    📊 Información del Sistema
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    @foreach($systemInfo as $key => $value)
                        <div class="bg-gray-50 p-3 rounded">
                            <div class="text-xs text-gray-500 uppercase">{{ str_replace('_', ' ', $key) }}</div>
                            <div class="font-mono text-sm font-semibold">
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

            <!-- Recent Errors -->
            <div class="bg-white shadow rounded-lg mb-6 p-6">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    🚨 Errores Recientes (Últimos 20)
                </h2>
                @if(empty($errors))
                    <div class="text-green-600 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        ✅ No se encontraron errores recientes
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

            <!-- Log Content -->
            <div class="bg-white shadow rounded-lg mb-6 p-6">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    📝 Logs del Sistema (Últimas 100 líneas)
                </h2>

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

            <!-- Debug Tools -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">🛠️ Herramientas de Debug</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <a href="/debug-502-test" target="_blank" class="bg-blue-50 border border-blue-200 rounded-lg p-4 hover:bg-blue-100 transition-colors text-center">
                        <div class="font-semibold text-blue-700">Test 502 Debug</div>
                        <div class="text-sm text-blue-600">Probar endpoint POST</div>
                    </a>

                    <a href="/debug/live-logs" target="_blank" class="bg-green-50 border border-green-200 rounded-lg p-4 hover:bg-green-100 transition-colors text-center">
                        <div class="font-semibold text-green-700">Live Logs JSON</div>
                        <div class="text-sm text-green-600">Formato JSON</div>
                    </a>

                    <a href="/debug-monitor.html" target="_blank" class="bg-purple-50 border border-purple-200 rounded-lg p-4 hover:bg-purple-100 transition-colors text-center">
                        <div class="font-semibold text-purple-700">Monitor Externo</div>
                        <div class="text-sm text-purple-600">Página independiente</div>
                    </a>

                    <button onclick="clearLogs()" class="bg-red-50 border border-red-200 rounded-lg p-4 hover:bg-red-100 transition-colors text-center">
                        <div class="font-semibold text-red-700">Limpiar Logs</div>
                        <div class="text-sm text-red-600">⚠️ Usar con cuidado</div>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script>
        function clearLogs() {
            if (confirm('¿Estás seguro que quieres limpiar los logs?')) {
                fetch('/debug/clear-logs', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        alert('Logs limpiados exitosamente');
                        location.reload();
                    })
                    .catch(error => {
                        alert('Error limpiando logs: ' + error);
                    });
            }
        }
    </script>
</body>
</html>
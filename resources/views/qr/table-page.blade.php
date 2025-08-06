<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $business->name }} - Mesa {{ $table->number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #e9ecef;
        }

        .business-name {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .table-info {
            font-size: 18px;
            color: #6c757d;
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 20px;
            display: inline-block;
        }

        .menu-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .section-header {
            background: #495057;
            color: white;
            padding: 20px;
            font-size: 22px;
            font-weight: bold;
        }

        .menu-content {
            padding: 20px;
        }

        .menu-iframe {
            width: 100%;
            height: 600px;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .download-btn {
            display: inline-block;
            background: #17a2b8;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 15px;
            font-weight: bold;
            transition: background 0.3s;
        }

        .download-btn:hover {
            background: #138496;
        }

        .call-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            text-align: center;
        }

        .call-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 18px 40px;
            font-size: 20px;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
            max-width: 300px;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .call-btn:hover:not(:disabled) {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4);
        }

        .call-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .status-message {
            padding: 20px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            display: none;
        }

        .status-loading {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .no-menu-message {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 40px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header {
                padding: 20px;
            }
            
            .business-name {
                font-size: 24px;
            }
            
            .menu-iframe {
                height: 400px;
            }
            
            .call-btn {
                padding: 16px 30px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header del negocio -->
        <header class="header">
            @if($business->logo)
                <img src="{{ asset('storage/' . $business->logo) }}" alt="{{ $business->name }}" class="logo">
            @endif
            <h1 class="business-name">{{ $business->name }}</h1>
            <div class="table-info">
                📍 Mesa {{ $table->number }}@if($table->name) - {{ $table->name }}@endif
            </div>
        </header>

        <!-- Menú PDF -->
        @if($menu)
        <section class="menu-section">
            <div class="section-header">
                🍽️ {{ $menu->name }}
            </div>
            <div class="menu-content">
                <iframe src="{{ route('public.menu.download', $menu->id) }}" class="menu-iframe"></iframe>
                <a href="{{ route('public.menu.download', $menu->id) }}" target="_blank" class="download-btn">
                    📄 Descargar Menú PDF
                </a>
            </div>
        </section>
        @else
        <section class="menu-section">
            <div class="section-header">
                🍽️ Nuestro Menú
            </div>
            <div class="no-menu-message">
                El menú no está disponible en este momento. Por favor, consulte con nuestro personal.
            </div>
        </section>
        @endif

        <!-- Botón llamar mozo -->
        <section class="call-section">
            <div id="status-message" class="status-message"></div>
            
            <div id="call-controls">
                @if($canCallWaiter)
                    <button id="call-waiter-btn" class="call-btn">
                        🔔 Llamar Mozo
                    </button>
                    <p style="margin-top: 15px; color: #6c757d; font-size: 14px;">
                        @if($table->activeWaiter)
                            Mozo asignado: <strong>{{ $table->activeWaiter->name }}</strong>
                        @endif
                    </p>
                @else
                    <div class="status-message status-error" style="display: block;">
                        ❌ Esta mesa no tiene un mozo asignado actualmente.<br>
                        Por favor, llame manualmente al personal.
                    </div>
                @endif
            </div>
        </section>
    </div>

    <script>
        const API_BASE = '{{ $apiBaseUrl }}';
        const TABLE_ID = {{ $table->id }};
        let pollingInterval;

        const statusMessage = document.getElementById('status-message');
        const callBtn = document.getElementById('call-waiter-btn');

        // Verificar estado inicial
        @if($pendingCall)
            @if($pendingCall->status === 'pending')
                showStatus('loading', '⏳ Llamando mozo... Aguarde por favor');
                startPolling();
            @elseif($pendingCall->status === 'acknowledged')
                showStatus('success', '✅ Mozo en camino - Tiempo de respuesta: {{ $pendingCall->called_at->diffForHumans() }}');
            @endif
        @endif

        if (callBtn) {
            callBtn.addEventListener('click', async function() {
                try {
                    showStatus('loading', '⏳ Enviando llamada...');
                    callBtn.disabled = true;

                    const response = await fetch(`${API_BASE}/api/tables/${TABLE_ID}/call-waiter`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            message: 'Solicitud de atención desde mesa {{ $table->number }}',
                            urgency: 'normal'
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        showStatus('loading', '⏳ Llamando mozo... Aguarde por favor');
                        startPolling();
                    } else {
                        showStatus('error', '❌ ' + data.message);
                        callBtn.disabled = false;
                    }
                } catch (error) {
                    console.error('Error calling waiter:', error);
                    showStatus('error', '❌ Error de conexión. Intente nuevamente.');
                    callBtn.disabled = false;
                }
            });
        }

        function showStatus(type, message) {
            statusMessage.className = `status-message status-${type}`;
            statusMessage.textContent = message;
            statusMessage.style.display = 'block';
        }

        function hideStatus() {
            statusMessage.style.display = 'none';
        }

        function startPolling() {
            if (pollingInterval) clearInterval(pollingInterval);
            
            pollingInterval = setInterval(async () => {
                try {
                    const response = await fetch(`${API_BASE}/api/table/${TABLE_ID}/status`);
                    const data = await response.json();

                    if (data.success && data.data.active_call) {
                        const call = data.data.active_call;
                        
                        if (call.status === 'acknowledged') {
                            showStatus('success', '✅ Mozo en camino - Confirmado hace ' + call.minutes_ago + ' minutos');
                            stopPolling();
                            setTimeout(() => {
                                resetToInitialState();
                            }, 30000); // Reset after 30 seconds
                        } else if (call.status === 'pending') {
                            showStatus('loading', '⏳ Llamando mozo... Aguarde por favor');
                        }
                    } else {
                        // No active call - probably completed
                        resetToInitialState();
                    }
                } catch (error) {
                    console.error('Polling error:', error);
                }
            }, 3000); // Poll every 3 seconds
        }

        function stopPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        }

        function resetToInitialState() {
            hideStatus();
            if (callBtn) {
                callBtn.disabled = false;
            }
            stopPolling();
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            stopPolling();
        });
    </script>
</body>
</html>
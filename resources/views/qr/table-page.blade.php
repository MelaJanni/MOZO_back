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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .header {
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin: 0 auto 15px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .logo img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
        }

        .restaurant-name {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .table-info {
            font-size: 18px;
            opacity: 0.9;
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
        }

        .content {
            padding: 30px 20px;
        }

        .welcome-message {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            border-left: 5px solid #4a90e2;
        }

        .welcome-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .welcome-text {
            color: #6c757d;
            font-size: 16px;
            line-height: 1.5;
        }

        .menu-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #4a90e2;
            display: flex;
            align-items: center;
        }

        .section-title::before {
            content: "📋";
            margin-right: 10px;
            font-size: 24px;
        }

        .menu-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            background: white;
            border: 1px solid #e9ecef;
        }

        .menu-pdf {
            width: 100%;
            height: 600px;
            border: none;
        }

        .call-waiter-section {
            text-align: center;
            margin: 40px 0;
            padding: 30px 20px;
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-radius: 15px;
            border: 1px solid #f39c12;
        }

        .call-button {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            padding: 18px 40px;
            font-size: 20px;
            font-weight: bold;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.3);
            position: relative;
            overflow: hidden;
        }

        .call-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
        }

        .call-button:active {
            transform: translateY(0);
        }

        .call-button:disabled {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
        }

        .call-button::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .call-button:hover::before {
            left: 100%;
        }

        .status-message {
            margin-top: 15px;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            animation: pulse 2s infinite;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
            background: #f8f9fa;
        }

        @media (max-width: 768px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            
            .header {
                padding: 15px;
            }
            
            .restaurant-name {
                font-size: 24px;
            }
            
            .content {
                padding: 20px 15px;
            }
            
            .call-button {
                width: 100%;
                max-width: 300px;
                padding: 16px 30px;
                font-size: 18px;
            }
            
            .menu-pdf {
                height: 500px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                @if($business->logo)
                    <img src="{{ asset('storage/' . $business->logo) }}" alt="{{ $business->name }} Logo">
                @else
                    🍴
                @endif
            </div>
            <h1 class="restaurant-name">{{ $business->name }}</h1>
            <div class="table-info">Mesa {{ $table->number }} - {{ $table->name }}</div>
        </header>

        <main class="content">
            <div class="welcome-message">
                <h2 class="welcome-title">¡Bienvenido!</h2>
                <p class="welcome-text">
                    Estás en {{ $business->name }}, Mesa {{ $table->number }}. 
                    Revisa nuestro menú y no dudes en llamar a nuestro mozo cuando estés listo para ordenar.
                </p>
            </div>

            <section class="menu-section">
                <h2 class="section-title">Nuestro Menú</h2>
                <div class="menu-container">
                    @if($defaultMenu && $defaultMenu->file_path)
                        <iframe 
                            src="{{ asset('storage/' . $defaultMenu->file_path) }}" 
                            class="menu-pdf"
                            title="Menú de {{ $business->name }}">
                            <p>Tu navegador no puede mostrar PDFs. 
                            <a href="{{ asset('storage/' . $defaultMenu->file_path) }}" target="_blank">
                                Haz clic aquí para ver el menú
                            </a></p>
                        </iframe>
                    @elseif($business->menu_pdf)
                        <!-- Fallback al campo antiguo menu_pdf si existe -->
                        <iframe 
                            src="{{ asset('storage/' . $business->menu_pdf) }}" 
                            class="menu-pdf"
                            title="Menú de {{ $business->name }}">
                            <p>Tu navegador no puede mostrar PDFs. 
                            <a href="{{ asset('storage/' . $business->menu_pdf) }}" target="_blank">
                                Haz clic aquí para ver el menú
                            </a></p>
                        </iframe>
                    @else
                        <div style="padding: 40px; text-align: center; color: #6c757d;">
                            <h3>Menú no disponible</h3>
                            <p>Por favor solicita el menú físico a nuestro personal</p>
                            @if($defaultMenu)
                                <small style="display: block; margin-top: 10px; font-size: 12px; opacity: 0.7;">
                                    Debug: Menú encontrado pero sin archivo (ID: {{ $defaultMenu->id }})
                                </small>
                            @endif
                        </div>
                    @endif
                </div>
            </section>

            <section class="call-waiter-section">
                <button id="callWaiterBtn" class="call-button" onclick="callWaiter()">
                    🔔 Llamar Mozo
                </button>
                <div id="statusMessage" class="status-message" style="display: none;"></div>
            </section>
        </main>

        <footer class="footer">
            <p>© {{ date('Y') }} {{ $business->name }}. Sistema de llamado QR activo.</p>
        </footer>
    </div>

    <script>
        const FRONTEND_URL = '{{ $frontendUrl }}';
        const RESTAURANT_ID = {{ $business->id }};
        const TABLE_ID = {{ $table->id }};
        
        let currentNotificationId = null;
        let pollingInterval = null;

        function callWaiter() {
            const button = document.getElementById('callWaiterBtn');
            const statusMessage = document.getElementById('statusMessage');
            
            button.disabled = true;
            button.textContent = '📞 Enviando...';
            
            statusMessage.style.display = 'block';
            statusMessage.className = 'status-message status-pending';
            statusMessage.textContent = 'Enviando solicitud al mozo...';

            fetch(`${FRONTEND_URL}/api/waiter-notifications`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    restaurant_id: RESTAURANT_ID,
                    table_id: TABLE_ID,
                    message: 'Cliente solicita atención',
                    urgency: 'normal'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentNotificationId = data.data.id;
                    button.textContent = '⏳ Esperando confirmación...';
                    statusMessage.className = 'status-message status-pending';
                    statusMessage.textContent = '🔥 Esperando confirmación en tiempo real...';
                    
                    // 🚀 SOLO FIREBASE TIEMPO REAL - NO POLLING
                    if (!firebaseReady) {
                        console.warn('⚠️ Firebase not ready, forcing initialization...');
                        initializeFirebaseImmediately();
                    }
                    
                    // Timeout de emergencia si Firebase falla
                    setTimeout(() => {
                        if (button.disabled && (button.textContent.includes('Esperando') || button.textContent.includes('Enviando'))) {
                            button.disabled = false;
                            button.textContent = '🔔 Llamar Mozo';
                            statusMessage.className = 'status-message';
                            statusMessage.textContent = 'Tiempo de espera agotado. Puedes volver a llamar.';
                            
                            setTimeout(() => {
                                statusMessage.style.display = 'none';
                            }, 3000);
                            
                            currentNotificationId = null;
                        }
                    }, 30000); // 30 segundos timeout
                    
                    // NO LLAMAR startPolling() - Solo Firebase
                } else {
                    throw new Error(data.message || 'Error al enviar la solicitud');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                statusMessage.className = 'status-message status-error';
                statusMessage.textContent = 'Error al contactar al mozo. Inténtalo nuevamente.';
                
                button.disabled = false;
                button.textContent = '🔔 Llamar Mozo';
            });
        }

        // 🔥 ELIMINADO: Ya no usamos polling, solo Firebase tiempo real
        function startPolling() {
            console.log('🚫 Polling disabled - using Firebase real-time only');
            
            // Si llegamos aquí sin Firebase, forzar inicialización
            if (!firebaseReady) {
                console.error('❌ Firebase not ready when trying to poll. Forcing init...');
                initializeFirebaseImmediately();
            }
        }
        
        // 🚀 Inicialización inmediata de Firebase
        function initializeFirebaseImmediately() {
            console.log('🔥 Forcing immediate Firebase initialization...');
            
            if (window.FirebaseRealtimeService) {
                try {
                    window.FirebaseRealtimeService.forceInit();
                    firebaseReady = true;
                    console.log('✅ Firebase initialized successfully!');
                    setupFirebaseRealtime();
                } catch (error) {
                    console.error('❌ Failed to initialize Firebase:', error);
                }
            } else {
                console.error('❌ FirebaseRealtimeService not available');
                // Intentar cargar el script de Firebase si no está disponible
                loadFirebaseScript();
            }
        }
        
        // 📥 Cargar script de Firebase si no está disponible
        function loadFirebaseScript() {
            const script = document.createElement('script');
            script.src = '{{ asset('js/firebase-realtime.js') }}';
            script.onload = function() {
                console.log('🔄 Firebase script loaded, retrying initialization...');
                setTimeout(initializeFirebaseImmediately, 100);
            };
            script.onerror = function() {
                console.error('❌ Failed to load Firebase script');
            };
            document.head.appendChild(script);
        }

        // 🚀 MEJORAR NOTIFICACIONES CON FIREBASE REAL-TIME
        let firebaseReady = false;
        let firebaseRetries = 0;
        
        // Esperar a que Firebase esté listo (SIN POLLING FALLBACK)
        function waitForFirebase() {
            if (window.FirebaseRealtimeService && window.FirebaseRealtimeService.initialized) {
                firebaseReady = true;
                console.log('🎉 Firebase ready! Real-time notifications active');
                setupFirebaseRealtime();
            } else if (firebaseRetries < 20) { // Más reintentos
                firebaseRetries++;
                console.log(`⏳ Waiting for Firebase... (retry ${firebaseRetries}/20)`);
                setTimeout(waitForFirebase, 500); // Más frecuente
            } else {
                console.error('❌ Firebase not ready after 20 retries. Real-time notifications disabled.');
                console.error('🚫 POLLING IS PERMANENTLY DISABLED - Only Firebase real-time supported');
                
                // Mostrar mensaje al usuario
                const statusMessage = document.getElementById('statusMessage');
                if (statusMessage && currentNotificationId) {
                    statusMessage.className = 'status-message status-error';
                    statusMessage.textContent = '⚠️ Tiempo real no disponible. Por favor recarga la página.';
                    statusMessage.style.display = 'block';
                }
            }
        }
        
        // Configurar escucha en tiempo real con Firebase
        function setupFirebaseRealtime() {
            const tableId = {{ $table->id }};
            
            // Escuchar llamadas de mozo
            window.FirebaseRealtimeService.listenToTableCalls(tableId, (update) => {
                if (update.success) {
                    console.log('📨 Real-time call update:', update);
                    
                    // Buscar nuestra llamada actual
                    const ourCall = update.calls.find(call => call.id == currentNotificationId);
                    if (ourCall && ourCall.status === 'acknowledged') {
                        handleWaiterAcknowledgment();
                    }
                } else {
                    console.error('❌ Firebase call update error:', update.error);
                    // NO FALLBACK - Solo Firebase tiempo real
                    console.warn('⚠️ Firebase error detected, attempting reconnection...');
                    if (currentNotificationId) {
                        setTimeout(() => {
                            if (!firebaseReady) {
                                initializeFirebaseImmediately();
                            }
                        }, 2000);
                    }
                }
            });
            
            // Opcional: Escuchar cambios de estado de mesa
            window.FirebaseRealtimeService.listenToTableStatus(tableId, (update) => {
                if (update.success) {
                    console.log('📊 Real-time status update:', update);
                    // Aquí puedes manejar cambios de estado de mesa si es necesario
                }
            });
        }
        
        // Manejar confirmación del mozo
        function handleWaiterAcknowledgment() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
            
            const statusMessage = document.getElementById('statusMessage');
            const button = document.getElementById('callWaiterBtn');
            
            statusMessage.className = 'status-message status-success';
            statusMessage.textContent = '✅ ¡El mozo confirmó tu solicitud! Llegará en breve.';
            
            setTimeout(() => {
                button.disabled = false;
                button.textContent = '🔔 Llamar Mozo';
                statusMessage.style.display = 'none';
                currentNotificationId = null;
            }, 5000);
        }
        
        // 🚀 INICIALIZAR FIREBASE INMEDIATAMENTE
        // Intentar inicialización inmediata en lugar de esperar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initializeFirebaseImmediately();
                // Fallback si Firebase tarda
                setTimeout(waitForFirebase, 1000);
            });
        } else {
            initializeFirebaseImmediately();
            // Fallback si Firebase tarda
            setTimeout(waitForFirebase, 1000);
        }
        
        // 🚫 POLLING COMPLETAMENTE ELIMINADO - Solo Firebase tiempo real
        // Eliminar todas las variables de polling
        let pollingInterval = null; // Mantener para compatibilidad con handleWaiterAcknowledgment
        
        // Sobreescribir startPolling para que NUNCA haga polling
        const originalStartPolling = startPolling;
        startPolling = function() {
            console.log('🚫 Polling permanently disabled - Firebase real-time only');
            
            // Si Firebase no está listo, forzar inicialización
            if (!firebaseReady) {
                console.warn('⚠️ Firebase not ready, forcing immediate initialization...');
                initializeFirebaseImmediately();
            }
            
            // NUNCA iniciar polling - solo Firebase
            return;
        };
    </script>

    <!-- 🔥 Firebase Real-time Service -->
    <script src="{{ asset('js/firebase-realtime.js') }}"></script>
</body>
</html>
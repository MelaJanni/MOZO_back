<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $business->name }} - Mesa {{ $table->number }} (REAL-TIME)</title>
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

        .call-button:disabled {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
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

        .realtime-status {
            position: fixed;
            bottom: 10px;
            left: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px 15px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .realtime-status.connected {
            background: rgba(34, 139, 34, 0.9);
        }

        .realtime-status.error {
            background: rgba(220, 20, 60, 0.9);
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
            <div class="table-info">Mesa {{ $table->number }} - {{ $table->name }} (REAL-TIME)</div>
        </header>

        <main class="content">
            <section class="call-waiter-section">
                <button id="callWaiterBtn" class="call-button" onclick="callWaiter()">
                    🔔 Llamar Mozo
                </button>
                <div id="statusMessage" class="status-message" style="display: none;"></div>
            </section>
        </main>
    </div>

    <div id="realtimeStatus" class="realtime-status">
        🔄 Conectando Firebase...
    </div>

    <script>
        const FRONTEND_URL = '{{ $frontendUrl }}';
        const RESTAURANT_ID = {{ $business->id }};
        const TABLE_ID = {{ $table->id }};
        
        let currentNotificationId = null;
        let firebaseListener = null;

        function updateRealtimeStatus(message, type = 'connecting') {
            const status = document.getElementById('realtimeStatus');
            status.textContent = message;
            status.className = `realtime-status ${type}`;
        }

        function callWaiter() {
            const button = document.getElementById('callWaiterBtn');
            const statusMessage = document.getElementById('statusMessage');
            
            button.disabled = true;
            button.textContent = '📞 Enviando...';
            
            statusMessage.style.display = 'block';
            statusMessage.className = 'status-message status-pending';
            statusMessage.textContent = '🚀 Enviando solicitud - Firebase tiempo real activo...';

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
                    urgency: 'high'
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('✅ Notification created:', data);
                if (data.success) {
                    currentNotificationId = data.data.id;
                    button.textContent = '⚡ Esperando confirmación...';
                    statusMessage.textContent = '🔥 Escuchando Firebase en TIEMPO REAL...';
                    
                    // NO polling - solo Firebase real-time
                    startFirebaseRealtime();
                    
                    // Timeout de emergencia
                    setTimeout(() => {
                        if (button.disabled && button.textContent.includes('Esperando')) {
                            console.warn('⚠️ Emergency timeout reached');
                            resetButton();
                        }
                    }, 60000); // 1 minuto timeout
                } else {
                    throw new Error(data.message || 'Error al enviar la solicitud');
                }
            })
            .catch(error => {
                console.error('❌ Error:', error);
                statusMessage.className = 'status-message status-error';
                statusMessage.textContent = 'Error al contactar al mozo. Inténtalo nuevamente.';
                
                button.disabled = false;
                button.textContent = '🔔 Llamar Mozo';
            });
        }

        function startFirebaseRealtime() {
            console.log('🔥 Starting Firebase real-time listener...');
            
            if (!window.FirebaseRealtimeService) {
                console.error('❌ Firebase service not available');
                updateRealtimeStatus('❌ Firebase no disponible', 'error');
                return;
            }

            if (!window.FirebaseRealtimeService.initialized) {
                console.log('⏳ Waiting for Firebase to initialize...');
                updateRealtimeStatus('⏳ Inicializando Firebase...', 'connecting');
                
                setTimeout(() => {
                    if (window.FirebaseRealtimeService.initialized) {
                        startFirebaseRealtime();
                    } else {
                        console.error('❌ Firebase initialization timeout');
                        updateRealtimeStatus('❌ Timeout Firebase', 'error');
                    }
                }, 5000);
                return;
            }

            updateRealtimeStatus('🔥 Escuchando tiempo real...', 'connected');

            firebaseListener = window.FirebaseRealtimeService.listenToTableCalls(TABLE_ID, (update) => {
                console.log('📨 Firebase real-time update:', update);
                
                if (update.success) {
                    updateRealtimeStatus(`🔥 Tiempo real activo (${update.totalCalls} calls)`, 'connected');
                    
                    // Buscar nuestra llamada actual
                    const ourCall = update.calls.find(call => call.id == currentNotificationId);
                    if (ourCall) {
                        console.log('🎯 Found our call:', ourCall);
                        
                        if (ourCall.status === 'acknowledged') {
                            console.log('🎉 FIREBASE REAL-TIME SUCCESS! Call acknowledged!');
                            handleWaiterAcknowledgment();
                        } else if (ourCall.status === 'completed') {
                            console.log('✅ Call completed via Firebase!');
                            handleCallCompleted();
                        }
                    }
                } else {
                    console.error('❌ Firebase real-time error:', update.error);
                    updateRealtimeStatus(`❌ Error: ${update.error}`, 'error');
                }
            });
        }

        function handleWaiterAcknowledgment() {
            console.log('🎉 Waiter acknowledged - FIREBASE REAL-TIME SUCCESS!');
            
            if (firebaseListener) {
                firebaseListener();
                firebaseListener = null;
            }
            
            const statusMessage = document.getElementById('statusMessage');
            const button = document.getElementById('callWaiterBtn');
            
            statusMessage.className = 'status-message status-success';
            statusMessage.textContent = '🎉 ¡FIREBASE TIEMPO REAL! El mozo confirmó instantáneamente!';
            
            updateRealtimeStatus('✅ Confirmado en tiempo real!', 'connected');
            
            setTimeout(() => {
                resetButton();
            }, 5000);
        }

        function handleCallCompleted() {
            console.log('✅ Call completed via Firebase real-time!');
            
            if (firebaseListener) {
                firebaseListener();
                firebaseListener = null;
            }
            
            const statusMessage = document.getElementById('statusMessage');
            
            statusMessage.className = 'status-message status-success';
            statusMessage.textContent = '✅ ¡Solicitud completada en tiempo real!';
            
            updateRealtimeStatus('✅ Completado!', 'connected');
            
            setTimeout(() => {
                resetButton();
            }, 3000);
        }

        function resetButton() {
            const button = document.getElementById('callWaiterBtn');
            const statusMessage = document.getElementById('statusMessage');
            
            button.disabled = false;
            button.textContent = '🔔 Llamar Mozo';
            statusMessage.style.display = 'none';
            currentNotificationId = null;
            
            updateRealtimeStatus('🔥 Tiempo real listo', 'connected');
        }

        // Esperar a que Firebase esté listo
        function waitForFirebase() {
            if (window.FirebaseRealtimeService && window.FirebaseRealtimeService.initialized) {
                updateRealtimeStatus('🔥 Firebase listo!', 'connected');
                console.log('🎉 Firebase Real-time ready for instant notifications!');
            } else {
                updateRealtimeStatus('⏳ Cargando Firebase...', 'connecting');
                setTimeout(waitForFirebase, 1000);
            }
        }

        // Inicializar cuando la página esté lista
        document.addEventListener('DOMContentLoaded', () => {
            console.log('🚀 Firebase Real-time QR page loaded!');
            waitForFirebase();
        });

        console.log('🔥 REAL-TIME notification system initialized!');
        console.log('⚡ Using Firebase Firestore real-time listeners');
        console.log('🎯 Target: Instant notifications (<1 second)');
    </script>

    <!-- 🔥 Firebase Real-time Service -->
    <script src="{{ asset('js/firebase-realtime-simple.js') }}"></script>
</body>
</html>
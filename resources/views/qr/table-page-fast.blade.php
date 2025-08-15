<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $business->name }} - Mesa {{ $table->number }} (REAL-TIME)</title>
    
    <!-- 🔥 Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-database-compat.js"></script>
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

        .debug-info {
            position: fixed;
            bottom: 10px;
            left: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            max-width: 300px;
            z-index: 1000;
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

        /* 🔥 ESTILOS PARA NOTIFICACIONES EN TIEMPO REAL */
        .client-notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-100px);
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            z-index: 10000;
            max-width: 400px;
            width: 90%;
            opacity: 0;
            transition: all 0.3s ease;
            border-left: 5px solid #4CAF50;
        }

        .client-notification.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        .client-notification.hiding {
            transform: translateX(-50%) translateY(-100px);
            opacity: 0;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .notification-header h3 {
            margin: 0;
            color: #333;
            font-size: 18px;
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
            <div class="table-info">Mesa {{ $table->number }} - {{ $table->name }} (ULTRA FAST)</div>
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

    <div id="debugInfo" class="debug-info" style="display: none;">
        <strong>🚀 ULTRA FAST DEBUG:</strong><br>
        Polling: <span id="pollingStatus">Stopped</span><br>
        Requests: <span id="requestCount">0</span><br>
        Last Check: <span id="lastCheck">Never</span><br>
        Current ID: <span id="currentId">None</span>
    </div>

    <script>
        const FRONTEND_URL = '{{ $frontendUrl }}';
        const RESTAURANT_ID = {{ $business->id }};
        const TABLE_ID = {{ $table->id }};
        
        let currentNotificationId = null;
        let pollingInterval = null;
        let requestCount = 0;
        let debugMode = true; // Enable debug for testing

        // Show debug info
        if (debugMode) {
            document.getElementById('debugInfo').style.display = 'block';
        }

        function updateDebug() {
            if (!debugMode) return;
            document.getElementById('pollingStatus').textContent = pollingInterval ? 'Active' : 'Stopped';
            document.getElementById('requestCount').textContent = requestCount;
            document.getElementById('lastCheck').textContent = new Date().toLocaleTimeString();
            document.getElementById('currentId').textContent = currentNotificationId || 'None';
        }

        function callWaiter() {
            const button = document.getElementById('callWaiterBtn');
            const statusMessage = document.getElementById('statusMessage');
            
            button.disabled = true;
            button.textContent = '📞 Enviando...';
            
            statusMessage.style.display = 'block';
            statusMessage.className = 'status-message status-pending';
            statusMessage.textContent = '🚀 Enviando solicitud ULTRA RÁPIDA...';

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
                    urgency: 'high' // HIGH priority for faster processing
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('✅ Notification created:', data);
                if (data.success) {
                    currentNotificationId = data.data.id;
                    button.textContent = '⚡ Esperando (ULTRA FAST)...';
                    statusMessage.textContent = '⚡ Esperando respuesta en TIEMPO REAL...';
                    
                    updateDebug();
                    startRealTimeStatusListener();
                    
                    // Timeout más corto
                    setTimeout(() => {
                        if (button.disabled) {
                            clearInterval(pollingInterval);
                            pollingInterval = null;
                            button.disabled = false;
                            button.textContent = '🔔 Llamar Mozo';
                            statusMessage.textContent = '⏰ Tiempo agotado. Inténtalo de nuevo.';
                            currentNotificationId = null;
                            updateDebug();
                        }
                    }, 30000);
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

        // 🔥 FIREBASE REALTIME LISTENER - REEMPLAZA POLLING
        function startRealTimeStatusListener() {
            console.log('🔥 Iniciando escucha en TIEMPO REAL con Firebase...');
            
            // Usar Firebase Realtime Database
            const firebaseConfig = {
                projectId: "mozoqr-7d32c",
                apiKey: "AIzaSyDGJJKNfSSxD6YnXnNjwRb6VUtPSyGN5CM",
                authDomain: "mozoqr-7d32c.firebaseapp.com",
                databaseURL: "https://mozoqr-7d32c-default-rtdb.firebaseio.com",
                storageBucket: "mozoqr-7d32c.appspot.com"
            };
            
            // Inicializar Firebase si no está inicializado
            if (!window.firebase || !window.firebase.apps.length) {
                firebase.initializeApp(firebaseConfig);
            }
            
            const database = firebase.database();
            const statusRef = database.ref(`tables/${TABLE_ID}/call_status`);
            
            // 🎧 ESCUCHAR CAMBIOS EN TIEMPO REAL
            statusRef.on('value', (snapshot) => {
                const data = snapshot.val();
                console.log('🔥 Estado actualizado en tiempo real:', data);
                
                if (data && currentNotificationId) {
                    handleRealTimeStatusUpdate(data);
                }
            });
            
            // Guardar referencia para cleanup
            window.firebaseStatusListener = statusRef;
        }

        // 🔥 MANEJAR ACTUALIZACIONES EN TIEMPO REAL
        function handleRealTimeStatusUpdate(data) {
            const { status, message, waiter_name, call_id } = data;
            
            console.log(`🔥 Estado: ${status}, Mensaje: ${message}`);
            
            if (status === 'acknowledged') {
                console.log('🎉 ACKNOWLEDGED! Mozo recibió la solicitud!');
                showRealTimeNotification('✅ Solicitud Recibida', `${waiter_name} recibió tu solicitud`);
                handleWaiterAcknowledgment();
                
            } else if (status === 'completed') {
                console.log('✅ COMPLETED! Servicio completado!');
                showRealTimeNotification('🎉 Servicio Completado', 'Tu solicitud ha sido atendida');
                handleTaskCompleted();
            }
        }

        // 🔔 MOSTRAR NOTIFICACIÓN VISUAL AL CLIENTE
        function showRealTimeNotification(title, message) {
            // Remover notificación anterior
            const existing = document.querySelector('.client-notification');
            if (existing) existing.remove();

            const notification = document.createElement('div');
            notification.className = 'client-notification';
            notification.innerHTML = `
                <div class="notification-header">
                    <h3>${title}</h3>
                    <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; font-size: 20px; cursor: pointer;">✕</button>
                </div>
                <p>${message}</p>
                <div style="font-size: 12px; color: #666; margin-top: 8px; text-align: right;">
                    ${new Date().toLocaleTimeString()}
                </div>
            `;

            document.body.appendChild(notification);
            
            // Mostrar con animación
            setTimeout(() => notification.classList.add('show'), 10);
            
            // Auto-remove después de 5 segundos
            setTimeout(() => {
                notification.classList.add('hiding');
                setTimeout(() => notification.remove(), 300);
            }, 5000);

            // 🔔 Notificación del navegador si está disponible
            if (Notification.permission === 'granted') {
                new Notification(title, {
                    body: message,
                    icon: '/favicon.ico',
                    tag: 'waiter-update'
                });
            }
        }

        function checkNotificationStatus() {
            if (!currentNotificationId) return;
            
            requestCount++;
            updateDebug();
            
            console.log(`⚡ Ultra fast check #${requestCount}:`, currentNotificationId);
            
            fetch(`${FRONTEND_URL}/api/waiter-notifications/${currentNotificationId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('📨 Status update:', data);
                    
                    if (data.success && data.data) {
                        if (data.data.is_acknowledged || data.data.status === 'acknowledged') {
                            console.log('🎉 ACKNOWLEDGED! Ultra fast response achieved!');
                            handleWaiterAcknowledgment();
                        } else if (data.data.is_completed || data.data.status === 'completed') {
                            console.log('✅ COMPLETED! Task finished!');
                            handleTaskCompleted();
                        }
                    }
                })
                .catch(error => {
                    console.error('❌ Polling error:', error);
                    // Continue polling even on error for maximum reliability
                });
        }

        function handleWaiterAcknowledgment() {
            clearInterval(pollingInterval);
            pollingInterval = null;
            
            const statusMessage = document.getElementById('statusMessage');
            const button = document.getElementById('callWaiterBtn');
            
            statusMessage.className = 'status-message status-success';
            statusMessage.textContent = '🎉 ¡ULTRA FAST! El mozo confirmó en tiempo récord!';
            
            updateDebug();
            
            setTimeout(() => {
                button.disabled = false;
                button.textContent = '🔔 Llamar Mozo';
                statusMessage.style.display = 'none';
                currentNotificationId = null;
                requestCount = 0;
                updateDebug();
            }, 5000);
        }

        function handleTaskCompleted() {
            clearInterval(pollingInterval);
            pollingInterval = null;
            
            const statusMessage = document.getElementById('statusMessage');
            const button = document.getElementById('callWaiterBtn');
            
            statusMessage.className = 'status-message status-success';
            statusMessage.textContent = '✅ ¡Tarea completada! Ultra rápido.';
            
            updateDebug();
            
            setTimeout(() => {
                button.disabled = false;
                button.textContent = '🔔 Llamar Mozo';
                statusMessage.style.display = 'none';
                currentNotificationId = null;
                requestCount = 0;
                updateDebug();
            }, 3000);
        }

        // 🔔 SOLICITAR PERMISOS DE NOTIFICACIÓN
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }

        console.log('🔥 FIREBASE Real-time notification system loaded!');
        console.log('⚡ Sin polling - WebSocket directo con Firebase');
        console.log('🎯 Latencia ultra-baja: ~50-200ms');
    </script>
</body>
</html>
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
        
        console.log('🔍 PAGE DEBUG INFO:');
        console.log('📍 TABLE_ID:', TABLE_ID);
        console.log('📍 RESTAURANT_ID:', RESTAURANT_ID);
        console.log('📍 FRONTEND_URL:', FRONTEND_URL);
        
        let currentNotificationId = null;
        let pollingInterval = null;
        let requestCount = 0;
        let debugMode = true; // Enable debug for testing
        let lastProcessedStatus = null; // Evitar procesar el mismo estado múltiples veces

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
                    lastProcessedStatus = null; // Reset para nueva llamada
                    console.log('🔍 currentNotificationId set to:', currentNotificationId);
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

        // 🔥 FIREBASE REALTIME LISTENER - DETECTA CAMBIOS DE ESTADO
        function startRealTimeStatusListener() {
            console.log('🚀🚀🚀 INICIANDO DEBUG COMPLETO - FIREBASE REALTIME DATABASE 🚀🚀🚀');
            console.log('🔍 TABLE_ID:', TABLE_ID);
            console.log('🔍 currentNotificationId al iniciar listener:', currentNotificationId);
            
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
                console.log('✅ Firebase inicializado');
            } else {
                console.log('✅ Firebase ya estaba inicializado');
            }
            
            const database = firebase.database();
            console.log('✅ Database reference obtenida');
            
            // 🔥 LISTENER PRINCIPAL: Primero intentar /tables/{table_id}/call_status/
            // Pero también escuchar en toda la estructura de tables para encontrar nuestros datos
            console.log('🎧 Configurando múltiples listeners para detectar cambios...');
            
            // Listener 1: Path específico esperado
            const statusRef = database.ref(`tables/${TABLE_ID}/call_status`);
            console.log('🎧 Listener 1 en path:', `tables/${TABLE_ID}/call_status`);
            
            // Listener 2: Toda la estructura de tables (por si el índice es diferente)
            const allTablesRef = database.ref('tables');
            console.log('🎧 Listener 2 en path: tables (completo)');
            
            statusRef.on('child_added', (snapshot) => {
                const callId = snapshot.key;
                const data = snapshot.val();
                console.log('🔥 [CHILD_ADDED] Nueva llamada detectada:', { callId, data, currentNotificationId });
                
                if (data && currentNotificationId && String(callId) === String(currentNotificationId)) {
                    console.log('✅ [CHILD_ADDED] Match found - handling update');
                    handleRealTimeStatusUpdate(data);
                }
            });
            
            statusRef.on('child_changed', (snapshot) => {
                const callId = snapshot.key;
                const data = snapshot.val();
                console.log('🔥 [CHILD_CHANGED] Estado actualizado:', { callId, data, currentNotificationId });
                
                if (data && currentNotificationId && String(callId) === String(currentNotificationId)) {
                    console.log('✅ [CHILD_CHANGED] Match found - handling update');
                    handleRealTimeStatusUpdate(data);
                }
            });
            
            statusRef.on('value', (snapshot) => {
                const allData = snapshot.val();
                console.log('🔥 [VALUE] Todos los datos de la mesa:', allData);
                
                if (allData && currentNotificationId) {
                    const myCallData = allData[currentNotificationId];
                    if (myCallData) {
                        console.log('🔥 [VALUE] Encontré mi llamada:', myCallData);
                        handleRealTimeStatusUpdate(myCallData);
                    } else {
                        console.log('⚠️ [VALUE] Mi llamada no encontrada en:', Object.keys(allData));
                    }
                } else {
                    console.log('⚠️ [VALUE] No hay datos o currentNotificationId no está set');
                }
            });
            
            // 🔥 LISTENER PARA TODA LA ESTRUCTURA DE TABLES
            allTablesRef.on('child_changed', (tableSnapshot) => {
                const tableIndex = tableSnapshot.key;
                const tableData = tableSnapshot.val();
                console.log(`🔥 [ALL_TABLES] Tabla ${tableIndex} cambió:`, tableData);
                
                if (tableData && tableData.call_status && currentNotificationId) {
                    const myCall = tableData.call_status[currentNotificationId];
                    if (myCall) {
                        console.log(`🔥 [ALL_TABLES] Mi llamada encontrada en tabla ${tableIndex}:`, myCall);
                        handleRealTimeStatusUpdate(myCall);
                    }
                }
            });
            
            allTablesRef.on('value', (tablesSnapshot) => {
                const allTablesData = tablesSnapshot.val();
                console.log('🔥 [ALL_TABLES_VALUE] Estructura completa de tables:', allTablesData);
                
                if (allTablesData && currentNotificationId) {
                    // Buscar mi llamada en todas las tablas
                    Object.keys(allTablesData).forEach(tableIndex => {
                        const tableData = allTablesData[tableIndex];
                        if (tableData && tableData.call_status && tableData.call_status[currentNotificationId]) {
                            console.log(`🎯 [ALL_TABLES_VALUE] Mi llamada encontrada en tabla ${tableIndex}:`, tableData.call_status[currentNotificationId]);
                            handleRealTimeStatusUpdate(tableData.call_status[currentNotificationId]);
                        }
                    });
                }
            });
            
            // 🔥 LISTENER ALTERNATIVO: Escuchar directamente en /waiters/*/calls/*
            const waitersRef = database.ref('waiters');
            console.log('🎧 Configurando listener en path: waiters');
            
            waitersRef.on('child_changed', (waiterSnapshot) => {
                const waiterId = waiterSnapshot.key;
                const waiterData = waiterSnapshot.val();
                console.log('🔥 [WAITERS] Waiter cambió:', { waiterId, waiterData });
                
                if (waiterData && waiterData.calls && currentNotificationId) {
                    const myCall = waiterData.calls[currentNotificationId];
                    if (myCall) {
                        console.log('🔥 [WAITERS] Estado de mi llamada cambió:', myCall);
                        handleRealTimeStatusUpdate(myCall);
                        
                        // Sincronizar en el path de la mesa
                        database.ref(`tables/${TABLE_ID}/call_status/${currentNotificationId}`).set(myCall)
                            .then(() => console.log('✅ Sincronizado en path de mesa'))
                            .catch(e => console.error('❌ Error sincronizando:', e));
                    } else {
                        console.log('⚠️ [WAITERS] Mi llamada no encontrada en calls del waiter');
                    }
                } else {
                    console.log('⚠️ [WAITERS] No hay calls o currentNotificationId no está set');
                }
            });
            
            // 🔥 LISTENER DE EMERGENCIA: Escuchar TODO el root para debug
            const rootRef = database.ref('/');
            rootRef.on('value', (snapshot) => {
                const allRootData = snapshot.val();
                console.log('🔥 [ROOT DEBUG] Estructura completa de Firebase:', allRootData);
                
                // Buscar mi llamada en TODA la estructura
                if (currentNotificationId && allRootData) {
                    let found = false;
                    
                    // Buscar en /tables/
                    if (allRootData.tables && allRootData.tables[TABLE_ID] && allRootData.tables[TABLE_ID].call_status) {
                        const myCall = allRootData.tables[TABLE_ID].call_status[currentNotificationId];
                        if (myCall) {
                            console.log('🎯 [ROOT DEBUG] Mi llamada encontrada en tables:', myCall);
                            found = true;
                        }
                    }
                    
                    // Buscar en /waiters/
                    if (allRootData.waiters) {
                        Object.keys(allRootData.waiters).forEach(waiterId => {
                            if (allRootData.waiters[waiterId].calls && allRootData.waiters[waiterId].calls[currentNotificationId]) {
                                console.log(`🎯 [ROOT DEBUG] Mi llamada encontrada en waiters/${waiterId}:`, allRootData.waiters[waiterId].calls[currentNotificationId]);
                                found = true;
                            }
                        });
                    }
                    
                    if (!found) {
                        console.log('❌ [ROOT DEBUG] Mi llamada NO encontrada en ningún lado');
                    }
                }
            });
            
            // Guardar referencias para cleanup
            window.firebaseStatusListener = statusRef;
            window.firebaseAllTablesListener = allTablesRef;
            window.firebaseWaitersListener = waitersRef;
            window.firebaseRootListener = rootRef;
            
            console.log('✅ Todos los listeners configurados');
        }

        // 🧹 FUNCIÓN PARA LIMPIAR LISTENERS DE FIREBASE
        function cleanupFirebaseListeners() {
            try {
                if (window.firebaseStatusListener) {
                    window.firebaseStatusListener.off();
                    window.firebaseStatusListener = null;
                    console.log('🧹 Firebase status listener cleaned up');
                }
                if (window.firebaseAllTablesListener) {
                    window.firebaseAllTablesListener.off();
                    window.firebaseAllTablesListener = null;
                    console.log('🧹 Firebase all tables listener cleaned up');
                }
                if (window.firebaseWaitersListener) {
                    window.firebaseWaitersListener.off();
                    window.firebaseWaitersListener = null;
                    console.log('🧹 Firebase waiters listener cleaned up');
                }
                if (window.firebaseRootListener) {
                    window.firebaseRootListener.off();
                    window.firebaseRootListener = null;
                    console.log('🧹 Firebase root listener cleaned up');
                }
            } catch (e) {
                console.warn('⚠️ Error cleaning up Firebase listeners:', e);
            }
        }

        // 🔥 MANEJAR ACTUALIZACIONES EN TIEMPO REAL
        function handleRealTimeStatusUpdate(data) {
            console.log('🔥 handleRealTimeStatusUpdate called with:', data);
            const { status, message, waiter_name, call_id } = data;
            
            console.log(`🔥 Estado: ${status}, Mensaje: ${message}, Waiter: ${waiter_name}`);
            
            // Evitar procesar el mismo estado múltiples veces
            if (lastProcessedStatus === status) {
                console.log('⚠️ Same status already processed, skipping:', status);
                return;
            }
            
            lastProcessedStatus = status;
            
            if (status === 'acknowledged') {
                console.log('🎉 ACKNOWLEDGED! Mozo recibió la solicitud!');
                showRealTimeNotification('✅ Solicitud Recibida', `${waiter_name || 'Tu mozo'} recibió tu solicitud`);
                handleWaiterAcknowledgment();
                
            } else if (status === 'completed') {
                console.log('✅ COMPLETED! Servicio completado!');
                showRealTimeNotification('🎉 Servicio Completado', 'Tu solicitud ha sido atendida');
                handleTaskCompleted();
            } else {
                console.log('⚠️ Unknown status received:', status);
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
            
            // 🧹 LIMPIAR LISTENERS DE FIREBASE
            cleanupFirebaseListeners();
            
            const statusMessage = document.getElementById('statusMessage');
            const button = document.getElementById('callWaiterBtn');
            
            // 🎉 CAMBIO VISUAL INMEDIATO - BOTÓN SALE DE "ESPERANDO CONFIRMACIÓN"
            button.disabled = false;
            button.textContent = '🔔 Llamar Mozo';
            
            statusMessage.className = 'status-message status-success';
            statusMessage.textContent = '🎉 ¡CONFIRMADO! Tu mozo recibió la solicitud y está en camino';
            
            updateDebug();
            
            // Cleanup automático después de mostrar el mensaje de éxito
            setTimeout(() => {
                statusMessage.style.display = 'none';
                currentNotificationId = null;
                requestCount = 0;
                lastProcessedStatus = null; // Reset para siguiente llamada
                updateDebug();
            }, 4000);
        }

        function handleTaskCompleted() {
            clearInterval(pollingInterval);
            pollingInterval = null;
            
            // 🧹 LIMPIAR LISTENERS DE FIREBASE
            cleanupFirebaseListeners();
            
            const statusMessage = document.getElementById('statusMessage');
            const button = document.getElementById('callWaiterBtn');
            
            statusMessage.className = 'status-message status-success';
            statusMessage.textContent = '✅ ¡Servicio completado! Gracias por usar MozoQR';
            
            updateDebug();
            
            setTimeout(() => {
                button.disabled = false;
                button.textContent = '🔔 Llamar Mozo';
                statusMessage.style.display = 'none';
                currentNotificationId = null;
                requestCount = 0;
                lastProcessedStatus = null; // Reset para siguiente llamada
                updateDebug();
            }, 4000);
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
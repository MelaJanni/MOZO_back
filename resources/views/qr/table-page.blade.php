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
                        @php
                            // Usar la ruta Laravel para servir PDFs
                            $filename = basename($defaultMenu->file_path);
                            $menuUrl = route('menu.pdf', ['business_id' => $business->id, 'filename' => $filename]);
                        @endphp
                        
                        <!-- Mostrar PDF dinámico del admin -->
                        <div id="menu-pdf-container" style="position: relative; height: 600px; overflow: hidden;">
                            <iframe 
                                id="menu-pdf-iframe"
                                src="{{ $menuUrl }}"
                                class="menu-pdf"
                                title="Menú de {{ $business->name }}"
                                onload="handlePdfLoad()"
                                onerror="handlePdfError()">
                            </iframe>
                            
                            <!-- Botón para abrir en nueva pestaña -->
                            <div style="position: absolute; bottom: 10px; right: 10px; z-index: 10;">
                                <a href="{{ $menuUrl }}" 
                                   target="_blank" 
                                   style="background: #4a90e2; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                    📱 Abrir menú
                                </a>
                            </div>
                        </div>

                        <!-- Mensaje de fallback si el PDF no carga -->
                        <div id="menu-fallback" style="display: none; padding: 40px; text-align: center; color: #6c757d;">
                            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 10px; padding: 30px; margin: 20px 0;">
                                <h3 style="color: #856404; margin-bottom: 15px;">📋 Menú no disponible temporalmente</h3>
                                <p style="color: #856404; margin-bottom: 20px; font-size: 16px;">
                                    No pudimos cargar el menú digital en este momento.
                                </p>
                                <div style="background: #e3f2fd; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                    <p style="color: #1976d2; font-weight: 600; margin-bottom: 10px;">
                                        💡 ¿Qué puedes hacer?
                                    </p>
                                    <p style="color: #1976d2; margin: 0;">
                                        Llama a nuestro mozo usando el botón de abajo y te traeremos el menú físico de inmediato.
                                    </p>
                                </div>
                            </div>
                        </div>

                    @elseif($business->menu_pdf)
                        @php
                            // Fallback al campo menu_pdf antiguo
                            $filename = basename($business->menu_pdf);
                            $menuUrl = route('menu.pdf', ['business_id' => $business->id, 'filename' => $filename]);
                        @endphp
                        
                        <div id="menu-pdf-container" style="position: relative; height: 600px; overflow: hidden;">
                            <iframe 
                                id="menu-pdf-iframe"
                                src="{{ $menuUrl }}"
                                class="menu-pdf"
                                title="Menú de {{ $business->name }}"
                                onload="handlePdfLoad()"
                                onerror="handlePdfError()">
                            </iframe>
                            
                            <div style="position: absolute; bottom: 10px; right: 10px; z-index: 10;">
                                <a href="{{ $menuUrl }}" 
                                   target="_blank" 
                                   style="background: #4a90e2; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                    📱 Abrir menú
                                </a>
                            </div>
                        </div>

                        <div id="menu-fallback" style="display: none; padding: 40px; text-align: center; color: #6c757d;">
                            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 10px; padding: 30px; margin: 20px 0;">
                                <h3 style="color: #856404; margin-bottom: 15px;">📋 Menú no disponible temporalmente</h3>
                                <p style="color: #856404; margin-bottom: 20px; font-size: 16px;">
                                    No pudimos cargar el menú digital en este momento.
                                </p>
                                <div style="background: #e3f2fd; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                    <p style="color: #1976d2; font-weight: 600; margin-bottom: 10px;">
                                        💡 ¿Qué puedes hacer?
                                    </p>
                                    <p style="color: #1976d2; margin: 0;">
                                        Llama a nuestro mozo usando el botón de abajo y te traeremos el menú físico de inmediato.
                                    </p>
                                </div>
                            </div>
                        </div>

                    @else
                        <!-- No hay menú configurado -->
                        <div style="padding: 40px; text-align: center; color: #6c757d;">
                            <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 10px; padding: 30px; margin: 20px 0;">
                                <h3 style="color: #721c24; margin-bottom: 15px;">📋 Menú no configurado</h3>
                                <p style="color: #721c24; margin-bottom: 20px; font-size: 16px;">
                                    Este restaurante aún no ha subido su menú digital.
                                </p>
                                <div style="background: #e3f2fd; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                    <p style="color: #1976d2; font-weight: 600; margin-bottom: 10px;">
                                        💡 Solicita el menú físico
                                    </p>
                                    <p style="color: #1976d2; margin: 0;">
                                        Llama a nuestro mozo y te traerá el menú físico para que puedas revisar todas nuestras opciones.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <section class="call-waiter-section">
                <form method="POST" action="{{ route('waiter.call') }}">
                    @csrf
                    <input type="hidden" name="restaurant_id" value="{{ $business->id }}">
                    <input type="hidden" name="table_id" value="{{ $table->id }}">
                    <input type="hidden" name="message" value="Cliente solicita atención">
                    
                    <button type="submit" class="call-button">
                        🔔 Llamar Mozo
                    </button>
                </form>
                
                @if(session('success'))
                    <div class="status-message status-success" style="display: block;">
                        🎉 {{ session('success') }}
                        @if(session('notification_id'))
                            <div style="font-size: 14px; margin-top: 8px; opacity: 0.8;">
                                ⏳ Te notificaremos cuando el mozo confirme...
                            </div>
                        @endif
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="status-message status-error" style="display: block;">
                        ❌ {{ session('error') }}
                    </div>
                @endif
            </section>
        </main>

        <footer class="footer">
            <p>© {{ date('Y') }} {{ $business->name }}. Sistema de llamado QR.</p>
        </footer>
    </div>

    <!-- Firebase SDK para escuchar acknowledged -->
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-database-compat.js"></script>

    <script>
        // Solo escuchar Firebase si hay una solicitud pendiente
        const TABLE_ID = {{ $table->id }};
        let currentNotificationId = null;
        let firebaseListener = null;

        // Obtener ID de notificación desde la sesión si existe (después de form submit)
        @if(session('notification_id'))
            currentNotificationId = '{{ session('notification_id') }}';
            console.log('🔍 Notification ID desde sesión:', currentNotificationId);
            startFirebaseListener();
        @endif

        function startFirebaseListener() {
            console.log('🔥 Iniciando listener Firebase para acknowledged...');
            
            // Configuración Firebase
            const firebaseConfig = {
                projectId: "mozoqr-7d32c",
                apiKey: "AIzaSyDGJJKNfSSxD6YnXnNjwRb6VUtPSyGN5CM",
                authDomain: "mozoqr-7d32c.firebaseapp.com",
                databaseURL: "https://mozoqr-7d32c-default-rtdb.firebaseio.com",
                storageBucket: "mozoqr-7d32c.appspot.com"
            };
            
            // Inicializar Firebase
            if (!window.firebase || !window.firebase.apps.length) {
                firebase.initializeApp(firebaseConfig);
            }
            
            const database = firebase.database();
            
            // Escuchar cambios en mi solicitud específica usando estructura unificada
            const myCallRef = database.ref(`active_calls/${currentNotificationId}`);
            
            firebaseListener = myCallRef.on('value', (snapshot) => {
                const data = snapshot.val();
                console.log('🔍 Firebase data received:', data);
                
                if (data && data.status === 'acknowledged') {
                    console.log('🎉 ACKNOWLEDGED! Mozo confirmó la solicitud');
                    showAcknowledgedMessage(data.waiter?.name || data.waiter_name);
                    
                    // Detener listener
                    myCallRef.off('value', firebaseListener);
                    firebaseListener = null;
                } else if (data && data.status === 'completed') {
                    console.log('✅ COMPLETED! Llamada completada');
                    showCompletedMessage(data.waiter?.name || data.waiter_name);
                    
                    // Detener listener
                    myCallRef.off('value', firebaseListener);
                    firebaseListener = null;
                }
            });
        }

        function showAcknowledgedMessage(waiterName) {
            // Actualizar mensaje de éxito para mostrar que el mozo confirmó
            const successMessage = document.querySelector('.status-success');
            if (successMessage) {
                successMessage.innerHTML = `
                    <div style="font-size: 18px; font-weight: bold; color: #155724;">
                        🎉 ¡${waiterName || 'El mozo'} confirmó tu solicitud!
                    </div>
                    <div style="font-size: 16px; margin-top: 5px;">
                        🚶‍♂️ Llegará pronto a tu mesa
                    </div>
                `;
                
                // Hacer que el mensaje sea más visible
                successMessage.style.border = '2px solid #28a745';
                successMessage.style.animation = 'pulse 2s ease-in-out 3';
                
                // Notificación del navegador
                if (Notification.permission === 'granted') {
                    new Notification('¡Mozo confirmado!', {
                        body: `${waiterName || 'El mozo'} confirmó tu solicitud y está en camino`,
                        icon: '/favicon.ico',
                        tag: 'waiter-confirmed'
                    });
                }
            }
        }

        function showCompletedMessage(waiterName) {
            // Actualizar mensaje para mostrar que la llamada fue completada
            const successMessage = document.querySelector('.status-success');
            if (successMessage) {
                successMessage.innerHTML = `
                    <div style="font-size: 18px; font-weight: bold; color: #155724;">
                        ✅ ¡${waiterName || 'El mozo'} completó tu solicitud!
                    </div>
                    <div style="font-size: 16px; margin-top: 5px;">
                        👍 Servicio finalizado
                    </div>
                `;
                
                // Hacer que el mensaje sea más visible
                successMessage.style.border = '2px solid #28a745';
                successMessage.style.background = '#d4edda';
                
                // Notificación del navegador
                if (Notification.permission === 'granted') {
                    new Notification('¡Servicio completado!', {
                        body: `${waiterName || 'El mozo'} completó tu solicitud`,
                        icon: '/favicon.ico',
                        tag: 'waiter-completed'
                    });
                }
            }
        }

        // Solicitar permisos de notificación
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }

        // 📋 MANEJO DE ERRORES DEL PDF
        let pdfLoadAttempts = 0;
        const maxPdfLoadAttempts = 3;

        function handlePdfLoad() {
            console.log('✅ PDF cargado correctamente');
            // El PDF se cargó bien, no hacer nada
        }

        function handlePdfError() {
            console.log('❌ Error cargando PDF, intento:', pdfLoadAttempts + 1);
            pdfLoadAttempts++;
            
            if (pdfLoadAttempts >= maxPdfLoadAttempts) {
                showPdfFallback();
            }
        }

        function showPdfFallback() {
            console.log('📋 Mostrando mensaje de fallback del menú');
            
            // Ocultar el iframe del PDF
            const pdfContainer = document.getElementById('menu-pdf-container');
            const fallbackContainer = document.getElementById('menu-fallback');
            
            if (pdfContainer && fallbackContainer) {
                pdfContainer.style.display = 'none';
                fallbackContainer.style.display = 'block';
            }
        }

        // Detectar si el PDF no carga después de un tiempo
        setTimeout(() => {
            const iframe = document.getElementById('menu-pdf-iframe');
            if (iframe) {
                // Verificar si el iframe está realmente mostrando contenido
                try {
                    // Si el iframe no tiene contenido válido después de 5 segundos, mostrar fallback
                    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    if (!iframeDoc || iframeDoc.body.innerHTML.trim() === '') {
                        console.log('⏰ Timeout: PDF no cargó en 5 segundos');
                        showPdfFallback();
                    }
                } catch (e) {
                    // Si hay un error de CORS o acceso, asumimos que el PDF no cargó
                    console.log('🔒 No se puede acceder al contenido del iframe, probablemente PDF no disponible');
                    // No mostrar fallback inmediatamente por CORS, solo si realmente falla
                }
            }
        }, 5000); // 5 segundos de timeout
    </script>
</body>
</html>
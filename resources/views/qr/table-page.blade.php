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
                    @if($business->menu_pdf)
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
            button.textContent = '📞 Llamando...';
            
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
                    statusMessage.className = 'status-message status-pending';
                    statusMessage.textContent = 'Solicitud enviada. El mozo ha sido notificado...';
                    
                    startPolling();
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

        function startPolling() {
            if (pollingInterval) clearInterval(pollingInterval);
            
            pollingInterval = setInterval(() => {
                if (!currentNotificationId) return;
                
                fetch(`${FRONTEND_URL}/api/waiter-notifications/${currentNotificationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.status === 'acknowledged') {
                        clearInterval(pollingInterval);
                        
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
                })
                .catch(error => {
                    console.error('Polling error:', error);
                });
            }, 3000);
        }
    </script>
</body>
</html>
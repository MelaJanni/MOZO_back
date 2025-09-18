<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contraseña Restablecida - MOZO</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 480px;
            width: 100%;
            overflow: hidden;
            text-align: center;
        }

        .header {
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            color: white;
            padding: 40px 30px;
        }

        .success-icon {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .content {
            padding: 40px 30px;
        }

        .message {
            font-size: 16px;
            color: #374151;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .instructions {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }

        .instructions h3 {
            color: #166534;
            font-size: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .instructions ul {
            color: #166534;
            margin-left: 20px;
        }

        .instructions li {
            margin-bottom: 8px;
            font-size: 14px;
        }

        .app-download {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .app-download h3 {
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 12px;
        }

        .app-download p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 16px;
        }

        .download-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #1e293b;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .download-btn:hover {
            background: #334155;
            transform: translateY(-2px);
        }

        .footer {
            padding: 20px 30px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }

        .footer a {
            color: #10b981;
            text-decoration: none;
            font-weight: 500;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container {
                margin: 10px;
            }

            .header {
                padding: 30px 20px;
            }

            .content {
                padding: 30px 20px;
            }

            .footer {
                padding: 20px;
            }

            .download-buttons {
                flex-direction: column;
            }

            .success-icon {
                font-size: 48px;
            }

            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="success-icon">✅</span>
            <h1>¡Contraseña Cambiada!</h1>
            <p>Tu contraseña se ha restablecido exitosamente</p>
        </div>

        <div class="content">
            <div class="message">
                {{ $message }}
            </div>

            <div class="instructions">
                <h3>📱 Próximos pasos:</h3>
                <ul>
                    <li><strong>Abre la aplicación MOZO</strong> en tu dispositivo móvil</li>
                    <li><strong>Inicia sesión</strong> con tu email y nueva contraseña</li>
                    <li><strong>¡Listo!</strong> Ya puedes usar todas las funciones</li>
                </ul>
            </div>

            <div class="app-download">
                <h3>¿No tienes la app instalada?</h3>
                <p>Descarga la aplicación MOZO para acceder a tu cuenta:</p>
                <div class="download-buttons">
                    <a href="#" class="download-btn">
                        🤖 Google Play
                    </a>
                    <a href="#" class="download-btn">
                        🍎 App Store
                    </a>
                </div>
            </div>

            <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                <p style="color: #92400e; font-size: 14px; margin: 0;">
                    <strong>💡 Consejo de seguridad:</strong> Guarda tu nueva contraseña en un lugar seguro y no la compartas con nadie.
                </p>
            </div>
        </div>

        <div class="footer">
            <p>¿Problemas para iniciar sesión? <a href="mailto:soporte@mozoqr.com">Contacta soporte</a></p>
        </div>
    </div>

    <script>
        // Auto-redirect después de 10 segundos (opcional)
        // setTimeout(function() {
        //     window.location.href = 'https://mozoqr.com';
        // }, 10000);
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enlace Enviado - MOZO</title>
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
            margin-bottom: 20px;
        }

        .email-highlight {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            color: #0369a1;
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

        .warning-box {
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .warning-box p {
            color: #92400e;
            font-size: 14px;
            margin: 0;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
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
            <span class="success-icon">📧</span>
            <h1>¡Enlace Enviado!</h1>
            <p>Revisa tu correo electrónico</p>
        </div>

        <div class="content">
            <div class="message">
                {{ $message }}
            </div>

            <div class="email-highlight">
                📮 {{ $email }}
            </div>

            <div class="instructions">
                <h3>📋 Próximos pasos:</h3>
                <ul>
                    <li><strong>Revisa tu bandeja de entrada</strong> - Busca un email de MOZO</li>
                    <li><strong>Verifica spam/promociones</strong> - A veces llega a estas carpetas</li>
                    <li><strong>Haz clic en el enlace</strong> - Te llevará a una página para cambiar tu contraseña</li>
                    <li><strong>Crea una contraseña nueva</strong> - Asegúrate de que sea segura</li>
                </ul>
            </div>

            <div class="warning-box">
                <p>
                    <strong>⏰ Importante:</strong> El enlace expira en 60 minutos por seguridad. Si no lo usas a tiempo, deberás solicitar uno nuevo.
                </p>
            </div>

            <div class="action-buttons">
                <a href="{{ route('password.request') }}" class="btn btn-secondary">
                    🔄 Solicitar Nuevo Enlace
                </a>
                <a href="mailto:soporte@mozoqr.com" class="btn btn-secondary">
                    💬 Contactar Soporte
                </a>
            </div>

            <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 16px;">
                <p style="color: #0369a1; font-size: 14px; margin: 0;">
                    <strong>💡 Consejo:</strong> Mientras esperas el email, puedes revisar si tienes la aplicación MOZO actualizada en tu dispositivo.
                </p>
            </div>
        </div>

        <div class="footer">
            <p>¿No llegó el email? <a href="{{ route('password.request') }}">Intentar de nuevo</a></p>
            <p>¿Necesitas ayuda? <a href="mailto:soporte@mozoqr.com">Contacta soporte</a></p>
        </div>
    </div>

    <script>
        // Auto-refresh después de 5 minutos para permitir re-envío
        setTimeout(function() {
            const refreshButton = document.createElement('div');
            refreshButton.innerHTML = '<a href="{{ route('password.request') }}" class="btn btn-primary">🔄 Solicitar Nuevo Enlace</a>';
            refreshButton.style.marginTop = '20px';
            document.querySelector('.action-buttons').appendChild(refreshButton);
        }, 300000); // 5 minutos
    </script>
</body>
</html>
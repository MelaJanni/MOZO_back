<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Restablecer Contraseña</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 40px 30px;
        }

        .error-icon {
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

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }

        .error-message p {
            color: #dc2626;
            font-size: 16px;
            line-height: 1.6;
            margin: 0;
        }

        .solutions {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }

        .solutions h3 {
            color: #0369a1;
            font-size: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .solutions ul {
            color: #0369a1;
            margin-left: 20px;
        }

        .solutions li {
            margin-bottom: 8px;
            font-size: 14px;
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
            color: #ef4444;
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

            .error-icon {
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
            <span class="error-icon">❌</span>
            <h1>Error de Restablecimiento</h1>
            <p>No pudimos procesar tu solicitud</p>
        </div>

        <div class="content">
            <div class="error-message">
                <p>{{ $error }}</p>
            </div>

            <div class="solutions">
                <h3>💡 ¿Qué puedes hacer?</h3>
                <ul>
                    <li><strong>Solicita un nuevo enlace</strong> - Los enlaces expiran después de 60 minutos</li>
                    <li><strong>Revisa tu email</strong> - Asegúrate de usar el enlace más reciente</li>
                    <li><strong>Verifica tu correo</strong> - Confirma que es la dirección correcta</li>
                    <li><strong>Contacta soporte</strong> - Si el problema persiste</li>
                </ul>
            </div>

            <div class="action-buttons">
                <a href="{{ route('password.request') }}" class="btn btn-primary">
                    📧 Solicitar Nuevo Enlace
                </a>
                <a href="mailto:soporte@mozoqr.com" class="btn btn-secondary">
                    💬 Contactar Soporte
                </a>
            </div>

            <div style="background: #fffbeb; border: 1px solid #fbbf24; border-radius: 8px; padding: 16px;">
                <p style="color: #92400e; font-size: 14px; margin: 0;">
                    <strong>⏰ Recuerda:</strong> Los enlaces de restablecimiento expiran después de 60 minutos por seguridad.
                </p>
            </div>
        </div>

        <div class="footer">
            <p>¿Necesitas ayuda? <a href="mailto:soporte@mozoqr.com">Contacta nuestro soporte</a></p>
        </div>
    </div>
</body>
</html>
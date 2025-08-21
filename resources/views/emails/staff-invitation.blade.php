<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitación de Trabajo - {{ $businessName }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e74c3c;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 10px;
        }
        .invitation-btn {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🍽️ MOZO QR</div>
            <h1>¡Invitación de Trabajo!</h1>
        </div>

        <p>Hola <strong>{{ $staffName }}</strong>,</p>

        <p>¡Tenemos excelentes noticias! Has sido invitado/a a formar parte del equipo de <strong>{{ $businessName }}</strong> como <strong>{{ $position }}</strong>.</p>

        <div class="details">
            <h3>📋 Detalles de la Invitación:</h3>
            <ul>
                <li><strong>Negocio:</strong> {{ $businessName }}</li>
                <li><strong>Posición:</strong> {{ $position }}</li>
                <li><strong>Dirección:</strong> {{ $businessAddress }}</li>
                <li><strong>Teléfono:</strong> {{ $businessPhone }}</li>
            </ul>
        </div>

        <div style="text-align: center;">
            <a href="{{ $invitationUrl }}" class="invitation-btn">
                ✅ Aceptar Invitación
            </a>
        </div>

        <div class="warning">
            <strong>⏰ Importante:</strong> Esta invitación expira el <strong>{{ $expirationDate }}</strong>. 
            Asegúrate de aceptarla antes de esa fecha.
        </div>

        <h3>📱 ¿Cómo aceptar la invitación?</h3>
        <ol>
            <li>Haz clic en el botón "Aceptar Invitación" arriba</li>
            <li>Si no tienes cuenta, regístrate con tu email</li>
            <li>Si ya tienes cuenta, inicia sesión</li>
            <li>Completa tu perfil con la información requerida</li>
            <li>¡Listo! Ya formarás parte del equipo</li>
        </ol>

        <h3>📋 Información Requerida:</h3>
        <p>Para completar tu registro como mozo, necesitarás proporcionar:</p>
        <ul>
            <li>Foto de perfil</li>
            <li>Información personal (altura, peso, fecha de nacimiento)</li>
            <li>Años de experiencia</li>
            <li>Habilidades y especialidades</li>
            <li>Horarios disponibles</li>
            <li>Ubicación actual</li>
        </ul>

        <div class="details">
            <h3>🔗 Enlace Alternativo:</h3>
            <p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
            <p style="word-break: break-all; background-color: #f1f1f1; padding: 10px; border-radius: 3px;">
                {{ $invitationUrl }}
            </p>
        </div>

        <div class="details">
            <h3>🔑 Código de Invitación:</h3>
            <p>Si necesitas el código manual: <strong>{{ $invitationToken }}</strong></p>
        </div>

        <p>Si tienes alguna pregunta o necesitas ayuda, no dudes en contactar directamente con {{ $businessName }} al {{ $businessPhone }}.</p>

        <p>¡Esperamos verte pronto en el equipo!</p>

        <div class="footer">
            <p>Este email fue enviado por el sistema MOZO QR</p>
            <p>{{ $businessName }} - {{ $businessAddress }}</p>
            <p>Si no esperabas esta invitación, puedes ignorar este email.</p>
        </div>
    </div>
</body>
</html>
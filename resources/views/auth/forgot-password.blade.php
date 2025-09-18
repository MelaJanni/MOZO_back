<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - MOZO</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        }

        .header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
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

        .form-container {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .form-control:focus {
            outline: none;
            border-color: #4facfe;
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
        }

        .btn {
            width: 100%;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .error ul {
            list-style: none;
        }

        .error li {
            margin-bottom: 4px;
        }

        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .info-box h4 {
            color: #0369a1;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .info-box ul {
            color: #0369a1;
            font-size: 13px;
            margin-left: 16px;
        }

        .info-box li {
            margin-bottom: 4px;
        }

        .footer {
            text-align: center;
            padding: 20px 30px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }

        .footer a {
            color: #4facfe;
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

            .form-container {
                padding: 30px 20px;
            }

            .footer {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔑 Recuperar Contraseña</h1>
            <p>Te enviaremos un enlace para restablecer tu contraseña</p>
        </div>

        <div class="form-container">
            @if ($errors->any())
                <div class="error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="{{ old('email') }}"
                        required
                        placeholder="Ingresa tu email registrado"
                        autocomplete="email"
                    >
                </div>

                <button type="submit" class="btn">
                    📧 Enviar Enlace de Recuperación
                </button>
            </form>

            <div class="info-box">
                <h4>ℹ️ ¿Cómo funciona?</h4>
                <ul>
                    <li>Ingresa el email asociado a tu cuenta</li>
                    <li>Recibirás un correo con un enlace especial</li>
                    <li>Haz clic en el enlace para crear una nueva contraseña</li>
                    <li>El enlace expira en 60 minutos por seguridad</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <p>¿Recordaste tu contraseña? <a href="/admin/login">Iniciar sesión</a></p>
            <p>¿Problemas? <a href="mailto:soporte@mozoqr.com">Contacta soporte</a></p>
        </div>
    </div>
</body>
</html>
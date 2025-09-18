<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - MOZO</title>
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

        .password-requirements {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 16px;
            margin-top: 8px;
        }

        .password-requirements h4 {
            color: #0369a1;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .password-requirements ul {
            color: #0369a1;
            font-size: 13px;
            margin-left: 16px;
        }

        .password-requirements li {
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

        .password-toggle {
            position: relative;
        }

        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 14px;
            padding: 4px;
        }

        .password-toggle-btn:hover {
            color: #4facfe;
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
            <h1>🔐 Restablecer Contraseña</h1>
            <p>Ingresa tu nueva contraseña para acceder a tu cuenta</p>
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

            <form method="POST" action="{{ route('password.reset.submit') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="{{ old('email', $email) }}"
                        required
                        readonly
                        style="background: #f3f4f6; color: #6b7280;"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Nueva Contraseña</label>
                    <div class="password-toggle">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            required
                            minlength="8"
                            placeholder="Ingresa tu nueva contraseña"
                        >
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('password')">
                            👁️
                        </button>
                    </div>
                    <div class="password-requirements">
                        <h4>Requisitos de la contraseña:</h4>
                        <ul>
                            <li>Mínimo 8 caracteres</li>
                            <li>Se recomienda usar letras, números y símbolos</li>
                            <li>Evita usar información personal</li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirmar Nueva Contraseña</label>
                    <div class="password-toggle">
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control"
                            required
                            minlength="8"
                            placeholder="Confirma tu nueva contraseña"
                        >
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('password_confirmation')">
                            👁️
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn">
                    ✅ Cambiar Contraseña
                </button>
            </form>
        </div>

        <div class="footer">
            <p>¿Necesitas ayuda? <a href="mailto:soporte@mozoqr.com">Contacta soporte</a></p>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;

            if (field.type === 'password') {
                field.type = 'text';
                button.textContent = '🙈';
            } else {
                field.type = 'password';
                button.textContent = '👁️';
            }
        }

        // Validación en tiempo real
        document.getElementById('password_confirmation').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmation = this.value;

            if (confirmation && password !== confirmation) {
                this.style.borderColor = '#ef4444';
            } else {
                this.style.borderColor = '#e5e7eb';
            }
        });

        // Validación de fuerza de contraseña
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const isStrong = password.length >= 8;

            if (password && !isStrong) {
                this.style.borderColor = '#f59e0b';
            } else if (isStrong) {
                this.style.borderColor = '#10b981';
            } else {
                this.style.borderColor = '#e5e7eb';
            }
        });
    </script>
</body>
</html>
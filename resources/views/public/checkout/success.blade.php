<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Pago Exitoso! - MOZO QR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-blue-600">MOZO QR</h1>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
            <!-- Ícono de éxito -->
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-check text-green-600 text-3xl"></i>
            </div>

            <!-- Mensaje principal -->
            <h1 class="text-3xl font-bold text-gray-900 mb-4">¡Pago Exitoso!</h1>
            <p class="text-xl text-gray-600 mb-8">
                Tu cuenta ha sido creada y tu suscripción está activa
            </p>

            <!-- Información de la cuenta -->
            <div class="bg-blue-50 rounded-lg p-6 mb-8">
                <h2 class="text-lg font-semibold text-blue-900 mb-4">¿Qué sigue ahora?</h2>
                <div class="space-y-3 text-left">
                    <div class="flex items-start">
                        <i class="fas fa-envelope text-blue-600 mt-1 mr-3"></i>
                        <div>
                            <div class="font-medium text-blue-900">Revisa tu email</div>
                            <div class="text-sm text-blue-700">Te hemos enviado los detalles de tu cuenta y links importantes</div>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-mobile-alt text-blue-600 mt-1 mr-3"></i>
                        <div>
                            <div class="font-medium text-blue-900">Descarga la app móvil</div>
                            <div class="text-sm text-blue-700">Para que tus mozos gestionen las mesas desde sus teléfonos</div>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-cog text-blue-600 mt-1 mr-3"></i>
                        <div>
                            <div class="font-medium text-blue-900">Configura tu restaurante</div>
                            <div class="text-sm text-blue-700">Agrega mesas, menús y personal desde el panel de administración</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="space-y-4">
                <a href="/admin"
                   class="w-full bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-300 inline-block">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Ir al Panel de Administración
                </a>

                <div class="grid grid-cols-2 gap-4">
                    <a href="#"
                       class="bg-gray-100 text-gray-700 font-semibold py-3 px-4 rounded-lg hover:bg-gray-200 transition duration-300 inline-block">
                        <i class="fab fa-apple mr-2"></i>
                        Descargar iOS
                    </a>
                    <a href="#"
                       class="bg-gray-100 text-gray-700 font-semibold py-3 px-4 rounded-lg hover:bg-gray-200 transition duration-300 inline-block">
                        <i class="fab fa-android mr-2"></i>
                        Descargar Android
                    </a>
                </div>
            </div>

            <!-- Información de soporte -->
            <div class="mt-8 pt-8 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">¿Necesitas ayuda?</h3>
                <div class="flex justify-center space-x-8 text-sm">
                    <a href="mailto:soporte@mozoqr.com" class="text-blue-600 hover:text-blue-700">
                        <i class="fas fa-envelope mr-1"></i>
                        Email de Soporte
                    </a>
                    <a href="tel:+5491123456789" class="text-blue-600 hover:text-blue-700">
                        <i class="fas fa-phone mr-1"></i>
                        Llamar Soporte
                    </a>
                    <a href="#" class="text-blue-600 hover:text-blue-700">
                        <i class="fas fa-book mr-1"></i>
                        Centro de Ayuda
                    </a>
                </div>
            </div>

            <!-- Mensaje de bienvenida -->
            <div class="mt-8 bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg p-6">
                <h3 class="text-xl font-bold mb-2">¡Bienvenido a MOZO QR!</h3>
                <p class="text-blue-100">
                    Estamos emocionados de ayudarte a digitalizar tu restaurante y mejorar la experiencia de tus clientes.
                </p>
            </div>
        </div>

        <!-- Recursos adicionales -->
        <div class="mt-8 grid md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <i class="fas fa-play-circle text-blue-600 text-3xl mb-4"></i>
                <h4 class="font-semibold text-gray-900 mb-2">Video Tutorial</h4>
                <p class="text-sm text-gray-600 mb-4">Aprende a configurar tu cuenta en 5 minutos</p>
                <a href="#" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Ver Video</a>
            </div>

            <div class="bg-white rounded-lg shadow p-6 text-center">
                <i class="fas fa-users text-green-600 text-3xl mb-4"></i>
                <h4 class="font-semibold text-gray-900 mb-2">Comunidad</h4>
                <p class="text-sm text-gray-600 mb-4">Únete a otros restaurantes que usan MOZO QR</p>
                <a href="#" class="text-green-600 hover:text-green-700 text-sm font-medium">Unirse</a>
            </div>

            <div class="bg-white rounded-lg shadow p-6 text-center">
                <i class="fas fa-calendar text-purple-600 text-3xl mb-4"></i>
                <h4 class="font-semibold text-gray-900 mb-2">Onboarding</h4>
                <p class="text-sm text-gray-600 mb-4">Agenda una sesión personalizada</p>
                <a href="#" class="text-purple-600 hover:text-purple-700 text-sm font-medium">Agendar</a>
            </div>
        </div>
    </div>

    <script>
        // Confetti animation (simple)
        function createConfetti() {
            const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];
            const confettiCount = 100;

            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.width = '10px';
                confetti.style.height = '10px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = '-10px';
                confetti.style.zIndex = '1000';
                confetti.style.pointerEvents = 'none';
                confetti.style.borderRadius = '50%';

                document.body.appendChild(confetti);

                const duration = Math.random() * 3000 + 2000;
                const fallDistance = window.innerHeight + 20;

                confetti.animate([
                    { transform: 'translateY(0px) rotate(0deg)', opacity: 1 },
                    { transform: `translateY(${fallDistance}px) rotate(360deg)`, opacity: 0 }
                ], {
                    duration: duration,
                    easing: 'linear'
                }).addEventListener('finish', () => {
                    confetti.remove();
                });
            }
        }

        // Trigger confetti on page load
        window.addEventListener('load', () => {
            setTimeout(createConfetti, 500);
        });
    </script>
</body>
</html>
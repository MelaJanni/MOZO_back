<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Pago Exitoso! - MOZO QR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #fbbf24;
            animation: confetti-fall 3s linear infinite;
        }
        @keyframes confetti-fall {
            0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
    </style>
</head>
<body class="bg-gray-50 overflow-x-hidden">
    <!-- Confetti Elements -->
    <div class="confetti" style="left: 10%; animation-delay: 0s; background: #ef4444;"></div>
    <div class="confetti" style="left: 20%; animation-delay: 0.5s; background: #3b82f6;"></div>
    <div class="confetti" style="left: 30%; animation-delay: 1s; background: #10b981;"></div>
    <div class="confetti" style="left: 40%; animation-delay: 1.5s; background: #f59e0b;"></div>
    <div class="confetti" style="left: 50%; animation-delay: 0.2s; background: #8b5cf6;"></div>
    <div class="confetti" style="left: 60%; animation-delay: 0.7s; background: #ef4444;"></div>
    <div class="confetti" style="left: 70%; animation-delay: 1.2s; background: #06b6d4;"></div>
    <div class="confetti" style="left: 80%; animation-delay: 1.7s; background: #84cc16;"></div>
    <div class="confetti" style="left: 90%; animation-delay: 0.3s; background: #f97316;"></div>

    <!-- Header -->
    <header class="gradient-bg text-white py-16">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-4xl font-bold mb-4">¡Pago Exitoso!</h1>
                <p class="text-xl opacity-90">Tu suscripción ha sido activada correctamente</p>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-12">
        <div class="max-w-3xl mx-auto">
            <!-- Success Message -->
            <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 text-center">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">¡Bienvenido a MOZO QR!</h2>
                    <p class="text-gray-600">
                        Tu suscripción está activa y ya puedes comenzar a disfrutar de todos los beneficios.
                    </p>
                </div>

                @if($subscription)
                <!-- Subscription Details -->
                <div class="bg-green-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-green-800 mb-4">Detalles de tu Suscripción</h3>
                    <div class="grid md:grid-cols-2 gap-4 text-left">
                        <div>
                            <span class="text-green-700 font-medium">Plan:</span>
                            <p class="text-green-900 font-semibold">{{ $subscription->plan->name }}</p>
                        </div>
                        <div>
                            <span class="text-green-700 font-medium">Precio:</span>
                            <p class="text-green-900 font-semibold">${{ number_format($subscription->price, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <span class="text-green-700 font-medium">Válido hasta:</span>
                            <p class="text-green-900 font-semibold">{{ $subscription->current_period_end->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <span class="text-green-700 font-medium">Estado:</span>
                            <p class="text-green-900 font-semibold">✅ Activo</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Next Steps -->
                <div class="text-left">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Próximos pasos:</h3>
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <div class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-3 mt-1 text-sm font-bold">1</div>
                            <div>
                                <h4 class="font-medium text-gray-900">Configura tu negocio</h4>
                                <p class="text-gray-600 text-sm">Completa la información de tu restaurante y personaliza tu menú</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-3 mt-1 text-sm font-bold">2</div>
                            <div>
                                <h4 class="font-medium text-gray-900">Genera tus códigos QR</h4>
                                <p class="text-gray-600 text-sm">Crea códigos QR únicos para cada mesa de tu restaurante</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-3 mt-1 text-sm font-bold">3</div>
                            <div>
                                <h4 class="font-medium text-gray-900">Descarga la app móvil</h4>
                                <p class="text-gray-600 text-sm">Gestiona pedidos y notificaciones desde tu smartphone</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="grid md:grid-cols-2 gap-4 mb-8">
                <a href="{{ url('/admin') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-lg text-center transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Ir al Panel de Administración
                </a>

                <a href="#"
                   class="bg-green-600 hover:bg-green-700 text-white font-semibold py-4 px-6 rounded-lg text-center transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.108"></path>
                    </svg>
                    Obtener Soporte
                </a>
            </div>

            <!-- Support Information -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-blue-600 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h3 class="text-lg font-semibold text-blue-800 mb-2">¿Necesitas ayuda?</h3>
                        <p class="text-blue-700 mb-3">
                            Nuestro equipo está listo para ayudarte a configurar tu sistema y aprovechar al máximo todas las funcionalidades.
                        </p>
                        <div class="space-y-2">
                            <p class="text-blue-600">
                                📧 <strong>Email:</strong> soporte@mozoqr.com
                            </p>
                            <p class="text-blue-600">
                                📱 <strong>WhatsApp:</strong> +54 9 11 1234-5678
                            </p>
                            <p class="text-blue-600">
                                🕒 <strong>Horario:</strong> Lunes a Viernes, 9:00 - 18:00 hs
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; {{ date('Y') }} MOZO QR. Todos los derechos reservados.</p>
            <p class="text-gray-400 mt-2">¡Gracias por confiar en nosotros!</p>
        </div>
    </footer>
</body>
</html>
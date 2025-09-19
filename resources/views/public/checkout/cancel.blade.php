<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Cancelado - MOZO QR</title>
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
            <!-- Ícono de cancelación -->
            <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-times text-orange-600 text-3xl"></i>
            </div>

            <!-- Mensaje principal -->
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Pago Cancelado</h1>
            <p class="text-xl text-gray-600 mb-8">
                No te preocupes, no se realizó ningún cargo a tu cuenta
            </p>

            <!-- Información sobre el proceso -->
            <div class="bg-blue-50 rounded-lg p-6 mb-8">
                <h2 class="text-lg font-semibold text-blue-900 mb-4">¿Qué pasó?</h2>
                <div class="space-y-3 text-left text-sm text-blue-700">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-600 mt-0.5 mr-3"></i>
                        <div>El proceso de pago fue interrumpido o cancelado</div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-shield-alt text-blue-600 mt-0.5 mr-3"></i>
                        <div>No se realizó ningún cargo a tu tarjeta o cuenta</div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-clock text-blue-600 mt-0.5 mr-3"></i>
                        <div>Puedes intentar nuevamente cuando estés listo</div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="space-y-4">
                <a href="{{ route('public.plans.pricing') }}"
                   class="w-full bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-300 inline-block">
                    <i class="fas fa-redo mr-2"></i>
                    Intentar Nuevamente
                </a>

                <a href="{{ route('public.plans.pricing') }}"
                   class="w-full bg-gray-100 text-gray-700 font-semibold py-3 px-6 rounded-lg hover:bg-gray-200 transition duration-300 inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver a los Planes
                </a>
            </div>

            <!-- Razones comunes de cancelación -->
            <div class="mt-8 pt-8 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Razones comunes de cancelación</h3>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="font-medium text-gray-900 mb-2">
                            <i class="fas fa-credit-card text-gray-600 mr-2"></i>
                            Problemas con la tarjeta
                        </div>
                        <ul class="text-gray-600 space-y-1">
                            <li>• Fondos insuficientes</li>
                            <li>• Tarjeta vencida</li>
                            <li>• Límite excedido</li>
                        </ul>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="font-medium text-gray-900 mb-2">
                            <i class="fas fa-globe text-gray-600 mr-2"></i>
                            Problemas técnicos
                        </div>
                        <ul class="text-gray-600 space-y-1">
                            <li>• Conexión interrumpida</li>
                            <li>• Timeout de sesión</li>
                            <li>• Error del navegador</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Alternativas de pago -->
            <div class="mt-8 bg-gradient-to-r from-gray-100 to-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Métodos de pago alternativos</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <i class="fas fa-university text-blue-600 text-2xl mb-2"></i>
                        <div class="font-medium text-gray-900">Transferencia</div>
                        <div class="text-sm text-gray-600">Bancaria</div>
                    </div>
                    <div class="text-center">
                        <i class="fab fa-whatsapp text-green-600 text-2xl mb-2"></i>
                        <div class="font-medium text-gray-900">WhatsApp</div>
                        <div class="text-sm text-gray-600">Asistencia personal</div>
                    </div>
                </div>
            </div>

            <!-- Información de soporte -->
            <div class="mt-8 pt-8 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">¿Necesitas ayuda?</h3>
                <p class="text-gray-600 mb-4">
                    Nuestro equipo está aquí para ayudarte a completar tu suscripción
                </p>
                <div class="flex justify-center space-x-8 text-sm">
                    <a href="mailto:soporte@mozoqr.com" class="text-blue-600 hover:text-blue-700">
                        <i class="fas fa-envelope mr-1"></i>
                        soporte@mozoqr.com
                    </a>
                    <a href="tel:+5491123456789" class="text-blue-600 hover:text-blue-700">
                        <i class="fas fa-phone mr-1"></i>
                        +54 911 2345-6789
                    </a>
                    <a href="https://wa.me/5491123456789" class="text-green-600 hover:text-green-700" target="_blank">
                        <i class="fab fa-whatsapp mr-1"></i>
                        WhatsApp
                    </a>
                </div>
            </div>

            <!-- Testimonios o garantías -->
            <div class="mt-8 bg-blue-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-4">¿Por qué elegir MOZO QR?</h3>
                <div class="grid md:grid-cols-3 gap-4 text-sm">
                    <div class="text-center">
                        <i class="fas fa-shield-alt text-blue-600 text-xl mb-2"></i>
                        <div class="font-medium text-blue-900">Seguro</div>
                        <div class="text-blue-700">Pagos protegidos</div>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-undo text-blue-600 text-xl mb-2"></i>
                        <div class="font-medium text-blue-900">Flexible</div>
                        <div class="text-blue-700">Cancela cuando quieras</div>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-headset text-blue-600 text-xl mb-2"></i>
                        <div class="font-medium text-blue-900">Soporte</div>
                        <div class="text-blue-700">24/7 disponible</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enlaces adicionales -->
        <div class="mt-8 text-center">
            <div class="space-x-6 text-sm text-gray-600">
                <a href="#" class="hover:text-blue-600">Términos de Servicio</a>
                <a href="#" class="hover:text-blue-600">Política de Privacidad</a>
                <a href="#" class="hover:text-blue-600">Centro de Ayuda</a>
            </div>
        </div>
    </div>
</body>
</html>
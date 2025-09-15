<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Cancelado - MOZO QR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg text-white py-16">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h1 class="text-4xl font-bold mb-4">Pago Cancelado</h1>
                <p class="text-xl opacity-90">No se realizó ningún cargo a tu cuenta</p>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-12">
        <div class="max-w-2xl mx-auto">
            <!-- Cancel Message -->
            <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 text-center">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">El proceso de pago fue cancelado</h2>
                <p class="text-gray-600 mb-6">
                    No te preocupes, no se realizó ningún cargo a tu cuenta.
                    Puedes intentar nuevamente cuando estés listo.
                </p>

                <!-- Reasons for cancellation -->
                <div class="bg-yellow-50 rounded-lg p-6 mb-6 text-left">
                    <h3 class="text-lg font-semibold text-yellow-800 mb-3">Posibles razones del problema:</h3>
                    <ul class="text-yellow-700 space-y-2">
                        <li class="flex items-start">
                            <span class="text-yellow-600 mr-2">•</span>
                            Cerraste la ventana del pago antes de completarlo
                        </li>
                        <li class="flex items-start">
                            <span class="text-yellow-600 mr-2">•</span>
                            Hubo un problema temporal con el procesador de pagos
                        </li>
                        <li class="flex items-start">
                            <span class="text-yellow-600 mr-2">•</span>
                            Tu tarjeta fue rechazada o tiene fondos insuficientes
                        </li>
                        <li class="flex items-start">
                            <span class="text-yellow-600 mr-2">•</span>
                            Decidiste cancelar el proceso voluntariamente
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-4">
                    <a href="{{ route('checkout.index') }}"
                       class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200 inline-block">
                        Intentar Nuevamente
                    </a>

                    <div class="grid md:grid-cols-2 gap-4">
                        <a href="#"
                           class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-lg transition-colors duration-200 inline-block">
                            Ver Otros Planes
                        </a>

                        <a href="#"
                           class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200 inline-block">
                            Contactar Soporte
                        </a>
                    </div>
                </div>
            </div>

            <!-- Alternative Payment Options -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Métodos de pago alternativos</h3>
                <p class="text-gray-600 mb-4">
                    Si continúas teniendo problemas con el pago en línea,
                    puedes usar estos métodos alternativos:
                </p>

                <div class="space-y-4">
                    <div class="flex items-center p-3 border border-gray-200 rounded-lg">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900">Transferencia Bancaria</h4>
                            <p class="text-sm text-gray-600">Pago manual con activación en 24hs</p>
                        </div>
                        <a href="{{ route('checkout.index') }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                            Usar →
                        </a>
                    </div>

                    <div class="flex items-center p-3 border border-gray-200 rounded-lg">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.108"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900">Contactar por WhatsApp</h4>
                            <p class="text-sm text-gray-600">Te ayudamos con el proceso de pago</p>
                        </div>
                        <a href="https://wa.me/5491112345678?text=Hola%2C%20tuve%20problemas%20con%20el%20pago%20en%20línea"
                           target="_blank"
                           class="text-blue-600 hover:text-blue-800 font-semibold">
                            Contactar →
                        </a>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="bg-gray-50 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Preguntas frecuentes</h3>

                <div class="space-y-4">
                    <div>
                        <h4 class="font-medium text-gray-900 mb-1">¿Se realizó algún cargo a mi tarjeta?</h4>
                        <p class="text-gray-600 text-sm">
                            No, cuando cancelas el pago no se realiza ningún cargo.
                            Si ves algún cargo temporal, será liberado automáticamente por tu banco.
                        </p>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-900 mb-1">¿Puedo usar otra tarjeta?</h4>
                        <p class="text-gray-600 text-sm">
                            Sí, puedes intentar con otra tarjeta o método de pago.
                            Simplemente regresa a la página de checkout.
                        </p>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-900 mb-1">¿El precio puede cambiar si espero?</h4>
                        <p class="text-gray-600 text-sm">
                            Los precios son estables, pero si tienes un cupón de descuento
                            verifica su fecha de vencimiento.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; {{ date('Y') }} MOZO QR. Todos los derechos reservados.</p>
            <p class="text-gray-400 mt-2">¿Necesitas ayuda? Contáctanos: soporte@mozoqr.com</p>
        </div>
    </footer>
</body>
</html>
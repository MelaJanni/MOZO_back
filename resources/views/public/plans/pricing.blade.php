<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planes y Precios - MOZO QR</title>
    <meta name="description" content="Elige el plan perfecto para tu restaurante. Gestiona mesas, menús y pedidos con MOZO QR.">
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
                <nav class="hidden md:flex space-x-8">
                    <a href="#features" class="text-gray-700 hover:text-blue-600">Características</a>
                    <a href="#pricing" class="text-gray-700 hover:text-blue-600">Precios</a>
                    <a href="#contact" class="text-gray-700 hover:text-blue-600">Contacto</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl md:text-6xl font-bold mb-6">
                Digitaliza tu Restaurante
            </h2>
            <p class="text-xl md:text-2xl mb-8 text-blue-100">
                Gestiona mesas, menús y pedidos con códigos QR. Simple, rápido y eficiente.
            </p>
            <a href="#pricing" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                Ver Planes
            </a>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h3 class="text-3xl font-bold text-gray-900 mb-4">¿Por qué elegir MOZO QR?</h3>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Simplifica la gestión de tu restaurante con nuestra plataforma todo en uno
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center p-6">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-qrcode text-blue-600 text-2xl"></i>
                    </div>
                    <h4 class="text-xl font-semibold mb-2">Códigos QR Inteligentes</h4>
                    <p class="text-gray-600">Los clientes escanean el QR de la mesa y acceden al menú digital instantáneamente</p>
                </div>

                <div class="text-center p-6">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-mobile-alt text-green-600 text-2xl"></i>
                    </div>
                    <h4 class="text-xl font-semibold mb-2">Gestión Móvil</h4>
                    <p class="text-gray-600">App móvil para mozos con notificaciones en tiempo real y gestión de pedidos</p>
                </div>

                <div class="text-center p-6">
                    <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-line text-purple-600 text-2xl"></i>
                    </div>
                    <h4 class="text-xl font-semibold mb-2">Analytics Avanzados</h4>
                    <p class="text-gray-600">Reportes detallados de ventas, mesas más populares y rendimiento del personal</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h3 class="text-3xl font-bold text-gray-900 mb-4">Planes que se adaptan a tu negocio</h3>
                <p class="text-xl text-gray-600">Elige el plan perfecto para el tamaño de tu restaurante</p>
            </div>

            <div class="grid md:grid-cols-{{ $plans->count() > 2 ? '3' : $plans->count() }} gap-8 max-w-6xl mx-auto">
                @foreach($plans as $plan)
                <div class="relative bg-white rounded-2xl shadow-xl border {{ $plan->is_popular ? 'border-blue-500 ring-2 ring-blue-500' : 'border-gray-200' }} p-8">
                    @if($plan->is_popular)
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-blue-500 text-white px-4 py-1 rounded-full text-sm font-semibold">
                            Más Popular
                        </span>
                    </div>
                    @endif

                    @if($plan->is_featured)
                    <div class="absolute -top-4 right-4">
                        <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                            Recomendado
                        </span>
                    </div>
                    @endif

                    <div class="text-center">
                        <h4 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h4>
                        <p class="text-gray-600 mb-6">{{ $plan->description }}</p>

                        <div class="mb-6">
                            <span class="text-4xl font-bold text-gray-900">${{ number_format($plan->price_ars, 0) }}</span>
                            <span class="text-gray-600">/mes</span>
                        </div>

                        @if($plan->hasTrialEnabled())
                        <div class="bg-green-50 text-green-700 px-4 py-2 rounded-lg mb-6">
                            <i class="fas fa-gift mr-2"></i>
                            {{ $plan->getTrialDays() }} días gratis
                        </div>
                        @endif

                        <a href="{{ route('public.checkout.plan', $plan) }}"
                           class="w-full {{ $plan->is_popular ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-900 hover:bg-gray-800' }} text-white font-semibold py-3 px-6 rounded-lg transition duration-300 inline-block">
                            Empezar Ahora
                        </a>
                    </div>

                    <div class="mt-8">
                        <h5 class="font-semibold text-gray-900 mb-4">Incluye:</h5>
                        <ul class="space-y-3">
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                <span>Hasta {{ $plan->getMaxTables() }} mesas</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                <span>{{ $plan->getMaxStaff() }} usuarios/mozos</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-3"></i>
                                <span>{{ $plan->getMaxBusinesses() }} {{ $plan->getMaxBusinesses() == 1 ? 'restaurante' : 'restaurantes' }}</span>
                            </li>
                            @if($plan->features)
                                @foreach($plan->features as $feature)
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span>{{ $feature }}</span>
                                </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="text-center mt-12">
                <p class="text-gray-600 mb-4">¿Necesitas más de lo que ofrecemos?</p>
                <a href="#contact" class="text-blue-600 hover:text-blue-700 font-semibold">
                    Contáctanos para un plan personalizado
                </a>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h3 class="text-3xl font-bold text-gray-900 mb-4">Preguntas Frecuentes</h3>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h4 class="font-semibold text-gray-900 mb-2">¿Cómo funciona el período de prueba?</h4>
                    <p class="text-gray-600">Puedes probar MOZO QR completamente gratis durante los días indicados en cada plan. No se requiere información de pago para comenzar.</p>
                </div>

                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h4 class="font-semibold text-gray-900 mb-2">¿Puedo cambiar de plan en cualquier momento?</h4>
                    <p class="text-gray-600">Sí, puedes actualizar o degradar tu plan en cualquier momento desde tu panel de control.</p>
                </div>

                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h4 class="font-semibold text-gray-900 mb-2">¿Qué métodos de pago aceptan?</h4>
                    <p class="text-gray-600">Aceptamos pagos con tarjeta de crédito/débito a través de Mercado Pago y transferencias bancarias.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 bg-blue-600 text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h3 class="text-3xl font-bold mb-4">¿Listo para digitalizar tu restaurante?</h3>
            <p class="text-xl mb-8 text-blue-100">Únete a cientos de restaurantes que ya confían en MOZO QR</p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('public.checkout.index') }}"
                   class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                    Empezar Ahora
                </a>
                <a href="mailto:info@mozoqr.com"
                   class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition duration-300">
                    Contactar Ventas
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h5 class="text-xl font-bold mb-4">MOZO QR</h5>
                    <p class="text-gray-400">La plataforma de gestión de restaurantes más simple y eficiente.</p>
                </div>
                <div>
                    <h6 class="font-semibold mb-4">Producto</h6>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">Características</a></li>
                        <li><a href="#" class="hover:text-white">Precios</a></li>
                        <li><a href="#" class="hover:text-white">Demo</a></li>
                    </ul>
                </div>
                <div>
                    <h6 class="font-semibold mb-4">Soporte</h6>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">Centro de Ayuda</a></li>
                        <li><a href="#" class="hover:text-white">Contacto</a></li>
                        <li><a href="#" class="hover:text-white">Estado del Servicio</a></li>
                    </ul>
                </div>
                <div>
                    <h6 class="font-semibold mb-4">Legal</h6>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">Términos de Uso</a></li>
                        <li><a href="#" class="hover:text-white">Privacidad</a></li>
                        <li><a href="#" class="hover:text-white">Cookies</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} MOZO QR. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - MOZO QR</title>
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
                    <a href="{{ route('public.plans.pricing') }}" class="text-gray-700 hover:text-blue-600">Planes</a>
                    <a href="#" class="text-gray-700 hover:text-blue-600">Ayuda</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Elige tu Plan</h1>
            <p class="text-xl text-gray-600">Selecciona el plan que mejor se adapte a tu restaurante</p>
        </div>

        <div class="grid md:grid-cols-{{ $plans->count() > 2 ? '3' : $plans->count() }} gap-8">
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
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
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
                        Seleccionar Plan
                    </a>
                </div>

                <div class="mt-8">
                    <h4 class="font-semibold text-gray-900 mb-4">Incluye:</h4>
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

        <!-- Información adicional -->
        <div class="mt-16 text-center">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">¿Por qué elegir MOZO QR?</h2>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Seguro y Confiable</h3>
                    <p class="text-gray-600">Pagos protegidos y datos encriptados</p>
                </div>

                <div class="text-center">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-undo text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Sin Permanencia</h3>
                    <p class="text-gray-600">Cancela cuando quieras, sin compromisos</p>
                </div>

                <div class="text-center">
                    <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-headset text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Soporte 24/7</h3>
                    <p class="text-gray-600">Estamos aquí para ayudarte siempre</p>
                </div>
            </div>
        </div>

        <!-- Enlaces de ayuda -->
        <div class="mt-12 text-center">
            <div class="space-x-6 text-sm text-gray-600">
                <a href="#" class="hover:text-blue-600">¿Necesitas ayuda?</a>
                <a href="#" class="hover:text-blue-600">Comparar planes</a>
                <a href="#" class="hover:text-blue-600">Contáctanos</a>
            </div>
        </div>
    </div>

    <!-- Footer simple -->
    <footer class="bg-gray-900 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p>&copy; {{ date('Y') }} MOZO QR. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planes - MOZO QR</title>
    <meta name="description" content="Descubre todos los planes disponibles de MOZO QR para tu restaurante.">
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
                    <a href="{{ route('public.plans.pricing') }}" class="text-gray-700 hover:text-blue-600">Precios</a>
                    <a href="{{ route('public.checkout.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Empezar</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Nuestros Planes</h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Encuentra el plan perfecto para tu restaurante. Desde pequeños cafés hasta grandes cadenas.
            </p>
        </div>

        <div class="grid md:grid-cols-{{ $plans->count() > 2 ? '3' : $plans->count() }} gap-8 mb-16">
            @foreach($plans as $plan)
            <div class="bg-white rounded-xl shadow-lg border {{ $plan->is_popular ? 'border-blue-500 ring-2 ring-blue-500' : 'border-gray-200' }} p-8 relative">
                @if($plan->is_popular)
                <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                    <span class="bg-blue-500 text-white px-4 py-1 rounded-full text-sm font-semibold">
                        Más Popular
                    </span>
                </div>
                @endif

                @if($plan->is_featured)
                <div class="absolute -top-3 right-4">
                    <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                        Recomendado
                    </span>
                </div>
                @endif

                <div class="text-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
                    <p class="text-gray-600 mb-4">{{ $plan->description }}</p>

                    <div class="mb-4">
                        <span class="text-4xl font-bold text-gray-900">${{ number_format($plan->price_ars, 0) }}</span>
                        <span class="text-gray-600">/mes</span>
                    </div>

                    @if($plan->hasTrialEnabled())
                    <div class="bg-green-50 text-green-700 px-3 py-2 rounded-lg mb-4 text-sm">
                        <i class="fas fa-gift mr-1"></i>
                        {{ $plan->getTrialDays() }} días gratis
                    </div>
                    @endif
                </div>

                <div class="mb-8">
                    <h4 class="font-semibold text-gray-900 mb-4">Características:</h4>
                    <ul class="space-y-2">
                        <li class="flex items-center text-sm">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            <span>Hasta {{ $plan->getMaxTables() }} mesas</span>
                        </li>
                        <li class="flex items-center text-sm">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            <span>{{ $plan->getMaxStaff() }} usuarios/mozos</span>
                        </li>
                        <li class="flex items-center text-sm">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            <span>{{ $plan->getMaxBusinesses() }} {{ $plan->getMaxBusinesses() == 1 ? 'restaurante' : 'restaurantes' }}</span>
                        </li>
                        @if($plan->features)
                            @foreach($plan->features as $feature)
                            <li class="flex items-center text-sm">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>{{ $feature }}</span>
                            </li>
                            @endforeach
                        @endif
                    </ul>
                </div>

                <div class="space-y-3">
                    <a href="{{ route('public.checkout.plan', $plan) }}"
                       class="w-full {{ $plan->is_popular ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-900 hover:bg-gray-800' }} text-white font-semibold py-3 px-6 rounded-lg transition duration-300 text-center block">
                        Empezar con {{ $plan->name }}
                    </a>

                    <a href="{{ route('public.plans.show', $plan) }}"
                       class="w-full bg-gray-100 text-gray-700 font-semibold py-2 px-6 rounded-lg hover:bg-gray-200 transition duration-300 text-center block">
                        Ver Detalles
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Comparación de planes -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-8 text-center">Comparación Detallada</h2>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-4 px-4">Característica</th>
                            @foreach($plans as $plan)
                            <th class="text-center py-4 px-4">
                                <div class="font-bold">{{ $plan->name }}</div>
                                <div class="text-sm text-gray-600">${{ number_format($plan->price_ars, 0) }}/mes</div>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b">
                            <td class="py-4 px-4 font-medium">Mesas</td>
                            @foreach($plans as $plan)
                            <td class="text-center py-4 px-4">{{ $plan->getMaxTables() }}</td>
                            @endforeach
                        </tr>
                        <tr class="border-b">
                            <td class="py-4 px-4 font-medium">Usuarios/Mozos</td>
                            @foreach($plans as $plan)
                            <td class="text-center py-4 px-4">{{ $plan->getMaxStaff() }}</td>
                            @endforeach
                        </tr>
                        <tr class="border-b">
                            <td class="py-4 px-4 font-medium">Restaurantes</td>
                            @foreach($plans as $plan)
                            <td class="text-center py-4 px-4">{{ $plan->getMaxBusinesses() }}</td>
                            @endforeach
                        </tr>
                        <tr class="border-b">
                            <td class="py-4 px-4 font-medium">Período de Prueba</td>
                            @foreach($plans as $plan)
                            <td class="text-center py-4 px-4">
                                @if($plan->hasTrialEnabled())
                                    <i class="fas fa-check text-green-500"></i>
                                    <div class="text-sm text-gray-600">{{ $plan->getTrialDays() }} días</div>
                                @else
                                    <i class="fas fa-times text-red-500"></i>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @if($plans->first()->features)
                            @php
                                $allFeatures = $plans->flatMap->features->unique();
                            @endphp
                            @foreach($allFeatures as $feature)
                            <tr class="border-b">
                                <td class="py-4 px-4 font-medium">{{ $feature }}</td>
                                @foreach($plans as $plan)
                                <td class="text-center py-4 px-4">
                                    @if($plan->hasFeature($feature))
                                        <i class="fas fa-check text-green-500"></i>
                                    @else
                                        <i class="fas fa-times text-red-500"></i>
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl text-white p-8 text-center">
            <h2 class="text-3xl font-bold mb-4">¿Listo para comenzar?</h2>
            <p class="text-xl mb-6 text-blue-100">
                Únete a cientos de restaurantes que ya confían en MOZO QR
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('public.checkout.index') }}"
                   class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                    Empezar Ahora
                </a>
                <a href="{{ route('public.plans.pricing') }}"
                   class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition duration-300">
                    Ver Precios Detallados
                </a>
            </div>
        </div>

        <!-- FAQ Básico -->
        <div class="mt-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-8 text-center">Preguntas Frecuentes</h2>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">¿Puedo cambiar de plan?</h3>
                    <p class="text-gray-600 text-sm">Sí, puedes actualizar o degradar tu plan en cualquier momento desde tu panel de control.</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">¿Hay permanencia?</h3>
                    <p class="text-gray-600 text-sm">No, puedes cancelar tu suscripción en cualquier momento sin penalizaciones.</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">¿Qué incluye el soporte?</h3>
                    <p class="text-gray-600 text-sm">Soporte técnico 24/7 por email, chat y teléfono para resolver cualquier duda.</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-semibent text-gray-900 mb-2">¿Cómo funciona el período de prueba?</h3>
                    <p class="text-gray-600 text-sm">Puedes probar todas las funciones sin costo durante los días indicados en cada plan.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h5 class="text-xl font-bold mb-4">MOZO QR</h5>
                    <p class="text-gray-400">La plataforma de gestión de restaurantes más simple y eficiente.</p>
                </div>
                <div>
                    <h6 class="font-semibold mb-4">Producto</h6>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('public.plans.pricing') }}" class="hover:text-white">Precios</a></li>
                        <li><a href="#" class="hover:text-white">Características</a></li>
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
</body>
</html>
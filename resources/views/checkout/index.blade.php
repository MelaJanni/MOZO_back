<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planes de Membresía - MOZO QR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .plan-card { transition: all 0.3s ease; }
        .plan-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg text-white py-12">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <h1 class="text-4xl font-bold mb-4">Elige tu Plan de Membresía</h1>
                <p class="text-xl opacity-90">Potencia tu negocio con nuestras soluciones QR</p>
            </div>
        </div>
    </header>

    <!-- Plans Grid -->
    <main class="container mx-auto px-4 py-12">
        <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
            @foreach($plans as $plan)
            <div class="plan-card bg-white rounded-2xl shadow-lg overflow-hidden {{ $plan->is_popular ? 'ring-2 ring-blue-500 relative' : '' }}">
                @if($plan->is_popular)
                <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    <span class="bg-blue-500 text-white px-4 py-1 rounded-full text-sm font-medium">Más Popular</span>
                </div>
                @endif

                <div class="p-8">
                    <!-- Plan Header -->
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
                        <div class="text-4xl font-bold text-blue-600 mb-2">
                            ${{ number_format($plan->price, 0, ',', '.') }}
                            <span class="text-lg text-gray-500">/ {{ $plan->billing_interval_days == 30 ? 'mes' : ($plan->billing_interval_days == 365 ? 'año' : $plan->billing_interval_days . ' días') }}</span>
                        </div>
                        <p class="text-gray-600">{{ $plan->description }}</p>
                    </div>

                    <!-- Features -->
                    @if($plan->features)
                    <div class="mb-8">
                        <ul class="space-y-3">
                            @foreach($plan->features as $feature)
                            <li class="flex items-center text-gray-700">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <!-- CTA Button -->
                    <div class="text-center">
                        <a href="{{ route('checkout.plan', $plan->id) }}"
                           class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200 inline-block">
                            Seleccionar Plan
                        </a>
                    </div>

                    @if($plan->trial_days > 0)
                    <p class="text-center text-sm text-gray-500 mt-3">
                        Incluye {{ $plan->trial_days }} días de prueba gratis
                    </p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <!-- Guarantee Section -->
        <div class="text-center mt-16">
            <div class="bg-white rounded-xl shadow-lg p-8 max-w-3xl mx-auto">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Garantía de Satisfacción</h3>
                <p class="text-gray-600 mb-6">
                    Estamos seguros de que nuestro servicio transformará tu negocio.
                    Si no estás completamente satisfecho, te devolvemos tu dinero.
                </p>
                <div class="flex justify-center items-center space-x-8">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-700">Soporte 24/7</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-700">Sin compromisos</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-700">Actualización automática</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; {{ date('Y') }} MOZO QR. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>
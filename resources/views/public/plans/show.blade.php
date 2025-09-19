<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan {{ $plan->name }} - MOZO QR</title>
    <meta name="description" content="Detalles completos del plan {{ $plan->name }} de MOZO QR. {{ $plan->description }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="{{ route('public.plans.index') }}" class="text-2xl font-bold text-blue-600">MOZO QR</a>
                </div>
                <nav class="hidden md:flex space-x-8">
                    <a href="{{ route('public.plans.index') }}" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Volver a Planes
                    </a>
                    <a href="{{ route('public.plans.pricing') }}" class="text-gray-700 hover:text-blue-600">Precios</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Hero del Plan -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8 text-center {{ $plan->is_popular ? 'ring-2 ring-blue-500' : '' }}">
            @if($plan->is_popular)
            <div class="inline-block bg-blue-500 text-white px-4 py-1 rounded-full text-sm font-semibold mb-4">
                Plan Más Popular
            </div>
            @endif

            @if($plan->is_featured)
            <div class="inline-block bg-green-500 text-white px-4 py-1 rounded-full text-sm font-semibold mb-4 {{ $plan->is_popular ? 'ml-2' : '' }}">
                Recomendado
            </div>
            @endif

            <h1 class="text-4xl font-bold text-gray-900 mb-4">Plan {{ $plan->name }}</h1>
            <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">{{ $plan->description }}</p>

            <div class="mb-8">
                <span class="text-5xl font-bold text-gray-900">${{ number_format($plan->price_ars, 0) }}</span>
                <span class="text-xl text-gray-600">/mes</span>
            </div>

            @if($plan->hasTrialEnabled())
            <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg mb-8 inline-block">
                <i class="fas fa-gift mr-2"></i>
                <strong>{{ $plan->getTrialDays() }} días gratis</strong> - Prueba sin compromiso
            </div>
            @endif

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('public.checkout.plan', $plan) }}"
                   class="bg-blue-600 text-white font-bold py-4 px-8 rounded-lg hover:bg-blue-700 transition duration-300 text-lg">
                    <i class="fas fa-rocket mr-2"></i>
                    Empezar con {{ $plan->name }}
                </a>
                <a href="{{ route('public.plans.pricing') }}"
                   class="bg-gray-100 text-gray-700 font-semibold py-4 px-8 rounded-lg hover:bg-gray-200 transition duration-300 text-lg">
                    Comparar Planes
                </a>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
            <!-- Características incluidas -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">¿Qué incluye?</h2>

                <div class="space-y-4">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-2 rounded-lg mr-4">
                            <i class="fas fa-table text-blue-600"></i>
                        </div>
                        <div>
                            <div class="font-semibold">{{ $plan->getMaxTables() }} Mesas</div>
                            <div class="text-sm text-gray-600">Códigos QR únicos para cada mesa</div>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="bg-green-100 p-2 rounded-lg mr-4">
                            <i class="fas fa-users text-green-600"></i>
                        </div>
                        <div>
                            <div class="font-semibold">{{ $plan->getMaxStaff() }} Usuarios</div>
                            <div class="text-sm text-gray-600">Mozos y personal de administración</div>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="bg-purple-100 p-2 rounded-lg mr-4">
                            <i class="fas fa-store text-purple-600"></i>
                        </div>
                        <div>
                            <div class="font-semibold">{{ $plan->getMaxBusinesses() }} {{ $plan->getMaxBusinesses() == 1 ? 'Restaurante' : 'Restaurantes' }}</div>
                            <div class="text-sm text-gray-600">Gestiona múltiples ubicaciones</div>
                        </div>
                    </div>

                    @if($plan->features)
                        @foreach($plan->features as $feature)
                        <div class="flex items-center">
                            <div class="bg-gray-100 p-2 rounded-lg mr-4">
                                <i class="fas fa-check text-gray-600"></i>
                            </div>
                            <div>
                                <div class="font-semibold">{{ $feature }}</div>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Opciones de facturación -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Opciones de Pago</h2>

                <div class="space-y-4">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-semibold">Mensual</div>
                                <div class="text-sm text-gray-600">Pago mes a mes</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold">${{ number_format($plan->price_ars, 0) }}</div>
                                <div class="text-sm text-gray-600">por mes</div>
                            </div>
                        </div>
                    </div>

                    @if($plan->quarterly_discount_percentage > 0)
                    <div class="border border-green-200 rounded-lg p-4 bg-green-50">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-semibold">Trimestral</div>
                                <div class="text-sm text-green-600">Ahorra {{ $plan->quarterly_discount_percentage }}%</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold">${{ number_format($plan->getPriceWithDiscount('quarterly'), 0) }}</div>
                                <div class="text-sm text-gray-600">por mes</div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($plan->yearly_discount_percentage > 0)
                    <div class="border border-blue-200 rounded-lg p-4 bg-blue-50">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-semibold">Anual</div>
                                <div class="text-sm text-blue-600">Ahorra {{ $plan->yearly_discount_percentage }}% - ¡Mejor oferta!</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold">${{ number_format($plan->getPriceWithDiscount('yearly'), 0) }}</div>
                                <div class="text-sm text-gray-600">por mes</div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="font-semibold text-gray-900 mb-3">Métodos de Pago Aceptados</h3>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                            <i class="fas fa-credit-card text-gray-400 mr-2"></i>
                            <span class="text-sm">Tarjetas</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-university text-gray-400 mr-2"></i>
                            <span class="text-sm">Transferencia</span>
                        </div>
                        <img src="https://http2.mlstatic.com/storage/logos-api-admin/51b446b0-571c-11e8-9a2d-4b2bd7b1bf77-m.svg" alt="Mercado Pago" class="h-6">
                    </div>
                </div>
            </div>
        </div>

        <!-- Beneficios adicionales -->
        <div class="bg-white rounded-xl shadow-lg p-8 mt-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-8 text-center">Beneficios Incluidos</h2>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-mobile-alt text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">App Móvil</h3>
                    <p class="text-gray-600 text-sm">App para mozos con notificaciones en tiempo real</p>
                </div>

                <div class="text-center">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-headset text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Soporte 24/7</h3>
                    <p class="text-gray-600 text-sm">Ayuda técnica cuando la necesites</p>
                </div>

                <div class="text-center">
                    <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-line text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Reportes</h3>
                    <p class="text-gray-600 text-sm">Analytics detallados de tu restaurante</p>
                </div>

                <div class="text-center">
                    <div class="bg-yellow-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-qrcode text-yellow-600 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">QR Personalizados</h3>
                    <p class="text-gray-600 text-sm">Códigos QR con tu marca y colores</p>
                </div>

                <div class="text-center">
                    <div class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-alt text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Seguridad</h3>
                    <p class="text-gray-600 text-sm">Datos protegidos con encriptación SSL</p>
                </div>

                <div class="text-center">
                    <div class="bg-indigo-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-sync text-indigo-600 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Actualizaciones</h3>
                    <p class="text-gray-600 text-sm">Nuevas funciones automáticamente</p>
                </div>
            </div>
        </div>

        <!-- FAQ específico del plan -->
        <div class="bg-white rounded-xl shadow-lg p-8 mt-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">Preguntas Frecuentes</h2>

            <div class="space-y-6">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">¿Qué pasa si necesito más mesas?</h3>
                    <p class="text-gray-600">Puedes actualizar a un plan superior en cualquier momento para obtener más mesas y usuarios.</p>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">¿Cómo funciona el período de prueba?</h3>
                    <p class="text-gray-600">
                        @if($plan->hasTrialEnabled())
                            Tienes {{ $plan->getTrialDays() }} días para probar todas las funciones sin costo. No se requiere información de pago para comenzar.
                        @else
                            Este plan no incluye período de prueba, pero puedes cancelar en cualquier momento.
                        @endif
                    </p>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">¿Puedo cambiar de plan después?</h3>
                    <p class="text-gray-600">Sí, puedes actualizar o degradar tu plan desde tu panel de control cuando lo necesites.</p>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">¿Hay costos adicionales?</h3>
                    <p class="text-gray-600">No hay costos ocultos. El precio mostrado incluye todas las funciones y el soporte técnico.</p>
                </div>
            </div>
        </div>

        <!-- CTA Final -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl text-white p-8 text-center mt-8">
            <h2 class="text-3xl font-bold mb-4">¿Listo para empezar?</h2>
            <p class="text-xl mb-6 text-blue-100">
                Únete a cientos de restaurantes que ya usan MOZO QR
            </p>
            <a href="{{ route('public.checkout.plan', $plan) }}"
               class="bg-white text-blue-600 px-8 py-4 rounded-lg font-bold hover:bg-gray-100 transition duration-300 text-lg">
                Empezar con Plan {{ $plan->name }}
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p>&copy; {{ date('Y') }} MOZO QR. Todos los derechos reservados.</p>
            <div class="mt-4 space-x-6 text-sm text-gray-400">
                <a href="#" class="hover:text-white">Términos de Uso</a>
                <a href="#" class="hover:text-white">Privacidad</a>
                <a href="#" class="hover:text-white">Soporte</a>
            </div>
        </div>
    </footer>
</body>
</html>
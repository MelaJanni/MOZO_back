@extends('layouts.mozo-public')

@section('title', 'Planes y Precios - MOZO QR')
@section('description', 'Descubre los planes de MOZO QR perfectos para tu restaurante. Desde pequeños cafés hasta grandes cadenas. ¡Prueba gratis 14 días!')

@section('content')
<!-- Hero Section -->
<section class="relative overflow-hidden bg-gradient-to-br from-mozo-900 via-mozo-800 to-mozo-700 text-white py-16 lg:py-24">
    <!-- Background decoration -->
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="absolute top-0 left-0 w-72 h-72 bg-mozo-500/20 rounded-full mix-blend-multiply filter blur-xl opacity-70"></div>
        <div class="absolute bottom-0 right-0 w-72 h-72 bg-mozo-300/20 rounded-full mix-blend-multiply filter blur-xl opacity-70"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="space-y-6">
            <span class="inline-block px-4 py-2 bg-mozo-500/20 border border-mozo-500/30 rounded-full text-mozo-200 text-sm font-medium">
                🚀 Planes flexibles para cada tamaño
            </span>
            <h1 class="text-4xl lg:text-6xl font-bold leading-tight">
                Elige el plan perfecto para
                <span class="bg-gradient-to-r from-mozo-300 to-mozo-100 bg-clip-text text-transparent">
                    tu restaurante
                </span>
            </h1>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto leading-relaxed">
                Desde pequeños cafés hasta grandes cadenas. Todos los planes incluyen período de prueba gratuito y migración de datos sin costo.
            </p>
            <a href="#plans" class="inline-flex items-center px-8 py-4 bg-mozo-500 text-white font-semibold rounded-xl hover:bg-mozo-600 transition-all duration-300 shadow-xl hover:shadow-2xl">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
                Ver Planes
            </a>
        </div>
    </div>
</section>

<!-- Quick Features -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Todo incluido en todos los planes</h2>
            <p class="text-xl text-gray-600">Las herramientas esenciales para modernizar tu negocio</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-mozo-500 to-mozo-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Códigos QR Personalizados</h3>
                <p class="text-gray-600">Con tu logo y colores corporativos. Genera e imprime en minutos.</p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-mozo-400 to-mozo-500 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM12 17h-7a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v5"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Notificaciones Instantáneas</h3>
                <p class="text-gray-600">Los mozos reciben alertas inmediatas en sus dispositivos móviles.</p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-mozo-300 to-mozo-400 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Dashboard Completo</h3>
                <p class="text-gray-600">Estadísticas en tiempo real y reportes detallados de tu negocio.</p>
            </div>
        </div>
    </div>
</section>

<!-- Plans Section -->
<section id="plans" class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl lg:text-5xl font-bold text-gray-900 mb-4">
                Planes que crecen con
                <span class="bg-gradient-to-r from-mozo-600 to-mozo-500 bg-clip-text text-transparent">tu negocio</span>
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Comienza con el plan que mejor se adapte a tu restaurante. Puedes cambiar en cualquier momento.
            </p>
        </div>

        <div class="grid md:grid-cols-{{ min($plans->count(), 3) }} gap-8 max-w-6xl mx-auto">
            @foreach($plans as $plan)
            <div class="relative bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 {{ $plan->is_popular ? 'border-2 border-mozo-500 transform scale-105' : 'border border-gray-200' }} p-8 hover-lift">
                @if($plan->is_popular)
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                    <span class="bg-gradient-to-r from-mozo-500 to-mozo-600 text-white px-6 py-2 rounded-full text-sm font-semibold shadow-lg">
                        ⭐ Más Popular
                    </span>
                </div>
                @endif

                @if($plan->is_featured)
                <div class="absolute -top-4 right-4">
                    <span class="bg-green-500 text-white px-4 py-2 rounded-full text-sm font-semibold shadow-lg">
                        Recomendado
                    </span>
                </div>
                @endif

                <div class="text-center">
                    <h3 class="text-2xl font-bold mb-2 text-gray-900">{{ $plan->name }}</h3>
                    <p class="text-gray-600 mb-6">{{ $plan->description }}</p>

                    <div class="mb-6">
                        <span class="text-5xl font-bold text-gray-900">{{ $plan->getFormattedPrice() }}</span>
                        <span class="text-gray-600 text-lg">/{{ $plan->billing_period === 'monthly' ? 'mes' : 'año' }}</span>
                    </div>

                    @if($plan->hasTrialEnabled())
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                        </svg>
                        <span class="font-semibold">{{ $plan->getTrialDays() }} días gratis</span>
                    </div>
                    @endif

                    <a href="{{ route('public.checkout.plan', $plan) }}"
                       class="w-full {{ $plan->is_popular ? 'bg-mozo-500 hover:bg-mozo-600' : 'bg-gray-900 hover:bg-gray-800' }} text-white font-semibold py-4 px-6 rounded-xl transition-all duration-300 inline-block mb-8 shadow-lg hover:shadow-xl transform hover:scale-105">
                        Empezar Ahora
                    </a>
                </div>

                <div class="space-y-4">
                    <h4 class="font-bold text-gray-900 text-lg border-b border-gray-200 pb-2">Lo que incluye:</h4>

                    <!-- Features -->
                    @if($plan->features && count($plan->features) > 0)
                    <div class="space-y-3">
                        @foreach($plan->features as $feature)
                        <div class="flex items-center">
                            <div class="w-6 h-6 bg-mozo-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-mozo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-gray-700">{{ $feature }}</span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-4">
                        <span class="text-gray-500 italic">No hay características definidas para este plan</span>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <!-- Additional info -->
        <div class="mt-16 text-center">
            <div class="bg-white rounded-2xl p-8 shadow-lg max-w-4xl mx-auto">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">¿Necesitas algo más personalizado?</h3>
                <p class="text-gray-600 mb-6">
                    Para cadenas grandes o necesidades específicas, tenemos planes empresariales a medida con integraciones personalizadas, soporte dedicado y precios especiales.
                </p>
                <a href="mailto:ventas@mozoqr.com" class="inline-flex items-center px-8 py-4 bg-mozo-500 text-white font-semibold rounded-xl hover:bg-mozo-600 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Contactar Ventas
                </a>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Preguntas Frecuentes</h2>
            <p class="text-xl text-gray-600">Todo lo que necesitas saber sobre nuestros planes</p>
        </div>

        <div class="space-y-8">
            <div class="bg-gray-50 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">¿Puedo cambiar de plan en cualquier momento?</h3>
                <p class="text-gray-600">Sí, puedes actualizar o degradar tu plan cuando quieras. Los cambios se aplican inmediatamente y se prorratea el costo.</p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">¿Qué incluye el período de prueba?</h3>
                <p class="text-gray-600">El período de prueba incluye acceso completo a todas las funciones del plan seleccionado, sin limitaciones. No se requiere tarjeta de crédito.</p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">¿Hay costos de instalación o configuración?</h3>
                <p class="text-gray-600">No, todos nuestros planes incluyen configuración gratuita y migración de datos sin costo adicional. Nuestro equipo te ayuda a empezar.</p>
            </div>

            <div class="bg-gray-50 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">¿Puedo cancelar en cualquier momento?</h3>
                <p class="text-gray-600">Sí, puedes cancelar tu suscripción cuando quieras. No hay penalizaciones ni costos de cancelación.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-r from-mozo-600 to-mozo-500 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="space-y-8">
            <h2 class="text-3xl lg:text-5xl font-bold">
                ¿Listo para empezar?
            </h2>
            <p class="text-xl text-mozo-100 max-w-2xl mx-auto">
                Únete a cientos de restaurantes que ya digitalizaron sus operaciones.
                Comienza tu prueba gratuita hoy mismo.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('public.checkout.index') }}"
                   class="bg-white text-mozo-600 px-8 py-4 rounded-xl font-semibold text-lg hover:bg-gray-100 transition-all duration-300 shadow-xl hover:shadow-2xl">
                    Empezar Prueba Gratis
                </a>
                <a href="/"
                   class="border-2 border-white text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-white hover:text-mozo-600 transition-all duration-300">
                    Volver al Inicio
                </a>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
</script>
@endpush
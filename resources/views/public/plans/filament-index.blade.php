@extends('layouts.filament-public')

@section('title', 'Planes y Precios - MOZO QR')
@section('description', 'Descubre los planes de MOZO QR. Desde pequeños cafés hasta grandes cadenas de restaurantes.')

@section('content')
    <div class="py-8">
        <!-- Hero Section -->
        <div class="text-center bg-gradient-to-br from-primary-500 to-primary-600 text-white py-20 mb-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    Digitaliza tu Restaurante
                </h1>
                <p class="text-xl md:text-2xl mb-8 text-primary-100">
                    Gestiona mesas, menús y pedidos con códigos QR. Simple, rápido y eficiente.
                </p>
                <a href="#plans" class="inline-flex items-center px-8 py-3 bg-white text-primary-600 font-semibold rounded-lg hover:bg-gray-100 transition duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                    Ver Planes
                </a>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Features Section -->
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">¿Por qué elegir MOZO QR?</h2>
                <p class="text-xl text-gray-600 dark:text-gray-300 mb-8">
                    Simplifica la gestión de tu restaurante con nuestra plataforma todo en uno
                </p>

                <div class="grid md:grid-cols-3 gap-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8">
                        <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">Códigos QR Inteligentes</h3>
                        <p class="text-gray-600 dark:text-gray-300">Los clientes escanean el QR de la mesa y acceden al menú digital instantáneamente</p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8">
                        <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">Gestión Móvil</h3>
                        <p class="text-gray-600 dark:text-gray-300">App móvil para mozos con notificaciones en tiempo real y gestión de pedidos</p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8">
                        <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00-2-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 00-2 2h2a2 2 0 002-2V9a2 2 0 00-2-2V5a2 2 0 00-2-2"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">Analytics Avanzados</h3>
                        <p class="text-gray-600 dark:text-gray-300">Reportes detallados de ventas, mesas más populares y rendimiento del personal</p>
                    </div>
                </div>
            </div>

            <!-- Plans Section -->
            <div id="plans">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Planes que se adaptan a tu negocio</h2>
                    <p class="text-xl text-gray-600 dark:text-gray-300">Elige el plan perfecto para el tamaño de tu restaurante</p>
                </div>

                <div class="grid md:grid-cols-{{ $plans->count() > 2 ? '3' : $plans->count() }} gap-8 max-w-6xl mx-auto">
                    @foreach($plans as $plan)
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl border {{ $plan->is_popular ? 'border-primary-500 ring-2 ring-primary-500' : 'border-gray-200 dark:border-gray-700' }} p-8 transition duration-300 hover:shadow-2xl">
                        @if($plan->is_popular)
                        <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                            <span class="bg-primary-500 text-white px-4 py-1 rounded-full text-sm font-semibold shadow-lg">
                                Más Popular
                            </span>
                        </div>
                        @endif

                        @if($plan->is_featured)
                        <div class="absolute -top-4 right-4">
                            <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                                Recomendado
                            </span>
                        </div>
                        @endif

                        <div class="text-center">
                            <h3 class="text-2xl font-bold mb-2 text-gray-900 dark:text-white">{{ $plan->name }}</h3>
                            <p class="text-gray-600 dark:text-gray-300 mb-6">{{ $plan->description }}</p>

                            <div class="mb-6">
                                <span class="text-4xl font-bold text-gray-900 dark:text-white">${{ number_format($plan->price_ars, 0) }}</span>
                                <span class="text-gray-600 dark:text-gray-300">/mes</span>
                            </div>

                            @if($plan->hasTrialEnabled())
                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-2 rounded-lg mb-6">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                                </svg>
                                {{ $plan->getTrialDays() }} días gratis
                            </div>
                            @endif

                            <a href="{{ route('public.checkout.plan', $plan) }}"
                               class="w-full {{ $plan->is_popular ? 'bg-primary-600 hover:bg-primary-700' : 'bg-gray-900 hover:bg-gray-800' }} text-white font-semibold py-3 px-6 rounded-lg transition duration-300 inline-block mb-6">
                                Empezar Ahora
                            </a>
                        </div>

                        <div class="mt-8">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-4">Incluye:</h4>
                            <ul class="space-y-3">
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300">Hasta {{ $plan->getMaxTables() }} mesas</span>
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300">{{ $plan->getMaxStaff() }} usuarios/mozos</span>
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300">{{ $plan->getMaxBusinesses() }} {{ $plan->getMaxBusinesses() == 1 ? 'restaurante' : 'restaurantes' }}</span>
                                </li>
                                @if($plan->features)
                                    @foreach($plan->features as $feature)
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-gray-700 dark:text-gray-300">{{ $feature }}</span>
                                    </li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- CTA Section -->
            <div class="text-center bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl p-8 text-white mt-16">
                <h2 class="text-3xl font-bold mb-4">¿Listo para digitalizar tu restaurante?</h2>
                <p class="text-xl text-primary-100 mb-6">Únete a cientos de restaurantes que ya confían en MOZO QR</p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('public.checkout.index') }}"
                       class="inline-flex items-center justify-center px-8 py-3 bg-white text-primary-600 font-semibold rounded-lg hover:bg-gray-100 transition duration-300">
                        Empezar Ahora
                    </a>
                    <a href="mailto:info@mozoqr.com"
                       class="inline-flex items-center justify-center px-8 py-3 border-2 border-white text-white font-semibold rounded-lg hover:bg-white hover:text-primary-600 transition duration-300">
                        Contactar Ventas
                    </a>
                </div>
            </div>
        </div>
    </div>
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
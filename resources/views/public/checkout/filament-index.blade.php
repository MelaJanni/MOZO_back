@extends('layouts.filament-public')

@section('title', 'Checkout - Elige tu Plan - MOZO QR')
@section('description', 'Selecciona el plan perfecto para tu restaurante y comienza a digitalizar tu negocio hoy mismo.')

@section('content')
    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">Elige tu Plan</h1>
                <p class="text-xl text-gray-600 dark:text-gray-300">Selecciona el plan que mejor se adapte a tu restaurante</p>
            </div>

            <div class="grid md:grid-cols-{{ $plans->count() > 2 ? '3' : $plans->count() }} gap-8">
                @foreach($plans as $plan)
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl border {{ $plan->is_popular ? 'border-primary-500 ring-2 ring-primary-500' : 'border-gray-200 dark:border-gray-700' }} p-8">
                    @if($plan->is_popular)
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-primary-500 text-white px-4 py-1 rounded-full text-sm font-semibold">
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
                           class="w-full {{ $plan->is_popular ? 'bg-primary-600 hover:bg-primary-700' : 'bg-gray-900 hover:bg-gray-800' }} text-white font-semibold py-3 px-6 rounded-lg transition duration-300 inline-block">
                            Seleccionar Plan
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

            <!-- Información adicional -->
            <div class="mt-16 text-center">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-8">¿Por qué elegir MOZO QR?</h2>

                <div class="grid md:grid-cols-3 gap-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 text-center">
                        <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">Seguro y Confiable</h3>
                        <p class="text-gray-600 dark:text-gray-300">Pagos protegidos y datos encriptados</p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 text-center">
                        <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">Sin Permanencia</h3>
                        <p class="text-gray-600 dark:text-gray-300">Cancela cuando quieras, sin compromisos</p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 text-center">
                        <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">Soporte 24/7</h3>
                        <p class="text-gray-600 dark:text-gray-300">Estamos aquí para ayudarte siempre</p>
                    </div>
                </div>
            </div>

            <!-- Enlaces de ayuda -->
            <div class="mt-12 text-center">
                <div class="space-x-6 text-sm text-gray-600 dark:text-gray-400">
                    <a href="#" class="hover:text-primary-600 dark:hover:text-primary-400">¿Necesitas ayuda?</a>
                    <a href="{{ route('public.plans.index') }}" class="hover:text-primary-600 dark:hover:text-primary-400">Comparar planes</a>
                    <a href="mailto:info@mozoqr.com" class="hover:text-primary-600 dark:hover:text-primary-400">Contáctanos</a>
                </div>
            </div>
        </div>
    </div>
@endsection
@extends('layouts.filament-public')

@section('title', 'Plan Requerido - MOZO QR')
@section('description', 'Su plan anterior ya no está disponible. Seleccione un nuevo plan para continuar usando la plataforma.')

@section('content')
    <div class="py-12 bg-gradient-to-br from-red-50 to-orange-50 dark:from-red-950 dark:to-orange-950">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Encabezado de Aviso -->
            <div class="text-center mb-12">
                <div class="mx-auto w-16 h-16 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    Plan Actualización Requerida
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                    Su plan anterior ha sido descontinuado. Para continuar usando MOZO QR,
                    debe seleccionar uno de nuestros nuevos planes disponibles.
                </p>
            </div>

            @if(isset($subscription) && $subscription->isInGracePeriod())
            <!-- Período de Gracia -->
            <div class="bg-orange-100 dark:bg-orange-900/30 border border-orange-200 dark:border-orange-800 rounded-lg p-6 mb-8">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-orange-800 dark:text-orange-200 mb-1">
                            Período de Gracia Activo
                        </h3>
                        <p class="text-orange-700 dark:text-orange-300">
                            Tiene <strong>{{ $subscription->getGraceDaysRemaining() }} días restantes</strong>
                            para seleccionar un nuevo plan antes de que se suspenda el acceso.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Selección de Planes -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-8">
                    Seleccione su Nuevo Plan
                </h2>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($availablePlans as $plan)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 relative {{ $plan->is_popular ? 'ring-2 ring-primary-500' : '' }}">
                        @if($plan->is_popular)
                        <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                            <span class="bg-primary-500 text-white px-4 py-1 rounded-full text-sm font-semibold">
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

                        <div class="text-center">
                            <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">{{ $plan->name }}</h3>
                            <p class="text-gray-600 dark:text-gray-300 mb-4">{{ $plan->description }}</p>

                            <div class="mb-6">
                                <span class="text-3xl font-bold text-gray-900 dark:text-white">${{ number_format($plan->price_ars, 0) }}</span>
                                <span class="text-gray-600 dark:text-gray-300">/mes</span>
                            </div>

                            @if($plan->hasTrialEnabled())
                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-3 py-2 rounded-lg mb-6 text-sm">
                                <div class="flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                                    </svg>
                                    {{ $plan->getTrialDays() }} días gratis
                                </div>
                            </div>
                            @endif

                            <!-- Características principales -->
                            <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-2 mb-6 text-left">
                                <li class="flex items-center">
                                    <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    {{ $plan->getMaxTables() }} mesas máximo
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    {{ $plan->getMaxStaff() }} usuarios/mozos
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    {{ $plan->getMaxBusinesses() }} {{ $plan->getMaxBusinesses() == 1 ? 'restaurante' : 'restaurantes' }}
                                </li>
                            </ul>

                            <a href="{{ route('public.checkout.grace-plan', $plan) }}"
                               class="w-full {{ $plan->is_popular ? 'bg-primary-600 hover:bg-primary-700' : 'bg-gray-900 hover:bg-gray-800' }} text-white font-semibold py-3 px-6 rounded-lg transition duration-300 inline-block">
                                Seleccionar Plan
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Información adicional -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-3">
                    ¿Necesita ayuda para elegir?
                </h3>
                <p class="text-blue-700 dark:text-blue-300 mb-4">
                    Nuestro equipo de soporte está disponible para ayudarle a seleccionar el plan
                    que mejor se adapte a las necesidades de su restaurante.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="mailto:soporte@mozoqr.com"
                       class="inline-flex items-center justify-center px-4 py-2 border-2 border-blue-600 text-blue-600 dark:text-blue-400 font-semibold rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition duration-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Contactar Soporte
                    </a>
                    <a href="{{ route('public.plans.index') }}"
                       class="inline-flex items-center justify-center px-4 py-2 border-2 border-blue-600 text-blue-600 dark:text-blue-400 font-semibold rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition duration-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Ver Comparación Completa
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
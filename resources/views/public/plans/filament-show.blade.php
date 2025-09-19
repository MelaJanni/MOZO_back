@extends('layouts.filament-public')

@section('title', $plan->name . ' - Plan Detallado - MOZO QR')
@section('description', $plan->description)

@section('content')
    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Botón volver -->
            <div class="mb-8">
                <a href="{{ route('public.plans.index') }}"
                   class="inline-flex items-center text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver a Planes
                </a>
            </div>

            <div class="grid lg:grid-cols-2 gap-8">
                <!-- Información del Plan -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8">
                    @if($plan->is_popular)
                    <div class="mb-4">
                        <span class="bg-primary-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                            Más Popular
                        </span>
                    </div>
                    @endif

                    @if($plan->is_featured)
                    <div class="mb-4">
                        <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                            Recomendado
                        </span>
                    </div>
                    @endif

                    <div class="text-center">
                        <h1 class="text-3xl font-bold mb-4 text-gray-900 dark:text-white">{{ $plan->name }}</h1>
                        <p class="text-lg text-gray-600 dark:text-gray-300 mb-6">{{ $plan->description }}</p>

                        <div class="mb-8">
                            <span class="text-4xl font-bold text-gray-900 dark:text-white">{{ $plan->getFormattedPrice() }}</span>
                            <span class="text-gray-600 dark:text-gray-300">/{{ $plan->billing_period === 'monthly' ? 'mes' : 'año' }}</span>
                        </div>

                        @if($plan->hasTrialEnabled())
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-lg mb-6">
                            <div class="flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                                </svg>
                                <span class="font-semibold">{{ $plan->getTrialDays() }} días gratis</span>
                            </div>
                        </div>
                        @endif

                        <a href="{{ route('public.checkout.plan', $plan) }}"
                           class="w-full {{ $plan->is_popular ? 'bg-primary-600 hover:bg-primary-700' : 'bg-gray-900 hover:bg-gray-800' }} text-white font-semibold py-3 px-6 rounded-lg transition duration-300 inline-block">
                            Empezar Ahora
                        </a>
                    </div>
                </div>

                <!-- Características Detalladas -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">¿Qué incluye este plan?</h2>

                    <div class="space-y-6">
                        <!-- Límites principales -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Capacidad</h3>
                            <ul class="space-y-3">
                                <li class="flex items-center">
                                    <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        <strong>{{ $plan->getMaxTables() }}</strong> mesas máximo
                                    </span>
                                </li>
                                <li class="flex items-center">
                                    <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        <strong>{{ $plan->getMaxStaff() }}</strong> usuarios/mozos
                                    </span>
                                </li>
                                <li class="flex items-center">
                                    <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        <strong>{{ $plan->getMaxBusinesses() }}</strong> {{ $plan->getMaxBusinesses() == 1 ? 'restaurante' : 'restaurantes' }}
                                    </span>
                                </li>
                            </ul>
                        </div>

                        <!-- Características especiales -->
                        @if($plan->features && is_array($plan->features) && count($plan->features) > 0)
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Características Especiales</h3>
                            <ul class="space-y-3">
                                @foreach($plan->features as $feature)
                                <li class="flex items-center">
                                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-300">{{ $feature }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <!-- Descuentos disponibles -->
                        @if($plan->yearly_discount_percentage > 0 || $plan->quarterly_discount_percentage > 0)
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Descuentos por Pago Anticipado</h3>
                            <ul class="space-y-3">
                                @if($plan->quarterly_discount_percentage > 0)
                                <li class="flex items-center">
                                    <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        <strong>{{ $plan->quarterly_discount_percentage }}% descuento</strong> en pago trimestral
                                    </span>
                                </li>
                                @endif
                                @if($plan->yearly_discount_percentage > 0)
                                <li class="flex items-center">
                                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        <strong>{{ $plan->yearly_discount_percentage }}% descuento</strong> en pago anual
                                    </span>
                                </li>
                                @endif
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Comparación con otros planes -->
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-8">Comparar con otros planes</h2>
                <div class="text-center">
                    <a href="{{ route('public.plans.index') }}"
                       class="inline-flex items-center px-6 py-3 border-2 border-primary-600 text-primary-600 dark:text-primary-400 font-semibold rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 transition duration-300">
                        Ver todos los planes
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
@extends('layouts.filament-public')

@section('title', 'Checkout - ' . $plan->name . ' - MOZO QR')
@section('description', 'Completa tu registro y contratación del plan ' . $plan->name . ' para comenzar a digitalizar tu restaurante.')

@section('content')
    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                @if($user)
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Contratar Plan</h1>
                    <p class="text-lg text-gray-600 dark:text-gray-300">¡Hola {{ $user->name }}! Plan seleccionado: <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $plan->name }}</span></p>
                @else
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Completar Registro</h1>
                    <p class="text-lg text-gray-600 dark:text-gray-300">Plan seleccionado: <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $plan->name }}</span></p>
                @endif
            </div>

            <div class="grid lg:grid-cols-2 gap-8">
                <!-- Formulario de Registro -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                        @if($user)
                            Información de Pago
                        @else
                            Información de Registro
                        @endif
                    </h2>

                    <form action="{{ $user ? route('public.checkout.subscribe') : route('public.checkout.register') }}" method="POST" id="checkout-form">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                        <div class="space-y-4">
                            @if(!$user)
                            <!-- Nombre -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nombre completo
                                </label>
                                <input type="text" id="name" name="name" required
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                       value="{{ old('name') }}">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Correo electrónico
                                </label>
                                <input type="email" id="email" name="email" required
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                       value="{{ old('email') }}">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Contraseña -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Contraseña
                                </label>
                                <input type="password" id="password" name="password" required
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Confirmar contraseña -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Confirmar contraseña
                                </label>
                                <input type="password" id="password_confirmation" name="password_confirmation" required
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            @else
                            <!-- Usuario ya autenticado -->
                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-green-700 dark:text-green-400 font-medium">Sesión iniciada como:</p>
                                        <p class="text-green-600 dark:text-green-300">{{ $user->name }} ({{ $user->email }})</p>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Período de facturación -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Período de facturación
                                </label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="radio" name="billing_period" value="monthly" checked
                                               class="text-primary-600 focus:ring-primary-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Mensual - {{ $plan->getFormattedPrice() }}/mes</span>
                                    </label>
                                    @if($plan->quarterly_discount_percentage > 0)
                                    <label class="flex items-center">
                                        <input type="radio" name="billing_period" value="quarterly"
                                               class="text-primary-600 focus:ring-primary-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                            Trimestral - ${{ number_format($plan->getPriceWithDiscount('quarterly'), 0) }}/trimestre
                                            <span class="text-green-600 dark:text-green-400 font-medium">({{ $plan->quarterly_discount_percentage }}% descuento)</span>
                                        </span>
                                    </label>
                                    @endif
                                    @if($plan->yearly_discount_percentage > 0)
                                    <label class="flex items-center">
                                        <input type="radio" name="billing_period" value="yearly"
                                               class="text-primary-600 focus:ring-primary-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                            Anual - ${{ number_format($plan->getPriceWithDiscount('yearly'), 0) }}/año
                                            <span class="text-green-600 dark:text-green-400 font-medium">({{ $plan->yearly_discount_percentage }}% descuento)</span>
                                        </span>
                                    </label>
                                    @endif
                                </div>
                            </div>

                            <!-- Método de pago -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Método de pago
                                </label>
                                <div class="space-y-2">
                                    @foreach($paymentMethods as $method)
                                    <label class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                        <input type="radio" name="payment_method" value="{{ $method->provider }}" {{ $loop->first ? 'checked' : '' }}
                                               class="text-primary-600 focus:ring-primary-500">
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $method->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $method->description }}</p>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Cupón -->
                            <div>
                                <label for="coupon_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Código de cupón (opcional)
                                </label>
                                <div class="flex">
                                    <input type="text" id="coupon_code" name="coupon_code"
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                           value="{{ old('coupon_code') }}">
                                    <button type="button" id="apply-coupon"
                                            class="px-4 py-2 bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 border border-l-0 border-gray-300 dark:border-gray-600 rounded-r-lg hover:bg-gray-200 dark:hover:bg-gray-500">
                                        Aplicar
                                    </button>
                                </div>
                                @error('coupon_code')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Términos -->
                            <div class="flex items-start">
                                <input type="checkbox" id="terms" name="terms" required
                                       class="mt-1 text-primary-600 focus:ring-primary-500">
                                <label for="terms" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Acepto los <a href="#" class="text-primary-600 dark:text-primary-400 hover:underline">términos y condiciones</a>
                                    y la <a href="#" class="text-primary-600 dark:text-primary-400 hover:underline">política de privacidad</a>
                                </label>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full mt-6 bg-primary-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-primary-700 transition duration-300">
                            @if($user)
                                Contratar Plan
                            @else
                                Completar Registro y Pago
                            @endif
                        </button>
                    </form>
                </div>

                <!-- Resumen del Plan -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Resumen del Plan</h2>

                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $plan->name }}</h3>
                        <p class="text-gray-600 dark:text-gray-300 mt-2">{{ $plan->description }}</p>

                        <div class="mt-4">
                            <span class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $plan->getFormattedPrice() }}</span>
                            <span class="text-gray-600 dark:text-gray-300">/mes</span>
                        </div>

                        @if($plan->hasTrialEnabled())
                        <div class="mt-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-2 rounded-lg">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                            </svg>
                            {{ $plan->getTrialDays() }} días gratis
                        </div>
                        @endif
                    </div>

                    <div>
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

                    <!-- Seguridad -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-center text-sm text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            Pago seguro y encriptado
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Aplicar cupón
        document.getElementById('apply-coupon').addEventListener('click', function() {
            const couponCode = document.getElementById('coupon_code').value;
            const planId = {{ $plan->id }};
            const billingPeriod = document.querySelector('input[name="billing_period"]:checked').value;

            if (!couponCode) {
                alert('Por favor ingresa un código de cupón');
                return;
            }

            fetch('{{ route('public.checkout.apply-coupon') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    coupon_code: couponCode,
                    plan_id: planId,
                    billing_period: billingPeriod
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('¡Cupón aplicado correctamente! Descuento: ' + data.pricing.formatted_savings);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al aplicar el cupón');
            });
        });
    </script>
@endsection
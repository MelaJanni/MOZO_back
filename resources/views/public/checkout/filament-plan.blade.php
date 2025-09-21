@extends('layouts.mozo-public')

@section('title', 'Checkout - ' . $plan->name . ' - MOZO QR')
@section('description', 'Completa tu registro y contratación del plan ' . $plan->name . ' para comenzar a digitalizar tu restaurante.')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-white py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <a href="/" class="inline-block mb-6">
                    <img src="{{ asset('images/logo.svg') }}" alt="MOZO QR" class="h-12 w-auto mx-auto">
                </a>
                @if($user)
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Contratar Plan</h1>
                    <p class="text-lg text-gray-600">¡Hola {{ $user->name }}! Plan seleccionado: <span class="font-semibold text-crypto-purple">{{ $plan->name }}</span></p>
                @else
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Completar Registro</h1>
                    <p class="text-lg text-gray-600">Plan seleccionado: <span class="font-semibold text-crypto-purple">{{ $plan->name }}</span></p>
                @endif
            </div>

            <div class="grid lg:grid-cols-2 gap-8">
                <!-- Formulario de Registro -->
                <div class="bg-white/80 backdrop-blur-lg rounded-2xl shadow-2xl border border-white/50 p-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">
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
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Nombre completo
                                </label>
                                <input type="text" id="name" name="name" required
                                       class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-crypto-purple focus:border-crypto-purple transition-colors bg-white bg-white"
                                       value="{{ old('name') }}">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                    Correo electrónico
                                </label>
                                <input type="email" id="email" name="email" required
                                       class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-crypto-purple focus:border-crypto-purple transition-colors bg-white"
                                       value="{{ old('email') }}">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Contraseña -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Contraseña
                                </label>
                                <input type="password" id="password" name="password" required
                                       class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-crypto-purple focus:border-crypto-purple transition-colors bg-white">
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Confirmar contraseña -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                    Confirmar contraseña
                                </label>
                                <input type="password" id="password_confirmation" name="password_confirmation" required
                                       class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-crypto-purple focus:border-crypto-purple transition-colors bg-white">
                            </div>

                            <!-- Separador con Google -->
                            <div class="relative my-6">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-2 bg-white dark:bg-gray-800 text-gray-500">o registrarse con</span>
                                </div>
                            </div>

                            <!-- Botón de Google -->
                            <div class="mb-6">
                                <a href="{{ route('auth.google', ['plan_id' => $plan->id]) }}"
                                   class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition duration-300">
                                    <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                    </svg>
                                    Continuar con Google
                                </a>
                            </div>
                            @else
                            <!-- Usuario ya autenticado -->
                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-green-700 font-medium">Sesión iniciada como:</p>
                                        <p class="text-green-600">{{ $user->name }} ({{ $user->email }})</p>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Período de facturación -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Período de facturación
                                </label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="radio" name="billing_period" value="monthly" checked
                                               class="text-crypto-purple focus:ring-crypto-purple">
                                        <span class="ml-2 text-sm text-gray-700">Mensual - {{ $plan->getFormattedPrice() }}/mes</span>
                                    </label>
                                    @if($plan->quarterly_discount_percentage > 0)
                                    <label class="flex items-center">
                                        <input type="radio" name="billing_period" value="quarterly"
                                               class="text-crypto-purple focus:ring-crypto-purple">
                                        <span class="ml-2 text-sm text-gray-700">
                                            Trimestral - ${{ number_format($plan->getPriceWithDiscount('quarterly'), 0) }}/trimestre
                                            <span class="text-green-600 font-medium">({{ $plan->quarterly_discount_percentage }}% descuento)</span>
                                        </span>
                                    </label>
                                    @endif
                                    @if($plan->yearly_discount_percentage > 0)
                                    <label class="flex items-center">
                                        <input type="radio" name="billing_period" value="yearly"
                                               class="text-crypto-purple focus:ring-crypto-purple">
                                        <span class="ml-2 text-sm text-gray-700">
                                            Anual - ${{ number_format($plan->getPriceWithDiscount('yearly'), 0) }}/año
                                            <span class="text-green-600 font-medium">({{ $plan->yearly_discount_percentage }}% descuento)</span>
                                        </span>
                                    </label>
                                    @endif
                                </div>
                            </div>

                            <!-- Método de pago -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Método de pago
                                </label>
                                <div class="space-y-2">
                                    <label class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                        <input type="radio" name="payment_method" value="mercadopago" checked
                                               class="text-crypto-purple focus:ring-crypto-purple">
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-700">MercadoPago</p>
                                            <p class="text-xs text-gray-500">Tarjetas de crédito, débito y efectivo</p>
                                        </div>
                                    </label>
                                    <label class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                        <input type="radio" name="payment_method" value="bank_transfer"
                                               class="text-crypto-purple focus:ring-crypto-purple">
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-700">Transferencia Bancaria</p>
                                            <p class="text-xs text-gray-500">Pago manual por transferencia</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Cupón -->
                            <div>
                                <label for="coupon_code" class="block text-sm font-medium text-gray-700 mb-1">
                                    Código de cupón (opcional)
                                </label>
                                <div class="flex">
                                    <input type="text" id="coupon_code" name="coupon_code"
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                           value="{{ old('coupon_code') }}">
                                    <button type="button" id="apply-coupon"
                                            class="px-4 py-2 bg-gray-100 dark:bg-gray-600 text-gray-700 border border-l-0 border-gray-300 dark:border-gray-600 rounded-r-lg hover:bg-gray-200 dark:hover:bg-gray-500">
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
                                       class="mt-1 h-4 w-4 text-crypto-purple focus:ring-crypto-purple border-gray-300 rounded">
                                <label for="terms" class="ml-2 text-sm text-gray-700">
                                    Acepto los <a href="#" class="text-crypto-purple hover:text-crypto-dark-purple hover:underline">términos y condiciones</a>
                                    y la <a href="#" class="text-crypto-purple hover:text-crypto-dark-purple hover:underline">política de privacidad</a>
                                </label>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full mt-6 bg-gradient-to-r from-crypto-purple to-crypto-light-purple text-white font-bold py-4 px-8 rounded-xl hover:from-crypto-dark-purple hover:to-crypto-purple transform hover:scale-105 transition-all duration-300 shadow-lg">
                            @if($user)
                                Contratar Plan
                            @else
                                Completar Registro y Pago
                            @endif
                        </button>
                    </form>
                </div>

                <!-- Resumen del Plan -->
                <div class="bg-white/80 backdrop-blur-lg rounded-2xl shadow-2xl border border-white/50 p-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Resumen del Plan</h2>

                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">{{ $plan->name }}</h3>
                        <p class="text-gray-600 dark:text-gray-300 mt-2">{{ $plan->description }}</p>

                        <div class="mt-4">
                            <span class="text-3xl font-bold text-crypto-purple">{{ $plan->getFormattedPrice() }}</span>
                            <span class="text-gray-600">/mes</span>
                        </div>

                        @if($plan->hasTrialEnabled())
                        <div class="mt-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 px-4 py-2 rounded-lg">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                            </svg>
                            {{ $plan->getTrialDays() }} días gratis
                        </div>
                        @endif
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 mb-4">Incluye:</h4>
                        <ul class="space-y-3">
                            @if($plan->features && is_array($plan->features))
                                @foreach($plan->features as $feature)
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">{{ $feature }}</span>
                                </li>
                                @endforeach
                            @else
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">Plan completo disponible</span>
                                </li>
                            @endif
                        </ul>
                    </div>

                    <!-- Seguridad -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex items-center justify-center text-sm text-gray-600">
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
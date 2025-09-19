@extends('layouts.filament-public')

@section('title', 'Cambio de Plan - ' . $plan->name . ' - MOZO QR')
@section('description', 'Complete el cambio a su nuevo plan: ' . $plan->name)

@section('content')
    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Encabezado -->
            <div class="text-center mb-8">
                <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    Cambio de Plan
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-300">
                    Usted ha seleccionado el plan <strong>{{ $plan->name }}</strong>
                </p>
            </div>

            @if($subscription->isInGracePeriod())
            <!-- Período de gracia activo -->
            <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-6 mb-8">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-orange-800 dark:text-orange-200 mb-1">
                            Período de Gracia: {{ $subscription->getGraceDaysRemaining() }} días restantes
                        </h3>
                        <p class="text-orange-700 dark:text-orange-300">
                            Complete este cambio de plan antes de que expire el período de gracia para evitar la suspensión del servicio.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <div class="grid lg:grid-cols-2 gap-8">
                <!-- Resumen del Plan -->
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
                        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">{{ $plan->name }}</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-6">{{ $plan->description }}</p>

                        <div class="mb-6">
                            <span class="text-3xl font-bold text-gray-900 dark:text-white">${{ number_format($plan->price_ars, 0) }}</span>
                            <span class="text-gray-600 dark:text-gray-300">/mes</span>
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

                        <!-- Características del plan -->
                        <div class="text-left">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Incluye:</h3>
                            <ul class="space-y-3">
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        <strong>{{ $plan->getMaxTables() }}</strong> mesas máximo
                                    </span>
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        <strong>{{ $plan->getMaxStaff() }}</strong> usuarios/mozos
                                    </span>
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300">
                                        <strong>{{ $plan->getMaxBusinesses() }}</strong> {{ $plan->getMaxBusinesses() == 1 ? 'restaurante' : 'restaurantes' }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Pago -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">Completar Cambio</h2>

                    <form action="{{ route('public.checkout.register') }}" method="POST">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <input type="hidden" name="is_grace_change" value="true">

                        <!-- Período de facturación -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Período de Facturación
                            </label>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="radio" name="billing_period" value="monthly" class="form-radio text-primary-600" checked>
                                    <span class="ml-3 text-gray-700 dark:text-gray-300">
                                        Mensual - ${{ number_format($plan->price_ars, 0) }}/mes
                                    </span>
                                </label>
                                @if($plan->yearly_discount_percentage > 0)
                                <label class="flex items-center">
                                    <input type="radio" name="billing_period" value="yearly" class="form-radio text-primary-600">
                                    <span class="ml-3 text-gray-700 dark:text-gray-300">
                                        Anual - ${{ number_format($plan->getPriceWithDiscount('yearly'), 0) }}/año
                                        <span class="text-green-600 text-sm">({{ $plan->yearly_discount_percentage }}% descuento)</span>
                                    </span>
                                </label>
                                @endif
                            </div>
                        </div>

                        <!-- Método de pago -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Método de Pago
                            </label>
                            <div class="space-y-3">
                                @foreach($paymentMethods as $method)
                                <label class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                    <input type="radio" name="payment_method" value="{{ $method->code }}" class="form-radio text-primary-600" {{ $loop->first ? 'checked' : '' }}>
                                    <div class="ml-3 flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $method->name }}</div>
                                        @if($method->description)
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $method->description }}</div>
                                        @endif
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Cupón descuento -->
                        <div class="mb-6">
                            <label for="coupon_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Código de Descuento (Opcional)
                            </label>
                            <div class="flex">
                                <input type="text"
                                       id="coupon_code"
                                       name="coupon_code"
                                       class="flex-1 rounded-l-lg border border-gray-300 dark:border-gray-600 px-3 py-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                       placeholder="Ingrese el código">
                                <button type="button"
                                        onclick="applyCoupon()"
                                        class="px-4 py-2 bg-gray-100 dark:bg-gray-600 border border-l-0 border-gray-300 dark:border-gray-600 rounded-r-lg text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500">
                                    Aplicar
                                </button>
                            </div>
                            <div id="coupon-result" class="mt-2 text-sm"></div>
                        </div>

                        <!-- Términos y condiciones -->
                        <div class="mb-6">
                            <label class="flex items-start">
                                <input type="checkbox" name="terms" required class="form-checkbox text-primary-600 mt-1">
                                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                                    Acepto los <a href="#" class="text-primary-600 hover:underline">términos y condiciones</a>
                                    y la <a href="#" class="text-primary-600 hover:underline">política de privacidad</a>
                                </span>
                            </label>
                        </div>

                        <!-- Botón de envío -->
                        <button type="submit"
                                class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">
                            Confirmar Cambio de Plan
                        </button>
                    </form>

                    <!-- Información adicional -->
                    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">💡 Información Importante</h4>
                        <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                            <li>• El cambio será efectivo inmediatamente</li>
                            <li>• Se mantendrán todos sus datos y configuraciones</li>
                            <li>• La facturación comenzará según el período seleccionado</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Botón volver -->
            <div class="mt-8 text-center">
                <a href="{{ route('public.plans.grace-period') }}"
                   class="inline-flex items-center text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver a Selección de Planes
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function applyCoupon() {
            const couponCode = document.getElementById('coupon_code').value;
            const planId = {{ $plan->id }};
            const billingPeriod = document.querySelector('input[name="billing_period"]:checked').value;

            if (!couponCode) {
                showCouponResult('Por favor ingrese un código de cupón', 'error');
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
                    showCouponResult(`✅ Cupón aplicado: ${data.pricing.formatted_savings} de descuento`, 'success');
                } else {
                    showCouponResult(`❌ ${data.message}`, 'error');
                }
            })
            .catch(error => {
                showCouponResult('❌ Error al verificar el cupón', 'error');
            });
        }

        function showCouponResult(message, type) {
            const resultDiv = document.getElementById('coupon-result');
            resultDiv.textContent = message;
            resultDiv.className = `mt-2 text-sm ${type === 'success' ? 'text-green-600' : 'text-red-600'}`;
        }
    </script>
    @endpush
@endsection
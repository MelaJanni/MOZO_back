<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Plan {{ $plan->name }} - MOZO QR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-blue-600">MOZO QR</h1>
                </div>
                <div class="text-sm text-gray-600">
                    Checkout Seguro <i class="fas fa-lock text-green-500 ml-1"></i>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Formulario de Registro -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Crear tu cuenta</h2>

                @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('public.checkout.register') }}" method="POST" id="checkout-form">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                    <!-- Información Personal -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Información Personal</h3>

                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Contraseña</label>
                                <input type="password" name="password" id="password" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirmar Contraseña</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Período de Facturación -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Período de Facturación</h3>

                        <div class="space-y-3">
                            <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="billing_period" value="monthly" class="mr-3" {{ old('billing_period', 'monthly') == 'monthly' ? 'checked' : '' }}>
                                <div class="flex-1">
                                    <div class="font-medium">Mensual</div>
                                    <div class="text-sm text-gray-600">${{ number_format($plan->price_ars, 0) }} ARS/mes</div>
                                </div>
                            </label>

                            @if($plan->quarterly_discount_percentage > 0)
                            <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="billing_period" value="quarterly" class="mr-3" {{ old('billing_period') == 'quarterly' ? 'checked' : '' }}>
                                <div class="flex-1">
                                    <div class="font-medium">Trimestral</div>
                                    <div class="text-sm text-gray-600">
                                        ${{ number_format($plan->getPriceWithDiscount('quarterly'), 0) }} ARS/mes
                                        <span class="text-green-600 font-medium">(-{{ $plan->quarterly_discount_percentage }}%)</span>
                                    </div>
                                </div>
                            </label>
                            @endif

                            @if($plan->yearly_discount_percentage > 0)
                            <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="billing_period" value="yearly" class="mr-3" {{ old('billing_period') == 'yearly' ? 'checked' : '' }}>
                                <div class="flex-1">
                                    <div class="font-medium">Anual</div>
                                    <div class="text-sm text-gray-600">
                                        ${{ number_format($plan->getPriceWithDiscount('yearly'), 0) }} ARS/mes
                                        <span class="text-green-600 font-medium">(-{{ $plan->yearly_discount_percentage }}%)</span>
                                    </div>
                                </div>
                            </label>
                            @endif
                        </div>
                    </div>

                    <!-- Cupón de Descuento -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Cupón de Descuento (Opcional)</h3>

                        <div class="flex gap-2">
                            <input type="text" name="coupon_code" id="coupon_code" value="{{ old('coupon_code') }}" placeholder="Ingresa tu código de cupón"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" id="apply-coupon-btn"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-300">
                                Aplicar
                            </button>
                        </div>

                        <div id="coupon-result" class="mt-2"></div>
                    </div>

                    <!-- Método de Pago -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Método de Pago</h3>

                        <div class="space-y-3">
                            <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="mercadopago" class="mr-3" {{ old('payment_method', 'mercadopago') == 'mercadopago' ? 'checked' : '' }}>
                                <div class="flex-1">
                                    <div class="font-medium">Tarjeta de Crédito/Débito</div>
                                    <div class="text-sm text-gray-600">Procesado por Mercado Pago</div>
                                </div>
                                <img src="https://http2.mlstatic.com/storage/logos-api-admin/51b446b0-571c-11e8-9a2d-4b2bd7b1bf77-m.svg" alt="Mercado Pago" class="h-8">
                            </label>

                            <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="bank_transfer" class="mr-3" {{ old('payment_method') == 'bank_transfer' ? 'checked' : '' }}>
                                <div class="flex-1">
                                    <div class="font-medium">Transferencia Bancaria</div>
                                    <div class="text-sm text-gray-600">Recibirás los datos bancarios por email</div>
                                </div>
                                <i class="fas fa-university text-gray-400 text-2xl"></i>
                            </label>
                        </div>
                    </div>

                    <!-- Términos y Condiciones -->
                    <div class="mb-6">
                        <label class="flex items-start">
                            <input type="checkbox" name="terms" required class="mt-1 mr-3">
                            <span class="text-sm text-gray-700">
                                Acepto los <a href="#" class="text-blue-600 hover:underline">Términos de Servicio</a>
                                y la <a href="#" class="text-blue-600 hover:underline">Política de Privacidad</a>
                            </span>
                        </label>
                    </div>

                    <!-- Botón de Envío -->
                    <button type="submit"
                            class="w-full bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-300">
                        <i class="fas fa-lock mr-2"></i>
                        Completar Registro y Pago
                    </button>
                </form>
            </div>

            <!-- Resumen del Pedido -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Resumen del Pedido</h2>

                <!-- Plan Seleccionado -->
                <div class="border border-gray-200 rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $plan->name }}</h3>
                            <p class="text-gray-600">{{ $plan->description }}</p>
                        </div>
                        @if($plan->is_popular)
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">Popular</span>
                        @endif
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Mesas incluidas:</span>
                            <span class="font-medium">{{ $plan->getMaxTables() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Usuarios/Mozos:</span>
                            <span class="font-medium">{{ $plan->getMaxStaff() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Restaurantes:</span>
                            <span class="font-medium">{{ $plan->getMaxBusinesses() }}</span>
                        </div>
                    </div>

                    @if($plan->features)
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <h4 class="font-medium text-gray-900 mb-2">Características incluidas:</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            @foreach($plan->features as $feature)
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>

                <!-- Desglose de Precios -->
                <div class="space-y-3 text-sm" id="price-breakdown">
                    <div class="flex justify-between">
                        <span>Subtotal:</span>
                        <span id="subtotal">${{ number_format($plan->price_ars, 2) }} ARS</span>
                    </div>

                    <div id="discount-line" class="hidden">
                        <div class="flex justify-between text-green-600">
                            <span>Descuento:</span>
                            <span id="discount-amount">-$0.00 ARS</span>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-3">
                        <div class="flex justify-between font-semibold text-lg">
                            <span>Total:</span>
                            <span id="total">${{ number_format($plan->price_ars, 2) }} ARS</span>
                        </div>
                    </div>
                </div>

                @if($plan->hasTrialEnabled())
                <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-gift text-green-500 mr-2"></i>
                        <div>
                            <div class="font-medium text-green-800">{{ $plan->getTrialDays() }} días gratis</div>
                            <div class="text-sm text-green-700">Tu primera facturación será el {{ now()->addDays($plan->getTrialDays())->format('d/m/Y') }}</div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Garantías -->
                <div class="mt-6 space-y-3 text-sm text-gray-600">
                    <div class="flex items-center">
                        <i class="fas fa-shield-alt text-green-500 mr-2"></i>
                        <span>Pagos 100% seguros</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-undo text-blue-500 mr-2"></i>
                        <span>Cancela cuando quieras</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-headset text-purple-500 mr-2"></i>
                        <span>Soporte 24/7</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('checkout-form');
            const applyCouponBtn = document.getElementById('apply-coupon-btn');
            const couponCodeInput = document.getElementById('coupon_code');
            const couponResult = document.getElementById('coupon-result');
            const billingPeriodInputs = document.querySelectorAll('input[name="billing_period"]');

            let appliedCoupon = null;
            const basePrices = {
                monthly: {{ $plan->price_ars }},
                quarterly: {{ $plan->getPriceWithDiscount('quarterly') }},
                yearly: {{ $plan->getPriceWithDiscount('yearly') }}
            };

            // Actualizar precios cuando cambie el período
            billingPeriodInputs.forEach(input => {
                input.addEventListener('change', updatePricing);
            });

            // Aplicar cupón
            applyCouponBtn.addEventListener('click', applyCoupon);
            couponCodeInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    applyCoupon();
                }
            });

            function updatePricing() {
                const selectedPeriod = document.querySelector('input[name="billing_period"]:checked').value;
                const basePrice = basePrices[selectedPeriod];

                document.getElementById('subtotal').textContent = `$${basePrice.toFixed(2)} ARS`;

                if (appliedCoupon) {
                    // Recalcular descuento con nuevo precio
                    const discountAmount = calculateDiscount(basePrice, appliedCoupon);
                    const finalPrice = basePrice - discountAmount;

                    document.getElementById('discount-amount').textContent = `-$${discountAmount.toFixed(2)} ARS`;
                    document.getElementById('total').textContent = `$${finalPrice.toFixed(2)} ARS`;
                } else {
                    document.getElementById('total').textContent = `$${basePrice.toFixed(2)} ARS`;
                }
            }

            function applyCoupon() {
                const couponCode = couponCodeInput.value.trim();
                if (!couponCode) return;

                const selectedPeriod = document.querySelector('input[name="billing_period"]:checked').value;
                const planId = document.querySelector('input[name="plan_id"]').value;

                applyCouponBtn.disabled = true;
                applyCouponBtn.textContent = 'Aplicando...';

                fetch('{{ route("public.checkout.apply-coupon") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        coupon_code: couponCode,
                        plan_id: planId,
                        billing_period: selectedPeriod
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        appliedCoupon = data.coupon;
                        showCouponSuccess(data);
                        updatePricingWithCoupon(data.pricing);
                    } else {
                        showCouponError(data.message);
                    }
                })
                .catch(error => {
                    showCouponError('Error aplicando el cupón. Intenta nuevamente.');
                })
                .finally(() => {
                    applyCouponBtn.disabled = false;
                    applyCouponBtn.textContent = 'Aplicar';
                });
            }

            function calculateDiscount(basePrice, coupon) {
                if (coupon.type === 'percentage') {
                    return (basePrice * coupon.value) / 100;
                } else if (coupon.type === 'fixed') {
                    return Math.min(coupon.value, basePrice);
                }
                return 0;
            }

            function updatePricingWithCoupon(pricing) {
                document.getElementById('subtotal').textContent = pricing.formatted_base_price + ' ARS';
                document.getElementById('discount-amount').textContent = `-${pricing.formatted_savings} ARS`;
                document.getElementById('total').textContent = pricing.formatted_discounted_price + ' ARS';
                document.getElementById('discount-line').classList.remove('hidden');
            }

            function showCouponSuccess(data) {
                couponResult.innerHTML = `
                    <div class="bg-green-50 border border-green-200 text-green-700 px-3 py-2 rounded flex justify-between items-center">
                        <span>
                            <i class="fas fa-check mr-1"></i>
                            ${data.coupon.description || 'Cupón aplicado correctamente'}
                        </span>
                        <button type="button" onclick="removeCoupon()" class="text-green-600 hover:text-green-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            }

            function showCouponError(message) {
                couponResult.innerHTML = `
                    <div class="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        ${message}
                    </div>
                `;
                setTimeout(() => {
                    couponResult.innerHTML = '';
                }, 5000);
            }

            window.removeCoupon = function() {
                appliedCoupon = null;
                couponCodeInput.value = '';
                couponResult.innerHTML = '';
                document.getElementById('discount-line').classList.add('hidden');
                updatePricing();
            };

            // Validación del formulario
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const passwordConfirm = document.getElementById('password_confirmation').value;

                if (password !== passwordConfirm) {
                    e.preventDefault();
                    alert('Las contraseñas no coinciden');
                    return;
                }

                if (password.length < 8) {
                    e.preventDefault();
                    alert('La contraseña debe tener al menos 8 caracteres');
                    return;
                }
            });
        });
    </script>
</body>
</html>
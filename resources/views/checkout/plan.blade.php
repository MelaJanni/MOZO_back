<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - {{ $plan->name }} - MOZO QR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg text-white py-8">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <h1 class="text-3xl font-bold">Finalizar Compra</h1>
                <p class="mt-2 opacity-90">Plan: {{ $plan->name }}</p>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Plan Summary -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Resumen del Plan</h2>

                    <div class="border-b pb-4 mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">{{ $plan->name }}</h3>
                        <p class="text-gray-600 text-sm">{{ $plan->description }}</p>
                    </div>

                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Precio base:</span>
                            <span class="font-semibold">${{ number_format($plan->price, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-green-600" id="discount-row" style="display: none;">
                            <span>Descuento:</span>
                            <span id="discount-amount">-$0</span>
                        </div>
                        <div class="border-t pt-3">
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total:</span>
                                <span id="final-price">${{ number_format($plan->price, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    @if($plan->features)
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">Incluye:</h4>
                        <ul class="space-y-2">
                            @foreach($plan->features as $feature)
                            <li class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>

                <!-- Checkout Form -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Información de Pago</h2>

                    @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                    @endif

                    <form action="{{ route('checkout.process') }}" method="POST" id="checkout-form">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <input type="hidden" name="coupon_code" id="applied-coupon-code">

                        <!-- Coupon Code -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Código de cupón (opcional)</label>
                            <div class="flex space-x-2">
                                <input type="text" id="coupon-input"
                                       class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Ingresa tu código">
                                <button type="button" id="apply-coupon-btn"
                                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
                                    Aplicar
                                </button>
                            </div>
                            <div id="coupon-message" class="mt-2 text-sm"></div>
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Método de pago</label>
                            <div class="space-y-3">
                                @foreach($paymentMethods as $method)
                                <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="{{ $method }}"
                                           class="mr-3" {{ $loop->first ? 'checked' : '' }}>
                                    <div class="flex items-center">
                                        @if($method === 'mercado_pago')
                                        <div class="w-8 h-8 bg-blue-500 rounded mr-3 flex items-center justify-center">
                                            <span class="text-white text-xs font-bold">MP</span>
                                        </div>
                                        <div>
                                            <div class="font-medium">Mercado Pago</div>
                                            <div class="text-sm text-gray-500">Tarjetas, transferencia o efectivo</div>
                                        </div>
                                        @elseif($method === 'paypal')
                                        <div class="w-8 h-8 bg-blue-600 rounded mr-3 flex items-center justify-center">
                                            <span class="text-white text-xs font-bold">PP</span>
                                        </div>
                                        <div>
                                            <div class="font-medium">PayPal</div>
                                            <div class="text-sm text-gray-500">Pago seguro internacional</div>
                                        </div>
                                        @elseif($method === 'bank_transfer')
                                        <div class="w-8 h-8 bg-green-600 rounded mr-3 flex items-center justify-center">
                                            <span class="text-white text-xs font-bold">$</span>
                                        </div>
                                        <div>
                                            <div class="font-medium">Transferencia Bancaria</div>
                                            <div class="text-sm text-gray-500">Pago manual - Activación en 24hs</div>
                                        </div>
                                        @endif
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="mb-6">
                            <label class="flex items-start">
                                <input type="checkbox" class="mt-1 mr-3" required>
                                <span class="text-sm text-gray-600">
                                    Acepto los <a href="#" class="text-blue-600 underline">términos y condiciones</a>
                                    y la <a href="#" class="text-blue-600 underline">política de privacidad</a>
                                </span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200">
                            Proceder al Pago
                        </button>
                    </form>

                    <!-- Security Info -->
                    <div class="mt-6 text-center">
                        <div class="flex items-center justify-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                            </svg>
                            Pago 100% seguro y encriptado
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Coupon application logic
        document.getElementById('apply-coupon-btn').addEventListener('click', function() {
            const couponCode = document.getElementById('coupon-input').value.trim();
            const messageDiv = document.getElementById('coupon-message');
            const button = this;

            if (!couponCode) {
                messageDiv.innerHTML = '<span class="text-red-600">Ingresa un código de cupón</span>';
                return;
            }

            button.disabled = true;
            button.textContent = 'Aplicando...';

            fetch('{{ route("checkout.apply-coupon") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    code: couponCode,
                    plan_id: {{ $plan->id }}
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show discount
                    document.getElementById('discount-row').style.display = 'flex';
                    document.getElementById('discount-amount').textContent = '-$' + data.discount.toLocaleString();
                    document.getElementById('final-price').textContent = '$' + data.final_price.toLocaleString();
                    document.getElementById('applied-coupon-code').value = couponCode;

                    messageDiv.innerHTML = '<span class="text-green-600">✓ Cupón aplicado: ' + data.coupon.description + '</span>';

                    // Disable input and button
                    document.getElementById('coupon-input').disabled = true;
                    button.textContent = 'Aplicado';
                } else {
                    messageDiv.innerHTML = '<span class="text-red-600">' + data.message + '</span>';
                    button.disabled = false;
                    button.textContent = 'Aplicar';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<span class="text-red-600">Error aplicando cupón</span>';
                button.disabled = false;
                button.textContent = 'Aplicar';
            });
        });

        // Form validation
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                e.preventDefault();
                alert('Selecciona un método de pago');
            }
        });
    </script>
</body>
</html>
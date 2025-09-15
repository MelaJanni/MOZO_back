<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferencia Bancaria - MOZO QR</title>
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
                <h1 class="text-3xl font-bold">Transferencia Bancaria</h1>
                <p class="mt-2 opacity-90">Completa tu pago con los siguientes datos</p>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <!-- Payment Instructions -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Datos para la Transferencia</h2>
                    <p class="text-gray-600">Por favor, realiza la transferencia con los siguientes datos:</p>
                </div>

                <!-- Bank Details -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Banco:</label>
                            <p class="text-lg font-semibold text-gray-900">Banco de la Nación Argentina</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de cuenta:</label>
                            <p class="text-lg font-semibold text-gray-900">Cuenta Corriente</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número de cuenta:</label>
                            <div class="flex items-center">
                                <p class="text-lg font-semibold text-gray-900 mr-2" id="account-number">1234-5678-9012-3456</p>
                                <button onclick="copyToClipboard('account-number')" class="text-blue-600 hover:text-blue-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CBU:</label>
                            <div class="flex items-center">
                                <p class="text-lg font-semibold text-gray-900 mr-2" id="cbu">0110123456789012345678</p>
                                <button onclick="copyToClipboard('cbu')" class="text-blue-600 hover:text-blue-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CUIT:</label>
                            <p class="text-lg font-semibold text-gray-900">20-12345678-9</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Titular:</label>
                            <p class="text-lg font-semibold text-gray-900">MOZO QR S.A.S.</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Detalles del Pago</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Plan:</span>
                            <span class="font-semibold">{{ $plan->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Monto:</span>
                            <span class="font-semibold text-xl text-green-600">${{ number_format($subscription->price, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">ID de Suscripción:</span>
                            <span class="font-mono text-sm">{{ $subscription->id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Fecha límite:</span>
                            <span class="text-red-600 font-semibold">{{ now()->addDays(3)->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Important Instructions -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-yellow-600 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <div>
                        <h3 class="text-lg font-semibold text-yellow-800 mb-2">Instrucciones importantes:</h3>
                        <ul class="text-yellow-700 space-y-1">
                            <li>• Incluye tu <strong>ID de suscripción ({{ $subscription->id }})</strong> en el concepto de la transferencia</li>
                            <li>• Envía el comprobante por WhatsApp al <strong>+54 9 11 1234-5678</strong></li>
                            <li>• Tu suscripción se activará en un máximo de 24 horas hábiles</li>
                            <li>• El pago debe realizarse dentro de los próximos 3 días</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Contact Actions -->
            <div class="grid md:grid-cols-2 gap-4 mb-8">
                <a href="https://wa.me/5491112345678?text=Hola%2C%20realicé%20una%20transferencia%20para%20la%20suscripción%20{{ $subscription->id }}"
                   target="_blank"
                   class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg text-center transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.108"></path>
                    </svg>
                    Enviar Comprobante por WhatsApp
                </a>

                <a href="mailto:pagos@mozoqr.com?subject=Comprobante de pago - Suscripción {{ $subscription->id }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg text-center transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Enviar por Email
                </a>
            </div>

            <!-- Status Check -->
            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">¿Ya realizaste la transferencia?</h3>
                <p class="text-gray-600 mb-4">Puedes verificar el estado de tu pago en cualquier momento</p>
                <a href="{{ route('checkout.index') }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                    Verificar Estado del Pago →
                </a>
            </div>
        </div>
    </main>

    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;

            navigator.clipboard.writeText(text).then(function() {
                // Show success feedback
                const originalText = element.textContent;
                element.textContent = '¡Copiado!';
                element.classList.add('text-green-600');

                setTimeout(() => {
                    element.textContent = originalText;
                    element.classList.remove('text-green-600');
                }, 2000);
            }).catch(function(err) {
                console.error('Error al copiar: ', err);
            });
        }
    </script>
</body>
</html>
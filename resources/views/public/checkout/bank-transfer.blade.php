<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferencia Bancaria - MOZO QR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                    Pago por Transferencia <i class="fas fa-university text-blue-500 ml-1"></i>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Información de la transferencia -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-university text-blue-600 text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Datos para Transferencia</h2>
                    <p class="text-gray-600 mt-2">Realiza la transferencia con los siguientes datos</p>
                </div>

                <!-- Datos bancarios -->
                <div class="space-y-6">
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Información Bancaria</h3>

                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                <span class="font-medium text-gray-700">Banco:</span>
                                <span class="text-gray-900">{{ $bankDetails['bank_name'] }}</span>
                            </div>

                            <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                <span class="font-medium text-gray-700">Titular:</span>
                                <span class="text-gray-900">{{ $bankDetails['account_holder'] }}</span>
                            </div>

                            <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                <span class="font-medium text-gray-700">Número de Cuenta:</span>
                                <div class="flex items-center">
                                    <span class="text-gray-900 mr-2" id="account-number">{{ $bankDetails['account_number'] }}</span>
                                    <button onclick="copyToClipboard('account-number')" class="text-blue-600 hover:text-blue-700">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                <span class="font-medium text-gray-700">CBU:</span>
                                <div class="flex items-center">
                                    <span class="text-gray-900 mr-2" id="cbu">{{ $bankDetails['cbu'] }}</span>
                                    <button onclick="copyToClipboard('cbu')" class="text-blue-600 hover:text-blue-700">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="flex justify-between items-center py-2">
                                <span class="font-medium text-gray-700">Alias:</span>
                                <div class="flex items-center">
                                    <span class="text-gray-900 mr-2" id="alias">{{ $bankDetails['alias'] }}</span>
                                    <button onclick="copyToClipboard('alias')" class="text-blue-600 hover:text-blue-700">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monto a transferir -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-blue-900 mb-2">Monto a Transferir</h3>
                        <div class="flex justify-between items-center">
                            <span class="text-blue-700">Total a pagar:</span>
                            <div class="flex items-center">
                                <span class="text-2xl font-bold text-blue-900 mr-2" id="amount">${{ number_format($subscription->price_at_creation, 2) }} ARS</span>
                                <button onclick="copyToClipboard('amount')" class="text-blue-600 hover:text-blue-700">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <p class="text-sm text-blue-700 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Importante: Transfiere exactamente este monto
                        </p>
                    </div>

                    <!-- Referencia -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-yellow-900 mb-2">Referencia Obligatoria</h3>
                        <div class="flex justify-between items-center">
                            <span class="text-yellow-700">ID de Suscripción:</span>
                            <div class="flex items-center">
                                <span class="text-lg font-mono font-bold text-yellow-900 mr-2" id="reference">SUB-{{ $subscription->id }}</span>
                                <button onclick="copyToClipboard('reference')" class="text-yellow-600 hover:text-yellow-700">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <p class="text-sm text-yellow-700 mt-2">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Incluye esta referencia en tu transferencia
                        </p>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="mt-8 space-y-4">
                    <button onclick="copyAllData()"
                            class="w-full bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-300">
                        <i class="fas fa-copy mr-2"></i>
                        Copiar Todos los Datos
                    </button>

                    <div class="grid grid-cols-2 gap-4">
                        <a href="mailto:?subject=Datos%20para%20Transferencia%20MOZO%20QR&body={{ urlencode('Datos para transferencia:' . "\n\n" . 'Banco: ' . $bankDetails['bank_name'] . "\n" . 'CBU: ' . $bankDetails['cbu'] . "\n" . 'Alias: ' . $bankDetails['alias'] . "\n" . 'Monto: $' . number_format($subscription->price_at_creation, 2) . ' ARS' . "\n" . 'Referencia: SUB-' . $subscription->id) }}"
                           class="bg-gray-100 text-gray-700 font-semibold py-3 px-4 rounded-lg hover:bg-gray-200 transition duration-300 text-center">
                            <i class="fas fa-envelope mr-2"></i>
                            Enviar por Email
                        </a>

                        <button onclick="shareData()"
                                class="bg-gray-100 text-gray-700 font-semibold py-3 px-4 rounded-lg hover:bg-gray-200 transition duration-300">
                            <i class="fas fa-share mr-2"></i>
                            Compartir
                        </button>
                    </div>
                </div>
            </div>

            <!-- Información del pedido e instrucciones -->
            <div class="space-y-6">
                <!-- Resumen del pedido -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Resumen del Pedido</h2>

                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Plan:</span>
                            <span class="font-medium">{{ $subscription->plan->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Período:</span>
                            <span class="font-medium">{{ ucfirst($subscription->billing_period) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Email:</span>
                            <span class="font-medium">{{ $subscription->user->email }}</span>
                        </div>
                        @if($subscription->trial_ends_at)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Prueba hasta:</span>
                            <span class="font-medium">{{ $subscription->trial_ends_at->format('d/m/Y') }}</span>
                        </div>
                        @endif
                        <div class="border-t pt-4">
                            <div class="flex justify-between text-lg font-semibold">
                                <span>Total:</span>
                                <span>${{ number_format($subscription->price_at_creation, 2) }} ARS</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instrucciones -->
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Instrucciones</h3>

                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-4 mt-1">
                                <span class="text-blue-600 font-bold text-sm">1</span>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Realiza la transferencia</h4>
                                <p class="text-sm text-gray-600">Usa los datos bancarios proporcionados y transfiere el monto exacto</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-4 mt-1">
                                <span class="text-blue-600 font-bold text-sm">2</span>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Incluye la referencia</h4>
                                <p class="text-sm text-gray-600">Muy importante: incluye SUB-{{ $subscription->id }} en el concepto</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-4 mt-1">
                                <span class="text-blue-600 font-bold text-sm">3</span>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Espera la confirmación</h4>
                                <p class="text-sm text-gray-600">Te enviaremos un email cuando confirmemos el pago (1-2 días hábiles)</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-4 mt-1">
                                <span class="text-green-600 font-bold text-sm">4</span>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">¡Listo!</h4>
                                <p class="text-sm text-gray-600">Tu cuenta será activada y podrás comenzar a usar MOZO QR</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información importante -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                    <h3 class="font-semibold text-yellow-900 mb-2">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Información Importante
                    </h3>
                    <ul class="text-sm text-yellow-800 space-y-1">
                        <li>• Los pagos se procesan de lunes a viernes, de 9:00 a 18:00</li>
                        <li>• Envía el comprobante a pagos@mozoqr.com para acelerar el proceso</li>
                        <li>• Si tienes dudas, contáctanos por WhatsApp: +54 911 2345-6789</li>
                        <li>• Tu cuenta se activará dentro de 24-48 horas hábiles</li>
                    </ul>
                </div>

                <!-- Soporte -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">¿Necesitas Ayuda?</h3>
                    <div class="space-y-3">
                        <a href="mailto:pagos@mozoqr.com" class="flex items-center text-blue-600 hover:text-blue-700">
                            <i class="fas fa-envelope mr-3"></i>
                            <span>pagos@mozoqr.com</span>
                        </a>
                        <a href="https://wa.me/5491123456789" class="flex items-center text-green-600 hover:text-green-700" target="_blank">
                            <i class="fab fa-whatsapp mr-3"></i>
                            <span>+54 911 2345-6789</span>
                        </a>
                        <a href="tel:+5491123456789" class="flex items-center text-blue-600 hover:text-blue-700">
                            <i class="fas fa-phone mr-3"></i>
                            <span>Llamar Soporte</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast notification -->
    <div id="toast" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full opacity-0 transition-all duration-300">
        <i class="fas fa-check mr-2"></i>
        <span id="toast-message">Copiado al portapapeles</span>
    </div>

    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent.trim();

            navigator.clipboard.writeText(text).then(() => {
                showToast('Copiado: ' + text);
            }).catch(() => {
                // Fallback para navegadores más antiguos
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast('Copiado: ' + text);
            });
        }

        function copyAllData() {
            const allData = `Datos para Transferencia - MOZO QR

Banco: {{ $bankDetails['bank_name'] }}
Titular: {{ $bankDetails['account_holder'] }}
Número de Cuenta: {{ $bankDetails['account_number'] }}
CBU: {{ $bankDetails['cbu'] }}
Alias: {{ $bankDetails['alias'] }}

Monto: ${{ number_format($subscription->price_at_creation, 2) }} ARS
Referencia: SUB-{{ $subscription->id }}

¡Importante! Incluye la referencia en tu transferencia.`;

            navigator.clipboard.writeText(allData).then(() => {
                showToast('Todos los datos copiados al portapapeles');
            }).catch(() => {
                console.error('Error al copiar');
            });
        }

        function shareData() {
            if (navigator.share) {
                navigator.share({
                    title: 'Datos para Transferencia - MOZO QR',
                    text: `CBU: {{ $bankDetails['cbu'] }}\nAlias: {{ $bankDetails['alias'] }}\nMonto: ${{ number_format($subscription->price_at_creation, 2) }} ARS\nReferencia: SUB-{{ $subscription->id }}`
                });
            } else {
                copyAllData();
            }
        }

        function showToast(message) {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');

            toastMessage.textContent = message;
            toast.classList.remove('translate-x-full', 'opacity-0');
            toast.classList.add('translate-x-0', 'opacity-100');

            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                toast.classList.remove('translate-x-0', 'opacity-100');
            }, 3000);
        }
    </script>
</body>
</html>
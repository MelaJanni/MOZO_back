<x-filament-panels::page>
    <div class="space-y-8">
        {{-- Hero Section --}}
        <div class="text-center bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl p-8 text-white">
            <h1 class="text-4xl font-bold mb-4">Digitaliza tu Restaurante</h1>
            <p class="text-xl text-primary-100 mb-6">
                Gestiona mesas, menús y pedidos con códigos QR. Simple, rápido y eficiente.
            </p>
            <x-filament::button
                :href="route('public.checkout.index')"
                size="lg"
                color="white"
                outlined
            >
                Ver Planes
            </x-filament::button>
        </div>

        {{-- Features Section --}}
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">¿Por qué elegir MOZO QR?</h2>
            <p class="text-xl text-gray-600 dark:text-gray-300 mb-8">
                Simplifica la gestión de tu restaurante con nuestra plataforma todo en uno
            </p>

            <div class="grid md:grid-cols-3 gap-6">
                <x-filament::card>
                    <div class="text-center p-4">
                        <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <x-heroicon-o-qr-code class="w-8 h-8 text-primary-600 dark:text-primary-400" />
                        </div>
                        <h3 class="text-lg font-semibold mb-2">Códigos QR Inteligentes</h3>
                        <p class="text-gray-600 dark:text-gray-300">Los clientes escanean el QR de la mesa y acceden al menú digital instantáneamente</p>
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-center p-4">
                        <div class="w-16 h-16 bg-success-100 dark:bg-success-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <x-heroicon-o-device-phone-mobile class="w-8 h-8 text-success-600 dark:text-success-400" />
                        </div>
                        <h3 class="text-lg font-semibold mb-2">Gestión Móvil</h3>
                        <p class="text-gray-600 dark:text-gray-300">App móvil para mozos con notificaciones en tiempo real y gestión de pedidos</p>
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-center p-4">
                        <div class="w-16 h-16 bg-info-100 dark:bg-info-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <x-heroicon-o-chart-bar class="w-8 h-8 text-info-600 dark:text-info-400" />
                        </div>
                        <h3 class="text-lg font-semibold mb-2">Analytics Avanzados</h3>
                        <p class="text-gray-600 dark:text-gray-300">Reportes detallados de ventas, mesas más populares y rendimiento del personal</p>
                    </div>
                </x-filament::card>
            </div>
        </div>

        {{-- Plans Section --}}
        <div>
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Planes que se adaptan a tu negocio</h2>
                <p class="text-xl text-gray-600 dark:text-gray-300">Elige el plan perfecto para el tamaño de tu restaurante</p>
            </div>

            <div class="grid md:grid-cols-{{ $plans->count() > 2 ? '3' : $plans->count() }} gap-6">
                @foreach($plans as $plan)
                <x-filament::card class="relative {{ $plan->is_popular ? 'ring-2 ring-primary-500 dark:ring-primary-400' : '' }}">
                    @if($plan->is_popular)
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                        <x-filament::badge color="primary">
                            Más Popular
                        </x-filament::badge>
                    </div>
                    @endif

                    @if($plan->is_featured)
                    <div class="absolute -top-3 right-4">
                        <x-filament::badge color="success">
                            Recomendado
                        </x-filament::badge>
                    </div>
                    @endif

                    <div class="text-center p-6">
                        <h3 class="text-2xl font-bold mb-2">{{ $plan->name }}</h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-6">{{ $plan->description }}</p>

                        <div class="mb-6">
                            <span class="text-4xl font-bold text-gray-900 dark:text-white">${{ number_format($plan->price_ars, 0) }}</span>
                            <span class="text-gray-600 dark:text-gray-300">/mes</span>
                        </div>

                        @if($plan->hasTrialEnabled())
                        <x-filament::badge color="success" class="mb-6">
                            <x-heroicon-m-gift class="w-4 h-4 mr-1" />
                            {{ $plan->getTrialDays() }} días gratis
                        </x-filament::badge>
                        @endif

                        <div class="mb-6">
                            <x-filament::button
                                :href="route('filament.public.pages.checkout', ['planId' => $plan->id])"
                                size="lg"
                                :color="$plan->is_popular ? 'primary' : 'gray'"
                                class="w-full"
                            >
                                Empezar Ahora
                            </x-filament::button>
                        </div>

                        <div class="space-y-3 text-left">
                            <h4 class="font-semibold text-center mb-4">Incluye:</h4>
                            <div class="flex items-center">
                                <x-heroicon-m-check class="w-5 h-5 text-success-500 mr-3" />
                                <span>Hasta {{ $plan->getMaxTables() }} mesas</span>
                            </div>
                            <div class="flex items-center">
                                <x-heroicon-m-check class="w-5 h-5 text-success-500 mr-3" />
                                <span>{{ $plan->getMaxStaff() }} usuarios/mozos</span>
                            </div>
                            <div class="flex items-center">
                                <x-heroicon-m-check class="w-5 h-5 text-success-500 mr-3" />
                                <span>{{ $plan->getMaxBusinesses() }} {{ $plan->getMaxBusinesses() == 1 ? 'restaurante' : 'restaurantes' }}</span>
                            </div>
                            @if($plan->features)
                                @foreach($plan->features as $feature)
                                <div class="flex items-center">
                                    <x-heroicon-m-check class="w-5 h-5 text-success-500 mr-3" />
                                    <span>{{ $feature }}</span>
                                </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </x-filament::card>
                @endforeach
            </div>
        </div>

        {{-- FAQ Section --}}
        <x-filament::card>
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Preguntas Frecuentes</h2>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">¿Cómo funciona el período de prueba?</h3>
                    <p class="text-gray-600 dark:text-gray-300">Puedes probar MOZO QR completamente gratis durante los días indicados en cada plan. No se requiere información de pago para comenzar.</p>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">¿Puedo cambiar de plan en cualquier momento?</h3>
                    <p class="text-gray-600 dark:text-gray-300">Sí, puedes actualizar o degradar tu plan en cualquier momento desde tu panel de control.</p>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">¿Qué métodos de pago aceptan?</h3>
                    <p class="text-gray-600 dark:text-gray-300">Aceptamos pagos con tarjeta de crédito/débito a través de Mercado Pago y transferencias bancarias.</p>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">¿Hay permanencia?</h3>
                    <p class="text-gray-600 dark:text-gray-300">No, puedes cancelar tu suscripción en cualquier momento sin penalizaciones.</p>
                </div>
            </div>
        </x-filament::card>

        {{-- CTA Section --}}
        <div class="text-center bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl p-8 text-white">
            <h2 class="text-3xl font-bold mb-4">¿Listo para digitalizar tu restaurante?</h2>
            <p class="text-xl text-primary-100 mb-6">Únete a cientos de restaurantes que ya confían en MOZO QR</p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <x-filament::button
                    :href="route('filament.public.pages.checkout')"
                    size="lg"
                    color="white"
                    outlined
                >
                    Empezar Ahora
                </x-filament::button>

                <x-filament::button
                    href="mailto:info@mozoqr.com"
                    size="lg"
                    color="white"
                    outlined
                >
                    Contactar Ventas
                </x-filament::button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
<x-filament-panels::page>
    <div class="max-w-2xl mx-auto text-center">
        {{-- Ícono de éxito --}}
        <div class="w-20 h-20 bg-success-100 dark:bg-success-900 rounded-full flex items-center justify-center mx-auto mb-6">
            <x-heroicon-o-check class="w-12 h-12 text-success-600 dark:text-success-400" />
        </div>

        {{-- Mensaje principal --}}
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">¡Pago Exitoso!</h1>
        <p class="text-xl text-gray-600 dark:text-gray-300 mb-8">
            Tu cuenta ha sido creada y tu suscripción está activa
        </p>

        {{-- Información de la cuenta --}}
        <x-filament::card class="mb-8">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-primary-600 dark:text-primary-400 mb-4">¿Qué sigue ahora?</h2>
                <div class="space-y-4 text-left">
                    <div class="flex items-start">
                        <x-heroicon-o-envelope class="w-6 h-6 text-primary-500 mt-1 mr-3" />
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Revisa tu email</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">Te hemos enviado los detalles de tu cuenta y links importantes</div>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <x-heroicon-o-device-phone-mobile class="w-6 h-6 text-primary-500 mt-1 mr-3" />
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Descarga la app móvil</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">Para que tus mozos gestionen las mesas desde sus teléfonos</div>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <x-heroicon-o-cog-6-tooth class="w-6 h-6 text-primary-500 mt-1 mr-3" />
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Configura tu restaurante</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">Agrega mesas, menús y personal desde el panel de administración</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::card>

        {{-- Botones de acción --}}
        <div class="space-y-4 mb-8">
            <x-filament::button
                href="/admin"
                size="lg"
                class="w-full"
                color="primary"
            >
                <x-heroicon-m-chart-bar class="w-5 h-5 mr-2" />
                Ir al Panel de Administración
            </x-filament::button>

            <div class="grid grid-cols-2 gap-4">
                <x-filament::button
                    href="#"
                    color="gray"
                    outlined
                    class="w-full"
                >
                    <x-heroicon-m-device-phone-mobile class="w-5 h-5 mr-2" />
                    App iOS
                </x-filament::button>
                <x-filament::button
                    href="#"
                    color="gray"
                    outlined
                    class="w-full"
                >
                    <x-heroicon-m-device-phone-mobile class="w-5 h-5 mr-2" />
                    App Android
                </x-filament::button>
            </div>
        </div>

        {{-- Información de soporte --}}
        <x-filament::card>
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">¿Necesitas ayuda?</h3>
                <div class="flex justify-center space-x-6 text-sm">
                    <a href="mailto:soporte@mozoqr.com" class="text-primary-600 dark:text-primary-400 hover:underline">
                        <x-heroicon-m-envelope class="w-4 h-4 inline mr-1" />
                        Email de Soporte
                    </a>
                    <a href="tel:+5491123456789" class="text-primary-600 dark:text-primary-400 hover:underline">
                        <x-heroicon-m-phone class="w-4 h-4 inline mr-1" />
                        Llamar Soporte
                    </a>
                    <a href="#" class="text-primary-600 dark:text-primary-400 hover:underline">
                        <x-heroicon-m-book-open class="w-4 h-4 inline mr-1" />
                        Centro de Ayuda
                    </a>
                </div>
            </div>
        </x-filament::card>

        {{-- Mensaje de bienvenida --}}
        <div class="mt-8 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-lg p-6">
            <h3 class="text-xl font-bold mb-2">¡Bienvenido a MOZO QR!</h3>
            <p class="text-primary-100">
                Estamos emocionados de ayudarte a digitalizar tu restaurante y mejorar la experiencia de tus clientes.
            </p>
        </div>

        {{-- Recursos adicionales --}}
        <div class="mt-8 grid md:grid-cols-3 gap-6">
            <x-filament::card>
                <div class="text-center p-4">
                    <x-heroicon-o-play-circle class="w-12 h-12 text-primary-500 mx-auto mb-4" />
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Video Tutorial</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Aprende a configurar tu cuenta en 5 minutos</p>
                    <x-filament::button href="#" size="sm" color="primary" outlined>
                        Ver Video
                    </x-filament::button>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-center p-4">
                    <x-heroicon-o-users class="w-12 h-12 text-success-500 mx-auto mb-4" />
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Comunidad</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Únete a otros restaurantes que usan MOZO QR</p>
                    <x-filament::button href="#" size="sm" color="success" outlined>
                        Unirse
                    </x-filament::button>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-center p-4">
                    <x-heroicon-o-calendar class="w-12 h-12 text-info-500 mx-auto mb-4" />
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Onboarding</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Agenda una sesión personalizada</p>
                    <x-filament::button href="#" size="sm" color="info" outlined>
                        Agendar
                    </x-filament::button>
                </div>
            </x-filament::card>
        </div>
    </div>
</x-filament-panels::page>
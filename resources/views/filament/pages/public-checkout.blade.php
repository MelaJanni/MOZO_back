<x-filament-panels::page>
    <div class="max-w-4xl mx-auto">
        <div class="grid lg:grid-cols-2 gap-8">
            {{-- Formulario --}}
            <div>
                <x-filament-panels::form wire:submit="register">
                    {{ $this->form }}

                    <x-slot name="actions">
                        {{ $this->registerAction }}
                    </x-slot>
                </x-filament-panels::form>
            </div>

            {{-- Resumen del Pedido --}}
            <div class="space-y-6">
                @if($plan)
                <x-filament::card>
                    <div class="p-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Resumen del Pedido</h2>

                        {{-- Plan seleccionado --}}
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 mb-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold">{{ $plan->name }}</h3>
                                    <p class="text-gray-600 dark:text-gray-300">{{ $plan->description }}</p>
                                </div>
                                @if($plan->is_popular)
                                <x-filament::badge color="primary">Popular</x-filament::badge>
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
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-2">Características incluidas:</h4>
                                <div class="space-y-1">
                                    @foreach($plan->features as $feature)
                                    <div class="flex items-center text-sm">
                                        <x-heroicon-m-check class="w-4 h-4 text-success-500 mr-2" />
                                        <span>{{ $feature }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- Desglose de precios --}}
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span>Subtotal:</span>
                                <span>${{ number_format($plan->price_ars, 2) }} ARS</span>
                            </div>

                            @if($appliedCoupon)
                            <div class="flex justify-between text-success-600 dark:text-success-400">
                                <span>Descuento ({{ $appliedCoupon->code }}):</span>
                                <span>-${{ number_format($plan->price_ars - $plan->getDiscountedPrice($appliedCoupon), 2) }} ARS</span>
                            </div>
                            @endif

                            <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                                <div class="flex justify-between font-semibold text-lg">
                                    <span>Total:</span>
                                    <span>${{ number_format($appliedCoupon ? $plan->getDiscountedPrice($appliedCoupon) : $plan->price_ars, 2) }} ARS</span>
                                </div>
                            </div>
                        </div>

                        @if($plan->hasTrialEnabled())
                        <x-filament::card class="mt-6 bg-success-50 dark:bg-success-900/20 border-success-200 dark:border-success-800">
                            <div class="flex items-center p-4">
                                <x-heroicon-m-gift class="w-6 h-6 text-success-500 mr-3" />
                                <div>
                                    <div class="font-medium text-success-800 dark:text-success-200">{{ $plan->getTrialDays() }} días gratis</div>
                                    <div class="text-sm text-success-700 dark:text-success-300">Tu primera facturación será el {{ now()->addDays($plan->getTrialDays())->format('d/m/Y') }}</div>
                                </div>
                            </div>
                        </x-filament::card>
                        @endif
                    </div>
                </x-filament::card>
                @endif

                {{-- Garantías --}}
                <x-filament::card>
                    <div class="p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Garantías</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center">
                                <x-heroicon-m-shield-check class="w-5 h-5 text-success-500 mr-3" />
                                <span>Pagos 100% seguros</span>
                            </div>
                            <div class="flex items-center">
                                <x-heroicon-m-arrow-path class="w-5 h-5 text-primary-500 mr-3" />
                                <span>Cancela cuando quieras</span>
                            </div>
                            <div class="flex items-center">
                                <x-heroicon-m-chat-bubble-left-ellipsis class="w-5 h-5 text-info-500 mr-3" />
                                <span>Soporte 24/7</span>
                            </div>
                        </div>
                    </div>
                </x-filament::card>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
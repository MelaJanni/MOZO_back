@extends('layouts.filament-public')

@section('title', 'Pago Cancelado - MOZO QR')
@section('description', 'Tu pago ha sido cancelado. Puedes intentar nuevamente cuando gustes.')

@section('content')
    <div class="py-16">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8">
                <!-- Error Icon -->
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>

                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    Pago Cancelado
                </h1>

                <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">
                    Tu pago ha sido cancelado. No se ha realizado ningún cargo a tu cuenta.
                </p>

                <!-- Action Buttons -->
                <div class="space-y-4 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center">
                    <a href="{{ route('public.checkout.index') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition duration-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                        Intentar Nuevamente
                    </a>

                    <a href="{{ route('public.plans.index') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-300">
                        Ver Planes
                    </a>
                </div>

                <!-- Help Section -->
                <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">¿Necesitas ayuda?</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Si experimentaste algún problema durante el proceso de pago, no dudes en contactarnos.
                    </p>
                    <div class="space-y-2 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center text-sm">
                        <a href="mailto:soporte@mozoqr.com" class="text-primary-600 dark:text-primary-400 hover:underline">
                            soporte@mozoqr.com
                        </a>
                        <span class="hidden sm:inline text-gray-400">|</span>
                        <a href="#" class="text-primary-600 dark:text-primary-400 hover:underline">
                            Centro de Ayuda
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
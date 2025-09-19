@extends('layouts.filament-public')

@section('title', '¡Pago Exitoso! - MOZO QR')
@section('description', 'Tu cuenta ha sido creada exitosamente. Bienvenido a MOZO QR.')

@section('content')
    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <!-- Ícono de éxito -->
            <div class="w-20 h-20 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <!-- Mensaje principal -->
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">¡Pago Exitoso!</h1>
            <p class="text-xl text-gray-600 dark:text-gray-300 mb-8">
                Tu cuenta ha sido creada y tu suscripción está activa
            </p>

            <!-- Información de la cuenta -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8 mb-8">
                <h2 class="text-lg font-semibold text-primary-600 dark:text-primary-400 mb-4">¿Qué sigue ahora?</h2>
                <div class="space-y-4 text-left">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-primary-500 mt-1 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 7.89a2 2 0 002.82 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Revisa tu email</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">Te hemos enviado los detalles de tu cuenta y links importantes</div>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-primary-500 mt-1 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Descarga la app móvil</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">Para que tus mozos gestionen las mesas desde sus teléfonos</div>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-primary-500 mt-1 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Configura tu restaurante</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">Agrega mesas, menús y personal desde el panel de administración</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="space-y-4 mb-8">
                <a href="/admin"
                   class="w-full bg-primary-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-primary-700 transition duration-300 inline-block">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00-2-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 00-2 2h2a2 2 0 002-2V9a2 2 0 00-2-2V5a2 2 0 00-2-2"></path>
                    </svg>
                    Ir al Panel de Administración
                </a>

                <div class="grid grid-cols-2 gap-4">
                    <a href="#"
                       class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold py-3 px-4 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-300 inline-block">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        App iOS
                    </a>
                    <a href="#"
                       class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold py-3 px-4 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-300 inline-block">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        App Android
                    </a>
                </div>
            </div>

            <!-- Información de soporte -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">¿Necesitas ayuda?</h3>
                <div class="flex justify-center space-x-6 text-sm">
                    <a href="mailto:soporte@mozoqr.com" class="text-primary-600 dark:text-primary-400 hover:underline">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 7.89a2 2 0 002.82 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Email de Soporte
                    </a>
                    <a href="tel:+5491123456789" class="text-primary-600 dark:text-primary-400 hover:underline">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        Llamar Soporte
                    </a>
                    <a href="#" class="text-primary-600 dark:text-primary-400 hover:underline">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        Centro de Ayuda
                    </a>
                </div>
            </div>

            <!-- Mensaje de bienvenida -->
            <div class="bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-lg p-6 mb-8">
                <h3 class="text-xl font-bold mb-2">¡Bienvenido a MOZO QR!</h3>
                <p class="text-primary-100">
                    Estamos emocionados de ayudarte a digitalizar tu restaurante y mejorar la experiencia de tus clientes.
                </p>
            </div>

            <!-- Recursos adicionales -->
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 text-center">
                    <svg class="w-12 h-12 text-primary-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.01M15 10h1.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Video Tutorial</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Aprende a configurar tu cuenta en 5 minutos</p>
                    <a href="#" class="text-primary-600 dark:text-primary-400 hover:underline text-sm font-medium">
                        Ver Video
                    </a>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 text-center">
                    <svg class="w-12 h-12 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Comunidad</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Únete a otros restaurantes que usan MOZO QR</p>
                    <a href="#" class="text-green-600 dark:text-green-400 hover:underline text-sm font-medium">
                        Unirse
                    </a>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 text-center">
                    <svg class="w-12 h-12 text-purple-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0h6m-6 0l6 6-6 6H4v-6H2a2 2 0 01-2-2V9a2 2 0 012-2h2z"></path>
                    </svg>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Onboarding</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">Agenda una sesión personalizada</p>
                    <a href="#" class="text-purple-600 dark:text-purple-400 hover:underline text-sm font-medium">
                        Agendar
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Efecto de confetti simple
    function createConfetti() {
        const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];

        for (let i = 0; i < 50; i++) {
            const confetti = document.createElement('div');
            confetti.style.position = 'fixed';
            confetti.style.width = '8px';
            confetti.style.height = '8px';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.top = '-10px';
            confetti.style.zIndex = '1000';
            confetti.style.pointerEvents = 'none';
            confetti.style.borderRadius = '50%';

            document.body.appendChild(confetti);

            const duration = Math.random() * 2000 + 1000;

            confetti.animate([
                { transform: 'translateY(0px)', opacity: 1 },
                { transform: `translateY(${window.innerHeight + 20}px)`, opacity: 0 }
            ], {
                duration: duration,
                easing: 'linear'
            }).addEventListener('finish', () => {
                confetti.remove();
            });
        }
    }

    // Ejecutar confetti al cargar
    window.addEventListener('load', () => {
        setTimeout(createConfetti, 500);
    });
</script>
@endpush
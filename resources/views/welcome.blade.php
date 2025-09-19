@extends('layouts.mozo-public')

@section('title', 'MOZO QR - Digitaliza tu restaurante')
@section('description', 'Transforma tu restaurante con códigos QR inteligentes. Gestión de mesas, menús digitales y notificaciones en tiempo real. ¡Prueba gratis 14 días!')

@section('content')
<!-- Hero Section -->
<section class="relative overflow-hidden bg-gradient-to-br from-mozo-900 via-mozo-800 to-mozo-700 text-white py-20 lg:py-32">
    <!-- Background decoration -->
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="absolute top-0 left-0 w-72 h-72 bg-mozo-500/30 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-mozo-300/30 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-mozo-400/30 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div class="space-y-8">
                <div class="space-y-4">
                    <span class="inline-block px-4 py-2 bg-mozo-500/20 border border-mozo-500/30 rounded-full text-mozo-200 text-sm font-medium">
                        🚀 Nuevo: Dashboard mejorado
                    </span>
                    <h1 class="text-4xl lg:text-6xl font-bold leading-tight">
                        Digitaliza tu
                        <span class="bg-gradient-to-r from-mozo-300 to-mozo-100 bg-clip-text text-transparent">
                            restaurante
                        </span>
                        con QR
                    </h1>
                    <p class="text-xl text-gray-300 leading-relaxed">
                        Códigos QR inteligentes para mesas, menús digitales interactivos y
                        notificaciones en tiempo real. Todo lo que necesitas para modernizar tu negocio.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('public.plans.index') }}"
                       class="bg-mozo-500 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-mozo-600 transform hover:scale-105 transition-all duration-300 shadow-xl hover:shadow-2xl text-center">
                        Comenzar gratis
                        <span class="block text-sm font-normal opacity-90">14 días de prueba</span>
                    </a>
                    <a href="#demo"
                       class="border-2 border-white/30 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-white/10 transition-all duration-300 text-center backdrop-blur-sm">
                        Ver demo
                    </a>
                </div>

                <div class="flex items-center space-x-8 text-sm text-gray-300">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Sin instalación</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Configuración en 5 minutos</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Soporte 24/7</span>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="relative z-10 bg-white/10 backdrop-blur-lg rounded-2xl p-8 shadow-2xl border border-white/20">
                    <div class="bg-white rounded-xl p-6 shadow-xl">
                        <div class="text-center space-y-4">
                            <div class="w-24 h-24 bg-gradient-to-br from-mozo-500 to-mozo-600 rounded-xl mx-auto flex items-center justify-center">
                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Mesa 5</h3>
                            <p class="text-gray-600">Escanea para ver el menú</p>
                            <div class="bg-gray-100 p-4 rounded-lg">
                                <div class="w-32 h-32 bg-white rounded-lg mx-auto flex items-center justify-center border-2 border-dashed border-gray-300">
                                    <span class="text-xs text-gray-500">Código QR</span>
                                </div>
                            </div>
                            <button class="w-full bg-mozo-500 text-white py-3 rounded-lg font-semibold hover:bg-mozo-600 transition-colors">
                                Llamar mozo
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Floating elements -->
                <div class="absolute -top-4 -right-4 w-16 h-16 bg-mozo-400/20 rounded-full animate-pulse"></div>
                <div class="absolute -bottom-4 -left-4 w-12 h-12 bg-mozo-300/20 rounded-full animate-pulse animation-delay-1000"></div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center space-y-4 mb-16">
            <h2 class="text-3xl lg:text-5xl font-bold text-gray-900">
                Todo lo que necesitas para
                <span class="bg-gradient-to-r from-mozo-600 to-mozo-500 bg-clip-text text-transparent">modernizar</span>
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Simplifica la gestión de tu restaurante con herramientas diseñadas para mejorar la experiencia de tus clientes
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="group hover-lift bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all border border-gray-100">
                <div class="w-12 h-12 bg-gradient-to-br from-mozo-500 to-mozo-600 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Códigos QR Inteligentes</h3>
                <p class="text-gray-600 leading-relaxed">
                    Cada mesa tiene su código QR único. Los clientes acceden al menú digital y pueden llamar al mozo instantáneamente.
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="group hover-lift bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all border border-gray-100">
                <div class="w-12 h-12 bg-gradient-to-br from-mozo-400 to-mozo-500 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Menús Digitales</h3>
                <p class="text-gray-600 leading-relaxed">
                    Sube tus menús en PDF y los clientes los verán en alta calidad en sus dispositivos. Actualiza precios en tiempo real.
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="group hover-lift bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all border border-gray-100">
                <div class="w-12 h-12 bg-gradient-to-br from-mozo-300 to-mozo-400 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM12 17h-7a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v5"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Notificaciones Tiempo Real</h3>
                <p class="text-gray-600 leading-relaxed">
                    Los mozos reciben alertas instantáneas en su móvil cuando un cliente necesita atención. Sin esperas.
                </p>
            </div>

            <!-- Feature 4 -->
            <div class="group hover-lift bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all border border-gray-100">
                <div class="w-12 h-12 bg-gradient-to-br from-mozo-600 to-mozo-700 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Gestión de Staff</h3>
                <p class="text-gray-600 leading-relaxed">
                    Administra tu equipo, asigna mesas a mozos específicos y mantén el control total de las operaciones.
                </p>
            </div>

            <!-- Feature 5 -->
            <div class="group hover-lift bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all border border-gray-100">
                <div class="w-12 h-12 bg-gradient-to-br from-mozo-200 to-mozo-300 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Dashboard Completo</h3>
                <p class="text-gray-600 leading-relaxed">
                    Estadísticas en tiempo real, historial de llamadas y análisis completo para optimizar tu servicio.
                </p>
            </div>

            <!-- Feature 6 -->
            <div class="group hover-lift bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all border border-gray-100">
                <div class="w-12 h-12 bg-gradient-to-br from-mozo-500 to-mozo-600 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">100% Seguro</h3>
                <p class="text-gray-600 leading-relaxed">
                    Tus datos están protegidos con encriptación de nivel bancario. Cumplimos con todas las normativas de seguridad.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-20 bg-gradient-to-r from-mozo-600 to-mozo-500 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-4 gap-8 text-center">
            <div class="space-y-2">
                <div class="text-4xl font-bold">500+</div>
                <div class="text-mozo-100">Restaurantes activos</div>
            </div>
            <div class="space-y-2">
                <div class="text-4xl font-bold">50k+</div>
                <div class="text-mozo-100">Mesas gestionadas</div>
            </div>
            <div class="space-y-2">
                <div class="text-4xl font-bold">99.9%</div>
                <div class="text-mozo-100">Tiempo de actividad</div>
            </div>
            <div class="space-y-2">
                <div class="text-4xl font-bold">4.9/5</div>
                <div class="text-mozo-100">Satisfacción cliente</div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="space-y-8">
            <h2 class="text-3xl lg:text-5xl font-bold text-gray-900">
                ¿Listo para
                <span class="bg-gradient-to-r from-mozo-600 to-mozo-500 bg-clip-text text-transparent">modernizar</span>
                tu restaurante?
            </h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Únete a cientos de restaurantes que ya digitalizaron sus operaciones con MOZO QR.
                Comienza gratis hoy mismo.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('public.plans.index') }}"
                   class="bg-mozo-500 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-mozo-600 transform hover:scale-105 transition-all duration-300 shadow-xl hover:shadow-2xl">
                    Ver planes y precios
                </a>
                <a href="#contact"
                   class="border-2 border-mozo-500 text-mozo-600 px-8 py-4 rounded-xl font-semibold text-lg hover:bg-mozo-50 transition-all duration-300">
                    Contactar ventas
                </a>
            </div>
        </div>
    </div>
</section>

<style>
@keyframes blob {
    0% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
    100% { transform: translate(0px, 0px) scale(1); }
}

.animate-blob {
    animation: blob 7s infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

.animation-delay-1000 {
    animation-delay: 1s;
}
</style>
@endsection
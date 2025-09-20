@extends('layouts.mozo-public')

@section('title', 'MOZO QR - Digitaliza tu restaurante')
@section('description', 'Transforma tu restaurante con códigos QR inteligentes. Gestión de mesas, menús digitales y notificaciones en tiempo real.')

@section('content')
<!-- Hero Section -->
<section class="relative min-h-screen flex flex-col justify-center overflow-hidden bg-gradient-hero hero-glow">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-1/4 left-10 w-72 h-72 bg-crypto-purple/10 rounded-full filter blur-3xl animate-pulse-slow"></div>
        <div class="absolute bottom-1/4 right-10 w-96 h-96 bg-crypto-light-purple/10 rounded-full filter blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
    </div>

    <div class="container mx-auto px-4 py-20 relative z-10">
        <div class="flex flex-col lg:flex-row items-center">
            <div class="lg:w-1/2 animate-fade-in-left">
                <div class="inline-flex items-center glass rounded-full px-4 py-1.5 mb-6">
                    <span class="text-xs font-medium text-crypto-purple mr-2">📱 Nueva App</span>
                    <span class="text-xs text-gray-300">Disponible en Play Store</span>
                    <svg class="h-4 w-4 text-gray-400 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">
                    <span class="text-gradient">Digitaliza tu Restaurante</span> con Códigos QR Inteligentes
                </h1>
                <p class="text-lg text-gray-300 mb-8 max-w-lg">
                    Transforma la experiencia de tus clientes con menús digitales, notificaciones instantáneas y gestión eficiente de mesas.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <button class="btn-primary text-white px-8 py-4 rounded-xl font-semibold text-lg inline-flex items-center justify-center">
                        Descargar App
                        <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </button>
                    <button class="border border-gray-700 text-white hover:bg-white/5 px-8 py-4 rounded-xl font-semibold text-lg inline-flex items-center justify-center transition-all">
                        Ver Demo
                        <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </button>
                </div>
                <div class="mt-8 flex items-center space-x-6">
                    <div>
                        <p class="text-2xl font-bold text-white">500+</p>
                        <p class="text-sm text-gray-400">Restaurantes</p>
                    </div>
                    <div class="h-12 w-px bg-gray-700"></div>
                    <div>
                        <p class="text-2xl font-bold text-white">50k+</p>
                        <p class="text-sm text-gray-400">Mesas Activas</p>
                    </div>
                    <div class="h-12 w-px bg-gray-700"></div>
                    <div>
                        <p class="text-2xl font-bold text-white">99.9%</p>
                        <p class="text-sm text-gray-400">Uptime</p>
                    </div>
                </div>
            </div>

            <div class="lg:w-1/2 mt-12 lg:mt-0 animate-fade-in-right">
                <div class="relative max-w-lg mx-auto">
                    <!-- Phone mockup with app interface -->
                    <div class="relative z-10 animate-float">
                        <div class="bg-gray-900 rounded-[3rem] p-3 shadow-2xl border border-gray-700">
                            <div class="bg-gray-800 rounded-[2.5rem] p-6">
                                <!-- Status bar -->
                                <div class="flex justify-between items-center mb-6">
                                    <div class="flex items-center space-x-1">
                                        <div class="w-1 h-1 bg-white rounded-full"></div>
                                        <div class="w-1 h-1 bg-white rounded-full"></div>
                                        <div class="w-1 h-1 bg-white rounded-full"></div>
                                    </div>
                                    <div class="text-white text-sm font-medium">9:41</div>
                                    <div class="flex items-center space-x-1">
                                        <div class="w-4 h-2 bg-white rounded-sm"></div>
                                        <div class="w-1 h-3 bg-white rounded-full"></div>
                                    </div>
                                </div>

                                <!-- App header -->
                                <div class="text-center mb-8">
                                    <div class="w-16 h-16 bg-gradient-to-br from-crypto-purple to-crypto-light-purple rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-white mb-1">Mesa 5</h3>
                                    <p class="text-gray-400 text-sm">Restaurante La Plaza</p>
                                </div>

                                <!-- QR Code section -->
                                <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 mb-6 border border-white/10">
                                    <div class="w-24 h-24 bg-white rounded-xl mx-auto mb-4 flex items-center justify-center">
                                        <div class="grid grid-cols-6 gap-0.5">
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                        </div>
                                    </div>
                                    <p class="text-center text-gray-400 text-xs">Escanea para ver el menú</p>
                                </div>

                                <!-- Action buttons -->
                                <div class="space-y-3">
                                    <button class="w-full bg-gradient-to-r from-crypto-purple to-crypto-light-purple text-white py-3 px-6 rounded-xl font-semibold transition-all hover:scale-105">
                                        🍽️ Ver Menú
                                    </button>
                                    <button class="w-full bg-yellow-500/20 text-yellow-400 py-3 px-6 rounded-xl font-semibold transition-all hover:bg-yellow-500/30 border border-yellow-500/30">
                                        🙋‍♂️ Llamar Mozo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating elements -->
                    <div class="absolute -top-6 -left-6 glass rounded-xl p-4 border border-crypto-purple/30 shadow-lg animate-float" style="animation-delay: 0.5s;">
                        <div class="flex items-center space-x-3">
                            <div class="h-10 w-10 bg-green-500/20 rounded-full flex items-center justify-center">
                                <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Orden recibida</p>
                                <p class="text-sm font-bold text-green-500">Mesa 3</p>
                            </div>
                        </div>
                    </div>

                    <div class="absolute -bottom-4 -right-8 glass rounded-xl p-4 border border-blue-500/30 shadow-lg animate-float" style="animation-delay: 1s;">
                        <div class="flex items-center space-x-3">
                            <div class="h-10 w-10 bg-blue-500/20 rounded-full flex items-center justify-center">
                                <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Respuesta</p>
                                <p class="text-sm font-bold text-blue-500">< 30s</p>
                            </div>
                        </div>
                    </div>

                    <div class="absolute top-1/2 -right-12 glass rounded-xl p-3 border border-purple-500/30 shadow-lg animate-float" style="animation-delay: 1.5s;">
                        <div class="text-center">
                            <p class="text-xs text-gray-400 mb-1">Satisfacción</p>
                            <div class="flex space-x-1">
                                <span class="text-yellow-400">⭐</span>
                                <span class="text-yellow-400">⭐</span>
                                <span class="text-yellow-400">⭐</span>
                                <span class="text-yellow-400">⭐</span>
                                <span class="text-yellow-400">⭐</span>
                            </div>
                            <p class="text-xs font-bold text-yellow-400">4.9/5</p>
                        </div>
                    </div>

                    <div class="absolute -left-6 -top-6 glass rounded-lg p-4 border border-crypto-purple/30 shadow-lg">
                        <div class="flex items-center space-x-3">
                            <div class="h-10 w-10 bg-crypto-purple/20 rounded-full flex items-center justify-center">
                                <svg class="h-6 w-6 text-crypto-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Seguridad</p>
                                <p class="text-lg font-bold text-white">Enterprise</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-20 bg-crypto-blue relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%239f54fd" fill-opacity="0.03"%3E%3Ccircle cx="7" cy="7" r="1"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center mb-16 animate-on-scroll">
            <h2 class="text-3xl lg:text-5xl font-bold text-white mb-4">
                Todo lo que necesitas para <span class="text-gradient">modernizar tu restaurante</span>
            </h2>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                Simplifica la gestión de tu restaurante con herramientas diseñadas para mejorar la experiencia de tus clientes
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="group glass rounded-2xl p-8 hover-lift transition-all animate-on-scroll relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-crypto-purple/10 to-crypto-light-purple/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-gradient-to-br from-crypto-purple to-crypto-dark-purple rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4 group-hover:text-crypto-purple transition-colors">Códigos QR Inteligentes</h3>
                    <p class="text-gray-300 leading-relaxed">
                        Cada mesa tiene su código QR único. Los clientes acceden al menú digital y pueden llamar al mozo instantáneamente.
                    </p>
                    <div class="mt-6 flex items-center text-crypto-purple opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span class="text-sm font-semibold mr-2">Más información</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="group glass rounded-2xl p-8 hover-lift transition-all animate-on-scroll relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-cyan-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4 group-hover:text-blue-400 transition-colors">Menús Digitales</h3>
                    <p class="text-gray-300 leading-relaxed">
                        Sube tus menús en PDF y los clientes los verán en alta calidad en sus dispositivos. Actualiza precios en tiempo real.
                    </p>
                    <div class="mt-6 flex items-center text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span class="text-sm font-semibold mr-2">Ver ejemplo</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="group glass rounded-2xl p-8 hover-lift transition-all animate-on-scroll relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-green-500/10 to-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM12 17h-7a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v5"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4 group-hover:text-green-400 transition-colors">Notificaciones Instantáneas</h3>
                    <p class="text-gray-300 leading-relaxed">
                        Los mozos reciben alertas instantáneas en su móvil cuando un cliente necesita atención. Sin esperas.
                    </p>
                    <div class="mt-6 flex items-center text-green-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span class="text-sm font-semibold mr-2">Tiempo promedio: &lt;30s</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Feature 4 -->
            <div class="group glass rounded-2xl p-8 hover-lift transition-all animate-on-scroll relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-orange-500/10 to-red-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4 group-hover:text-orange-400 transition-colors">Gestión de Staff</h3>
                    <p class="text-gray-300 leading-relaxed">
                        Administra tu equipo, asigna mesas a mozos específicos y mantén el control total de las operaciones.
                    </p>
                    <div class="mt-6 flex items-center text-orange-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span class="text-sm font-semibold mr-2">Panel admin</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Feature 5 -->
            <div class="group glass rounded-2xl p-8 hover-lift transition-all animate-on-scroll relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4 group-hover:text-indigo-400 transition-colors">Dashboard Completo</h3>
                    <p class="text-gray-300 leading-relaxed">
                        Estadísticas en tiempo real, historial de llamadas y análisis completo para optimizar tu servicio.
                    </p>
                    <div class="mt-6 flex items-center text-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span class="text-sm font-semibold mr-2">Analytics en vivo</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Feature 6 -->
            <div class="group glass rounded-2xl p-8 hover-lift transition-all animate-on-scroll relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-yellow-500/10 to-orange-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4 group-hover:text-yellow-400 transition-colors">100% Seguro</h3>
                    <p class="text-gray-300 leading-relaxed">
                        Tus datos están protegidos con encriptación de nivel bancario. Cumplimos con todas las normativas de seguridad.
                    </p>
                    <div class="mt-6 flex items-center text-yellow-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span class="text-sm font-semibold mr-2">SSL 256-bit</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-20 bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16 animate-on-scroll">
            <h2 class="text-3xl lg:text-5xl font-bold text-gray-900 mb-4">
                Cómo funciona <span class="text-gradient">MOZO QR</span>
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Implementa MOZO QR en tu restaurante en simples pasos
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center animate-on-scroll">
                <div class="w-16 h-16 bg-crypto-purple rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <span class="text-2xl font-bold text-white">1</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Descarga y Configura</h3>
                <p class="text-gray-600">
                    Descarga la app, crea tu cuenta y configura tu restaurante en minutos.
                </p>
            </div>

            <div class="text-center animate-on-scroll">
                <div class="w-16 h-16 bg-crypto-purple rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <span class="text-2xl font-bold text-white">2</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Genera Códigos QR</h3>
                <p class="text-gray-600">
                    Crea códigos QR únicos para cada mesa y personalízalos con tu marca.
                </p>
            </div>

            <div class="text-center animate-on-scroll">
                <div class="w-16 h-16 bg-crypto-purple rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <span class="text-2xl font-bold text-white">3</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">¡Listo para usar!</h3>
                <p class="text-gray-600">
                    Tus clientes escanean y pueden ver el menú y llamar al mozo al instante.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Download App Section -->
<section id="download" class="py-24 bg-gradient-to-br from-crypto-blue via-crypto-dark-blue to-gray-900 relative overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-1/4 left-10 w-96 h-96 bg-crypto-purple/15 rounded-full filter blur-3xl animate-pulse-slow"></div>
        <div class="absolute bottom-1/4 right-10 w-72 h-72 bg-crypto-light-purple/10 rounded-full filter blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-blue-500/5 rounded-full filter blur-3xl animate-pulse-slow" style="animation-delay: 2s;"></div>
    </div>

    <!-- Grid pattern overlay -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%239f54fd" fill-opacity="0.02"%3E%3Cpath d="M30 30h30v30H30zM0 0h30v30H0z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center mb-16 animate-on-scroll">
            <div class="inline-flex items-center glass rounded-full px-6 py-3 mb-6">
                <span class="text-sm font-medium text-crypto-purple mr-2">🚀 Lanzamiento</span>
                <span class="text-sm text-gray-300">Próximamente</span>
            </div>
            <h2 class="text-4xl lg:text-6xl font-bold text-white mb-6">
                Descarga la app <span class="text-gradient bg-gradient-to-r from-crypto-purple to-crypto-light-purple bg-clip-text text-transparent">MOZO QR</span>
            </h2>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto mb-8">
                Gestiona tu restaurante desde cualquier lugar con nuestra aplicación móvil completa. Próximamente disponible en Google Play Store.
            </p>
        </div>

        <div class="grid lg:grid-cols-2 gap-16 items-center max-w-7xl mx-auto">
            <!-- Left Content -->
            <div class="space-y-8 animate-on-scroll">
                <!-- Features Grid -->
                <div class="grid sm:grid-cols-2 gap-6">
                    <div class="glass rounded-xl p-6 border border-crypto-purple/20 hover:border-crypto-purple/40 transition-all group">
                        <div class="w-12 h-12 bg-gradient-to-br from-crypto-purple to-crypto-dark-purple rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="font-bold text-white mb-2">Panel Admin</h3>
                        <p class="text-gray-300 text-sm">Gestión completa de restaurante</p>
                    </div>

                    <div class="glass rounded-xl p-6 border border-blue-500/20 hover:border-blue-500/40 transition-all group">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM12 17h-7a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v5"></path>
                            </svg>
                        </div>
                        <h3 class="font-bold text-white mb-2">Push Real-time</h3>
                        <p class="text-gray-300 text-sm">Notificaciones instantáneas</p>
                    </div>

                    <div class="glass rounded-xl p-6 border border-green-500/20 hover:border-green-500/40 transition-all group">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <h3 class="font-bold text-white mb-2">Analytics</h3>
                        <p class="text-gray-300 text-sm">Reportes y estadísticas</p>
                    </div>

                    <div class="glass rounded-xl p-6 border border-yellow-500/20 hover:border-yellow-500/40 transition-all group">
                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="font-bold text-white mb-2">Seguridad</h3>
                        <p class="text-gray-300 text-sm">Encriptación bancaria</p>
                    </div>
                </div>

                <!-- Download Buttons -->
                <div class="space-y-4">
                    <div class="inline-flex items-center glass rounded-lg px-4 py-2 mb-4">
                        <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2 animate-pulse"></div>
                        <span class="text-sm text-gray-300">En desarrollo activo</span>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <button class="btn-primary text-white px-8 py-4 rounded-xl font-semibold text-lg inline-flex items-center justify-center bg-gradient-to-r from-crypto-purple to-crypto-light-purple hover:from-crypto-dark-purple hover:to-crypto-purple transition-all hover:scale-105 shadow-lg hover:shadow-crypto-purple/25">
                            <svg class="w-6 h-6 mr-3" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.9 17.39C17.64 16.59 16.89 16 16 16H8c-.89 0-1.64.59-1.9 1.39L5 19h14l-1.1-1.61zM8 14h8c2.21 0 4-1.79 4-4s-1.79-4-4-4H8c-2.21 0-4 1.79-4 4s1.79 4 4 4z"/>
                            </svg>
                            Pre-registro Play Store
                        </button>

                        <button class="border-2 border-crypto-purple text-crypto-purple bg-white/10 backdrop-blur-sm px-8 py-4 rounded-xl font-semibold text-lg hover:bg-crypto-purple hover:text-white transition-all inline-flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Ver Demo Web
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right - Phone Mockups -->
            <div class="relative animate-on-scroll">
                <div class="relative z-10 flex justify-center items-center">
                    <!-- Main Phone -->
                    <div class="relative animate-float">
                        <div class="bg-gray-900 rounded-[3rem] p-4 shadow-2xl border border-gray-700 transform rotate-3 hover:rotate-0 transition-transform duration-500">
                            <div class="bg-gradient-to-br from-crypto-blue to-crypto-dark-blue rounded-[2.5rem] p-6 h-[600px] w-[280px]">
                                <!-- Status bar -->
                                <div class="flex justify-between items-center mb-6 text-white text-sm">
                                    <div class="flex items-center space-x-1">
                                        <div class="w-1 h-1 bg-white rounded-full"></div>
                                        <div class="w-1 h-1 bg-white rounded-full"></div>
                                        <div class="w-1 h-1 bg-white rounded-full"></div>
                                    </div>
                                    <div class="font-semibold">9:41</div>
                                    <div class="flex items-center space-x-1">
                                        <div class="w-4 h-2 bg-white rounded-sm"></div>
                                        <div class="w-1 h-3 bg-white rounded-full"></div>
                                    </div>
                                </div>

                                <!-- App Content -->
                                <div class="space-y-6">
                                    <!-- Header -->
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-white font-bold text-lg">Mi Restaurante</h3>
                                            <p class="text-gray-300 text-sm">Mesa 5 - Llamando</p>
                                        </div>
                                        <div class="w-12 h-12 bg-crypto-purple rounded-xl flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM12 17h-7a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v5"></path>
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Stats Cards -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                                            <div class="text-2xl font-bold text-white">24</div>
                                            <div class="text-xs text-gray-300">Mesas activas</div>
                                        </div>
                                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                                            <div class="text-2xl font-bold text-green-400">3</div>
                                            <div class="text-xs text-gray-300">Llamadas hoy</div>
                                        </div>
                                    </div>

                                    <!-- Live Notifications -->
                                    <div class="space-y-3">
                                        <div class="bg-yellow-500/20 border border-yellow-500/30 rounded-xl p-4 flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                                <span class="text-black text-xs font-bold">5</span>
                                            </div>
                                            <div>
                                                <div class="text-white font-semibold text-sm">Mesa 5 necesita atención</div>
                                                <div class="text-yellow-300 text-xs">Hace 2 segundos</div>
                                            </div>
                                        </div>

                                        <div class="bg-green-500/20 border border-green-500/30 rounded-xl p-4 flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-white font-semibold text-sm">Mesa 3 atendida</div>
                                                <div class="text-green-300 text-xs">Hace 1 minuto</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Button -->
                                    <button class="w-full bg-gradient-to-r from-crypto-purple to-crypto-light-purple text-white py-4 rounded-xl font-semibold">
                                        Atender Mesa 5
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating stats -->
                    <div class="absolute -top-8 -left-8 glass rounded-xl p-4 border border-crypto-purple/30 shadow-lg animate-float" style="animation-delay: 0.5s;">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-crypto-purple">4.9</div>
                            <div class="text-xs text-gray-400">Rating</div>
                            <div class="flex space-x-1 mt-1">
                                <span class="text-yellow-400 text-xs">⭐</span>
                                <span class="text-yellow-400 text-xs">⭐</span>
                                <span class="text-yellow-400 text-xs">⭐</span>
                                <span class="text-yellow-400 text-xs">⭐</span>
                                <span class="text-yellow-400 text-xs">⭐</span>
                            </div>
                        </div>
                    </div>

                    <div class="absolute -bottom-8 -right-8 glass rounded-xl p-4 border border-green-500/30 shadow-lg animate-float" style="animation-delay: 1s;">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-green-500/20 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-green-500">< 30s</div>
                                <div class="text-xs text-gray-400">Respuesta</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-24 bg-gradient-to-br from-gray-50 via-gray-100 to-gray-200 relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cdefs%3E%3Cpattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"%3E%3Cpath d="M 10 0 L 0 0 0 10" fill="none" stroke="%23e5e7eb" stroke-width="0.5"/%3E%3C/pattern%3E%3C/defs%3E%3Crect width="100" height="100" fill="url(%23grid)"/%3E%3C/svg%3E')] opacity-40"></div>

    <!-- Animated elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-1/4 right-20 w-64 h-64 bg-crypto-purple/5 rounded-full filter blur-3xl animate-pulse-slow"></div>
        <div class="absolute bottom-1/4 left-20 w-80 h-80 bg-blue-500/5 rounded-full filter blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center mb-20 animate-on-scroll">
            <div class="inline-flex items-center glass rounded-full px-6 py-3 mb-6 border border-crypto-purple/20">
                <span class="text-sm font-medium text-crypto-purple mr-2">💬 Contáctanos</span>
                <span class="text-sm text-gray-600">Respuesta en 24h</span>
            </div>
            <h2 class="text-4xl lg:text-6xl font-bold text-gray-900 mb-6">
                ¿Necesitas <span class="text-gradient bg-gradient-to-r from-crypto-purple to-crypto-light-purple bg-clip-text text-transparent">ayuda</span>?
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Estamos aquí para ayudarte. Contáctanos para consultas generales o solicita soporte técnico especializado
            </p>
        </div>

        <div class="grid lg:grid-cols-2 gap-12 max-w-7xl mx-auto">
            <!-- Contact Form -->
            <div class="group relative animate-on-scroll">
                <div class="absolute inset-0 bg-gradient-to-br from-crypto-purple/10 to-crypto-light-purple/5 rounded-3xl transform rotate-1 group-hover:rotate-0 transition-transform duration-300"></div>
                <div class="relative bg-white/80 backdrop-blur-lg rounded-3xl p-8 shadow-2xl border border-white/50 hover:shadow-crypto-purple/20 transition-all duration-300">
                    <div class="flex items-center mb-8">
                        <div class="w-12 h-12 bg-gradient-to-br from-crypto-purple to-crypto-dark-purple rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">Contacto General</h3>
                            <p class="text-gray-600">Consultas, demos y información</p>
                        </div>
                    </div>

                    <form action="#" method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="relative">
                                <label for="nombre" class="block text-sm font-semibold text-gray-700 mb-2">Nombre</label>
                                <input type="text" id="nombre" name="nombre" required
                                    class="w-full px-4 py-4 bg-white/70 border border-gray-200 rounded-xl focus:ring-2 focus:ring-crypto-purple focus:border-crypto-purple focus:bg-white transition-all duration-300 hover:shadow-md">
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-crypto-purple/5 to-transparent opacity-0 hover:opacity-100 transition-opacity pointer-events-none"></div>
                            </div>
                            <div class="relative">
                                <label for="apellido" class="block text-sm font-semibold text-gray-700 mb-2">Apellido</label>
                                <input type="text" id="apellido" name="apellido" required
                                    class="w-full px-4 py-4 bg-white/70 border border-gray-200 rounded-xl focus:ring-2 focus:ring-crypto-purple focus:border-crypto-purple focus:bg-white transition-all duration-300 hover:shadow-md">
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-crypto-purple/5 to-transparent opacity-0 hover:opacity-100 transition-opacity pointer-events-none"></div>
                            </div>
                        </div>

                        <div class="relative">
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                            <input type="email" id="email" name="email" required
                                class="w-full px-4 py-4 bg-white/70 border border-gray-200 rounded-xl focus:ring-2 focus:ring-crypto-purple focus:border-crypto-purple focus:bg-white transition-all duration-300 hover:shadow-md">
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-crypto-purple/5 to-transparent opacity-0 hover:opacity-100 transition-opacity pointer-events-none"></div>
                        </div>

                        <div class="relative">
                            <label for="telefono" class="block text-sm font-semibold text-gray-700 mb-2">Teléfono (opcional)</label>
                            <input type="tel" id="telefono" name="telefono"
                                class="w-full px-4 py-4 bg-white/70 border border-gray-200 rounded-xl focus:ring-2 focus:ring-crypto-purple focus:border-crypto-purple focus:bg-white transition-all duration-300 hover:shadow-md">
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-crypto-purple/5 to-transparent opacity-0 hover:opacity-100 transition-opacity pointer-events-none"></div>
                        </div>

                        <div class="relative">
                            <label for="mensaje" class="block text-sm font-semibold text-gray-700 mb-2">Mensaje</label>
                            <textarea id="mensaje" name="mensaje" rows="4" required
                                class="w-full px-4 py-4 bg-white/70 border border-gray-200 rounded-xl focus:ring-2 focus:ring-crypto-purple focus:border-crypto-purple focus:bg-white transition-all duration-300 hover:shadow-md resize-none"
                                placeholder="Cuéntanos sobre tu restaurante y cómo podemos ayudarte..."></textarea>
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-crypto-purple/5 to-transparent opacity-0 hover:opacity-100 transition-opacity pointer-events-none"></div>
                        </div>

                        <button type="submit" class="w-full bg-gradient-to-r from-crypto-purple to-crypto-light-purple hover:from-crypto-dark-purple hover:to-crypto-purple text-white py-4 px-8 rounded-xl font-semibold text-lg shadow-lg hover:shadow-crypto-purple/25 transition-all duration-300 transform hover:scale-105 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Enviar mensaje
                        </button>
                    </form>
                </div>
            </div>

            <!-- Support Form -->
            <div class="group relative animate-on-scroll">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-indigo-500/5 rounded-3xl transform -rotate-1 group-hover:rotate-0 transition-transform duration-300"></div>
                <div class="relative bg-white/80 backdrop-blur-lg rounded-3xl p-8 shadow-2xl border border-white/50 hover:shadow-blue-500/20 transition-all duration-300">
                    <div class="flex items-center mb-8">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">Soporte Técnico</h3>
                            <p class="text-gray-600">Asistencia especializada 24/7</p>
                        </div>
                    </div>

                    <form action="#" method="POST" class="space-y-6">
                        <div class="relative">
                            <label for="empresa" class="block text-sm font-semibold text-gray-700 mb-2">Nombre de la empresa</label>
                            <input type="text" id="empresa" name="empresa" required
                                class="w-full px-4 py-4 bg-white/70 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all duration-300 hover:shadow-md">
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-transparent opacity-0 hover:opacity-100 transition-opacity pointer-events-none"></div>
                        </div>

                        <div class="relative">
                            <label for="contacto_email" class="block text-sm font-semibold text-gray-700 mb-2">Email de contacto</label>
                            <input type="email" id="contacto_email" name="contacto_email" required
                                class="w-full px-4 py-4 bg-white/70 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all duration-300 hover:shadow-md">
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-transparent opacity-0 hover:opacity-100 transition-opacity pointer-events-none"></div>
                        </div>

                        <div class="relative">
                            <label for="tipo_problema" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de problema</label>
                            <select id="tipo_problema" name="tipo_problema" required
                                class="w-full px-4 py-4 bg-white/70 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all duration-300 hover:shadow-md">
                                <option value="">Selecciona una opción</option>
                                <option value="tecnico">🔧 Problema técnico</option>
                                <option value="configuracion">⚙️ Ayuda con configuración</option>
                                <option value="facturacion">💳 Consulta de facturación</option>
                                <option value="integracion">🔗 Problemas de integración</option>
                                <option value="rendimiento">📊 Problemas de rendimiento</option>
                                <option value="otro">❓ Otro</option>
                            </select>
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-transparent opacity-0 hover:opacity-100 transition-opacity pointer-events-none"></div>
                        </div>

                        <div class="relative">
                            <label for="descripcion" class="block text-sm font-semibold text-gray-700 mb-2">Descripción del problema</label>
                            <textarea id="descripcion" name="descripcion" rows="4" required
                                class="w-full px-4 py-4 bg-white/70 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all duration-300 hover:shadow-md resize-none"
                                placeholder="Describe el problema en detalle, incluye pasos para reproducirlo si es posible..."></textarea>
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/5 to-transparent opacity-0 hover:opacity-100 transition-opacity pointer-events-none"></div>
                        </div>

                        <div class="flex items-center space-x-3 p-4 bg-blue-50 rounded-xl border border-blue-200">
                            <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-blue-700">Tiempo de respuesta</p>
                                <p class="text-xs text-blue-600">Problemas críticos: < 2 horas | Otros: < 24 horas</p>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white py-4 px-8 rounded-xl font-semibold text-lg shadow-lg hover:shadow-blue-500/25 transition-all duration-300 transform hover:scale-105 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            Solicitar soporte
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Contact Info Cards -->
        <div class="grid md:grid-cols-3 gap-8 mt-20 max-w-4xl mx-auto">
            <div class="text-center glass rounded-2xl p-6 border border-crypto-purple/20 hover:border-crypto-purple/40 transition-all animate-on-scroll">
                <div class="w-14 h-14 bg-gradient-to-br from-crypto-purple to-crypto-dark-purple rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h4 class="font-bold text-gray-900 mb-2">Email</h4>
                <p class="text-gray-600 text-sm">contacto@mozoqr.com</p>
                <p class="text-gray-600 text-sm">soporte@mozoqr.com</p>
            </div>

            <div class="text-center glass rounded-2xl p-6 border border-green-500/20 hover:border-green-500/40 transition-all animate-on-scroll">
                <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h4 class="font-bold text-gray-900 mb-2">Horarios</h4>
                <p class="text-gray-600 text-sm">Lun - Vie: 9:00 - 18:00</p>
                <p class="text-gray-600 text-sm">Soporte 24/7</p>
            </div>

            <div class="text-center glass rounded-2xl p-6 border border-blue-500/20 hover:border-blue-500/40 transition-all animate-on-scroll">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <h4 class="font-bold text-gray-900 mb-2">Ubicación</h4>
                <p class="text-gray-600 text-sm">Buenos Aires, Argentina</p>
                <p class="text-gray-600 text-sm">Cobertura nacional</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-crypto-blue relative overflow-hidden">
    <div class="absolute inset-0">
        <div class="absolute top-1/4 left-10 w-72 h-72 bg-crypto-purple/10 rounded-full filter blur-3xl animate-pulse-slow"></div>
        <div class="absolute bottom-1/4 right-10 w-96 h-96 bg-crypto-light-purple/10 rounded-full filter blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
    </div>

    <div class="container mx-auto px-4 text-center relative z-10">
        <div class="max-w-4xl mx-auto animate-on-scroll">
            <h2 class="text-3xl lg:text-5xl font-bold text-white mb-6">
                ¿Listo para <span class="text-gradient">modernizar</span> tu restaurante?
            </h2>
            <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
                Únete a cientos de restaurantes que ya digitalizaron sus operaciones con MOZO QR.
                Descarga la app y comienza hoy mismo.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button class="btn-primary text-white px-8 py-4 rounded-xl font-semibold text-lg inline-flex items-center justify-center">
                    Descargar App
                    <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </button>
                <button class="border-2 border-crypto-purple text-crypto-purple bg-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-crypto-purple hover:text-white transition-all inline-flex items-center justify-center">
                    Contactar
                    <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Scroll to Top Button -->
<button id="scrollToTop" class="fixed bottom-8 right-8 bg-crypto-purple hover:bg-crypto-dark-purple text-white p-3 rounded-full shadow-lg opacity-0 invisible transition-all duration-300 z-50">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
    </svg>
</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });

    // Scroll to top button
    const scrollBtn = document.getElementById('scrollToTop');

    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            scrollBtn.classList.remove('opacity-0', 'invisible');
            scrollBtn.classList.add('opacity-100', 'visible');
        } else {
            scrollBtn.classList.add('opacity-0', 'invisible');
            scrollBtn.classList.remove('opacity-100', 'visible');
        }
    });

    scrollBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>
@endsection
@extends('layouts.mozo-public')

@section('title', 'MOZO QR - Digitaliza tu restaurante')
@section('description', 'Transforma tu restaurante con códigos QR inteligentes. Gestión de mesas, menús digitales y notificaciones en tiempo real.')

@section('content')
<!-- Hero Section -->
<section class="relative min-h-screen flex flex-col justify-center overflow-hidden bg-gradient-to-br from-gray-50 to-white">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-1/4 left-10 w-72 h-72 bg-crypto-purple/10 rounded-full filter blur-3xl animate-pulse-slow"></div>
        <div class="absolute bottom-1/4 right-10 w-96 h-96 bg-crypto-light-purple/10 rounded-full filter blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
    </div>

    <div class="container mx-auto px-4 py-20 relative z-10">
        <div class="flex flex-col lg:flex-row items-center">
            <div class="lg:w-1/2 animate-fade-in-left">
                <div class="inline-flex items-center bg-white/80 backdrop-blur-sm border border-gray-200 rounded-full px-4 py-1.5 mb-6">
                    <span class="text-xs font-medium text-crypto-purple mr-2">📱 Nueva App</span>
                    <span class="text-xs text-gray-600">Disponible en Play Store</span>
                    <svg class="h-4 w-4 text-gray-400 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">
                    <span class="text-gray-900">Digitaliza tu Restaurante</span><br class="hidden sm:block">
                    <span class="text-gray-900">con </span><span class="text-crypto-purple font-bold">Códigos QR Inteligentes</span>
                </h1>
                <p class="text-lg text-gray-600 mb-6 max-w-lg">
                    Transforma la experiencia de tus clientes con menús digitales, notificaciones instantáneas y gestión eficiente de mesas.
                </p>

                <!-- Beneficios clave -->
                <div class="mb-8 space-y-3">
                    <div class="flex items-center text-sm text-gray-700">
                        <div class="custom-checkbox mr-3">
                            <div class="w-5 h-5 bg-green-500 border-2 border-green-500 rounded flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <span class="font-medium">Configuración en menos de 5 minutos</span>
                    </div>
                    <div class="flex items-center text-sm text-gray-700">
                        <div class="custom-checkbox mr-3">
                            <div class="w-5 h-5 bg-green-500 border-2 border-green-500 rounded flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <span class="font-medium">Notificaciones en tiempo real</span>
                    </div>
                    <div class="flex items-center text-sm text-gray-700">
                        <div class="custom-checkbox mr-3">
                            <div class="w-5 h-5 bg-green-500 border-2 border-green-500 rounded flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <span class="font-medium">Sin necesidad de instalar apps para clientes</span>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="grid grid-cols-3 gap-4 mb-8 p-4 bg-gray-50 rounded-xl border">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-crypto-purple">98%</div>
                        <div class="text-xs text-gray-600">Satisfacción</div>
                    </div>
                    <div class="text-center border-l border-gray-200">
                        <div class="text-2xl font-bold text-crypto-purple">< 30s</div>
                        <div class="text-xs text-gray-600">Tiempo respuesta</div>
                    </div>
                    <div class="text-center border-l border-gray-200">
                        <div class="text-2xl font-bold text-crypto-purple">24/7</div>
                        <div class="text-xs text-gray-600">Disponibilidad</div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="#download" class="btn-primary text-white px-8 py-4 rounded-xl font-semibold text-lg inline-flex items-center justify-center group">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        Probar Gratis
                        <svg class="ml-2 h-4 w-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    <a href="#plans" class="border border-gray-300 text-gray-700 hover:bg-gray-50 px-8 py-4 rounded-xl font-semibold text-lg inline-flex items-center justify-center transition-all">
                        Ver Planes
                        <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </a>
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

                    <!-- Floating elements with proper z-index -->
                    <div class="absolute -top-6 -left-6 z-20 glass rounded-xl p-4 border border-crypto-purple/30 shadow-lg animate-float" style="animation-delay: 0.5s;">
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

                    <div class="absolute -bottom-4 -right-8 z-20 glass rounded-xl p-4 border border-blue-500/30 shadow-lg animate-float" style="animation-delay: 1s;">
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

<!-- Trust & Social Proof Section -->
<section class="py-16 bg-white border-b border-gray-100">
    <div class="container mx-auto px-4">
        <!-- Trusted by section -->
        <div class="text-center mb-12">
            <p class="text-sm font-medium text-gray-500 mb-6">Más de 500+ restaurantes ya confían en MOZO QR</p>
            <div class="flex justify-center items-center space-x-8 opacity-60">
                <div class="text-xl font-bold text-gray-400">🍕 Pizza Bella</div>
                <div class="text-xl font-bold text-gray-400">🥘 El Asador</div>
                <div class="text-xl font-bold text-gray-400">☕ Café Central</div>
            </div>
        </div>

        <!-- Key benefits cards -->
        <div class="grid md:grid-cols-3 gap-6 mb-12">
            <div class="text-center p-6 bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl border border-green-100">
                <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">Aumenta tus ventas</h3>
                <p class="text-sm text-gray-600">Promedio 23% más ventas con atención más rápida</p>
            </div>

            <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl border border-blue-100">
                <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">Servicio más rápido</h3>
                <p class="text-sm text-gray-600">Respuesta promedio en menos de 30 segundos</p>
            </div>

            <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl border border-purple-100">
                <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">Clientes felices</h3>
                <p class="text-sm text-gray-600">98% de satisfacción en encuestas de clientes</p>
            </div>
        </div>

        <!-- Testimonial -->
        <div class="bg-gray-50 rounded-2xl p-8 text-center max-w-2xl mx-auto">
            <div class="flex justify-center mb-4">
                <div class="flex space-x-1">
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                </div>
            </div>
            <blockquote class="text-gray-700 text-lg italic mb-4">
                "Desde que implementamos MOZO QR, nuestros clientes están más satisfechos y podemos atender más mesas con el mismo personal. ¡Es increíble!"
            </blockquote>
            <div class="flex items-center justify-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-red-400 rounded-full flex items-center justify-center">
                    <span class="text-white font-bold text-sm">CM</span>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">Carlos Mendez</p>
                    <p class="text-sm text-gray-600">Propietario, Restaurante La Plaza</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-20 bg-gray-50 relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%236366f1" fill-opacity="0.03"%3E%3Ccircle cx="7" cy="7" r="1"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center mb-16 animate-on-scroll">
            <h2 class="text-3xl lg:text-5xl font-bold text-gray-900 mb-4">
                Todo lo que necesitas para <span class="text-gradient">modernizar tu restaurante</span>
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Simplifica la gestión de tu restaurante con herramientas diseñadas para mejorar la experiencia de tus clientes
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="group bg-white rounded-2xl p-8 hover-lift transition-all animate-on-scroll relative overflow-hidden shadow-lg border border-gray-100">
                <div class="absolute inset-0 bg-gradient-to-br from-crypto-purple/10 to-crypto-light-purple/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-gradient-to-br from-crypto-purple to-crypto-dark-purple rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4 group-hover:text-crypto-purple transition-colors">Códigos QR Inteligentes</h3>
                    <p class="text-gray-600 leading-relaxed">
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
            <div class="group bg-white rounded-2xl p-8 hover-lift transition-all animate-on-scroll relative overflow-hidden shadow-lg border border-gray-100">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-cyan-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4 group-hover:text-blue-400 transition-colors">Menús Digitales</h3>
                    <p class="text-gray-600 leading-relaxed">
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
            <div class="group bg-white rounded-2xl p-8 hover-lift transition-all animate-on-scroll relative overflow-hidden shadow-lg border border-gray-100">
                <div class="absolute inset-0 bg-gradient-to-br from-green-500/10 to-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM12 17h-7a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v5"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4 group-hover:text-green-400 transition-colors">Notificaciones Instantáneas</h3>
                    <p class="text-gray-600 leading-relaxed">
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
            <div class="group bg-white rounded-2xl p-8 hover-lift transition-all animate-on-scroll relative overflow-hidden shadow-lg border border-gray-100">
                <div class="absolute inset-0 bg-gradient-to-br from-orange-500/10 to-red-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4 group-hover:text-orange-400 transition-colors">Gestión de Staff</h3>
                    <p class="text-gray-600 leading-relaxed">
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
            <div class="group bg-white rounded-2xl p-8 hover-lift transition-all animate-on-scroll relative overflow-hidden shadow-lg border border-gray-100">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4 group-hover:text-indigo-400 transition-colors">Dashboard Completo</h3>
                    <p class="text-gray-600 leading-relaxed">
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
            <div class="group bg-white rounded-2xl p-8 hover-lift transition-all animate-on-scroll relative overflow-hidden shadow-lg border border-gray-100">
                <div class="absolute inset-0 bg-gradient-to-br from-yellow-500/10 to-orange-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4 group-hover:text-yellow-400 transition-colors">100% Seguro</h3>
                    <p class="text-gray-600 leading-relaxed">
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

<!-- Plans Section -->
<section id="plans" class="py-20 bg-gradient-to-br from-gray-50 via-gray-100 to-gray-200 relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cdefs%3E%3Cpattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"%3E%3Cpath d="M 10 0 L 0 0 0 10" fill="none" stroke="%23e5e7eb" stroke-width="0.5"/%3E%3C/pattern%3E%3C/defs%3E%3Crect width="100" height="100" fill="url(%23grid)"/%3E%3C/svg%3E')] opacity-40"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center mb-16 animate-on-scroll">
            <div class="inline-flex items-center glass rounded-full px-6 py-3 mb-6 border border-crypto-purple/20">
                <span class="text-sm font-medium text-crypto-purple mr-2">💰 Planes</span>
                <span class="text-sm text-gray-600">Soluciones para cada negocio</span>
            </div>
            <h2 class="text-4xl lg:text-6xl font-bold text-gray-900 mb-6">
                <span class="text-gradient bg-gradient-to-r from-crypto-purple to-crypto-light-purple bg-clip-text text-transparent">Elige el Plan Perfecto</span><br class="hidden sm:block">
                <span class="text-gray-900">para tu Restaurante</span>
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Desde pequeños cafés hasta grandes cadenas. Encuentra la solución que se adapte a las necesidades específicas de tu negocio.
            </p>
        </div>

        <!-- Plans Grid with max-width container -->
        <div class="max-w-6xl mx-auto mb-20">
            @if($plans->count() > 0)
            <div class="grid md:grid-cols-1 lg:grid-cols-{{ min($plans->count(), 3) }} gap-8 justify-items-center">
                @foreach($plans as $plan)
                <div class="group relative animate-on-scroll w-full max-w-sm">
                    <!-- Popular badge -->
                    @if($plan->is_popular)
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 z-20">
                        <span class="bg-gradient-to-r from-crypto-purple to-crypto-light-purple text-white px-6 py-2 rounded-full text-sm font-bold shadow-lg">
                            ⭐ Más Popular
                        </span>
                    </div>
                    @endif

                    @if($plan->is_featured)
                    <div class="absolute -top-4 right-4 z-20">
                        <span class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-4 py-2 rounded-full text-sm font-bold shadow-lg">
                            ✨ Recomendado
                        </span>
                    </div>
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-br {{ $plan->is_popular ? 'from-crypto-purple/10 to-crypto-light-purple/5' : 'from-gray-500/5 to-gray-600/5' }} rounded-3xl transform rotate-1 group-hover:rotate-0 transition-transform duration-300"></div>

                    <div class="relative bg-white/80 backdrop-blur-lg rounded-3xl p-8 shadow-2xl border border-white/50 {{ $plan->is_popular ? 'hover:shadow-crypto-purple/20' : 'hover:shadow-gray-500/20' }} transition-all duration-300">
                        <!-- Plan Header -->
                        <div class="text-center mb-8">
                            <div class="w-16 h-16 {{ $plan->is_popular ? 'bg-gradient-to-br from-crypto-purple to-crypto-dark-purple' : 'bg-gradient-to-br from-gray-700 to-gray-900' }} rounded-2xl flex items-center justify-center mx-auto mb-6">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <h3 class="text-3xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
                            <p class="text-gray-600 mb-6">{{ $plan->description }}</p>

                            <div class="mb-6">
                                @if($plan->yearly_discount_percentage > 0)
                                    <!-- Precio con descuento anual -->
                                    <div class="text-center">
                                        <div class="text-lg text-gray-500 line-through">
                                            {{ $plan->getFormattedPrice() }}/mes
                                        </div>
                                        <div>
                                            <span class="text-5xl font-bold {{ $plan->is_popular ? 'text-crypto-purple' : 'text-gray-900' }}">${{ number_format($plan->getPriceWithDiscount('yearly'), 0) }}</span>
                                            <span class="text-gray-600 text-xl">/mes</span>
                                        </div>
                                        <div class="text-sm text-green-600 font-semibold mt-1">
                                            {{ $plan->yearly_discount_percentage }}% OFF pagando anual
                                        </div>
                                    </div>
                                @else
                                    <!-- Precio normal -->
                                    <span class="text-5xl font-bold {{ $plan->is_popular ? 'text-crypto-purple' : 'text-gray-900' }}">{{ $plan->getFormattedPrice() }}</span>
                                    <span class="text-gray-600 text-xl">/mes</span>
                                @endif
                            </div>

                            @if($plan->hasTrialEnabled())
                            <div class="inline-flex items-center bg-green-50 text-green-700 px-4 py-2 rounded-xl mb-6 border border-green-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                                </svg>
                                {{ $plan->getTrialDays() }} días gratis
                            </div>
                            @endif
                        </div>

                        <!-- Features List -->
                        <div class="mb-8">
                            <h4 class="font-bold text-gray-900 mb-6 text-lg">✨ Incluye:</h4>
                            <ul class="space-y-4">
                                @if($plan->features && is_array($plan->features))
                                    @foreach($plan->features as $feature)
                                    <li class="flex items-center">
                                        <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center mr-3">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        <span class="text-gray-700">{{ $feature }}</span>
                                    </li>
                                    @endforeach
                                @else
                                    <!-- Características por defecto si no hay configuradas -->
                                    <li class="flex items-center">
                                        <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center mr-3">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        <span class="text-gray-700">Plan completo disponible</span>
                                    </li>
                                @endif
                            </ul>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-4">
                            <a href="{{ route('public.checkout.plan', $plan) }}" class="w-full {{ $plan->is_popular ? 'bg-gradient-to-r from-crypto-purple to-crypto-light-purple hover:from-crypto-dark-purple hover:to-crypto-purple' : 'bg-gradient-to-r from-gray-700 to-gray-900 hover:from-gray-800 hover:to-gray-900' }} text-white font-bold py-4 px-8 rounded-xl transition-all duration-300 text-center block transform hover:scale-105 shadow-lg">
                                Empezar con {{ $plan->name }}
                            </a>

                            <a href="#contact" class="w-full border-2 {{ $plan->is_popular ? 'border-crypto-purple text-crypto-purple hover:bg-crypto-purple' : 'border-gray-300 text-gray-700 hover:bg-gray-100' }} hover:text-white font-semibold py-3 px-8 rounded-xl transition-all duration-300 text-center block">
                                Más Información
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <!-- No plans message -->
            <div class="text-center py-12">
                <div class="w-16 h-16 bg-gray-200 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-600 mb-4">Próximamente</h3>
                <p class="text-gray-500 mb-8">Estamos preparando nuestros planes para ofrecerte las mejores opciones.</p>
                <a href="#contact" class="inline-flex items-center bg-crypto-purple text-white px-6 py-3 rounded-xl font-semibold hover:bg-crypto-dark-purple transition-colors">
                    Contáctanos para más información
                </a>
            </div>
            @endif
        </div>
    </div>
</section>

<!-- Download App Section with QR Codes -->
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
                <span class="text-sm font-medium text-crypto-purple mr-2">📱 Descarga ahora</span>
                <span class="text-sm text-gray-300">Disponible en Play Store</span>
            </div>
            <h2 class="text-4xl lg:text-6xl font-bold text-white mb-6">
                Descarga la app <span class="text-gradient bg-gradient-to-r from-crypto-purple to-crypto-light-purple bg-clip-text text-transparent">ahora!</span>
            </h2>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto mb-8">
                Chatea y acepta la mejor propuesta, son elegidos!
                Hace tu trato rápido y confiará, rápido, ya no hogar.
            </p>
        </div>

        <div class="flex flex-col lg:flex-row items-center gap-16 max-w-7xl mx-auto">
            <!-- Left Content -->
            <div class="lg:w-1/2 space-y-8 animate-on-scroll">
                <h3 class="text-2xl font-bold text-white mb-4">
                    Descarga la App <span class="text-gradient">ahora!</span>
                </h3>
                <p class="text-gray-300 mb-8">
                    Chatea y acepta la mejor propuesta, son elegidos!<br>
                    Hace tu trato rápido y confiará, rápido, ya no hogar.
                </p>

                <!-- Download buttons with app store badges -->
                <div class="flex flex-col sm:flex-row gap-4 mb-8">
                    <a href="{{ $androidAppUrl }}" class="inline-flex items-center bg-black text-white px-6 py-3 rounded-xl hover:bg-gray-800 transition-all">
                        <svg class="w-8 h-8 mr-3" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.9 17.39C17.64 16.59 16.89 16 16 16H8c-.89 0-1.64.59-1.9 1.39L5 19h14l-1.1-1.61zM8 14h8c2.21 0 4-1.79 4-4s-1.79-4-4-4H8c-2.21 0-4 1.79-4 4s1.79 4 4 4z"/>
                        </svg>
                        <div class="text-left">
                            <div class="text-xs">Download on the</div>
                            <div class="text-lg font-semibold">Google Play</div>
                        </div>
                    </a>

                    <a href="{{ $iosAppUrl }}" class="inline-flex items-center bg-black text-white px-6 py-3 rounded-xl hover:bg-gray-800 transition-all">
                        <svg class="w-8 h-8 mr-3" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                        </svg>
                        <div class="text-left">
                            <div class="text-xs">Download on the</div>
                            <div class="text-lg font-semibold">App Store</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Right Content - QR Codes Section -->
            <div class="lg:w-1/2 relative animate-on-scroll">
                <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8 border border-white/20 relative overflow-hidden">
                    <!-- Background decoration -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-crypto-purple/20 rounded-full -translate-y-16 translate-x-16"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-crypto-light-purple/20 rounded-full translate-y-12 -translate-x-12"></div>

                    <div class="relative z-10">
                        <div class="text-center mb-8">
                            <h3 class="text-2xl font-bold text-white mb-2">Encuentra nuestra app en Android e iOS</h3>
                            <p class="text-gray-300">Escanea el código QR con tu dispositivo</p>
                        </div>

                        <div class="grid grid-cols-2 gap-8">
                            <!-- Android QR -->
                            <div class="text-center">
                                <div class="bg-white rounded-2xl p-4 mb-4 mx-auto w-32 h-32 flex items-center justify-center">
                                    <!-- QR Code placeholder for Android -->
                                    <div class="w-24 h-24 bg-gray-900 rounded-lg flex items-center justify-center">
                                        <div class="grid grid-cols-8 gap-0.5">
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <!-- Repeat pattern for QR effect -->
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
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
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
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
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-center mb-2">
                                    <svg class="w-6 h-6 mr-2 text-green-500" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.9 17.39C17.64 16.59 16.89 16 16 16H8c-.89 0-1.64.59-1.9 1.39L5 19h14l-1.1-1.61zM8 14h8c2.21 0 4-1.79 4-4s-1.79-4-4-4H8c-2.21 0-4 1.79-4 4s1.79 4 4 4z"/>
                                    </svg>
                                    <span class="text-white font-semibold">Android</span>
                                </div>
                                <p class="text-gray-300 text-sm">Google Play Store</p>
                            </div>

                            <!-- iOS QR -->
                            <div class="text-center">
                                <div class="bg-white rounded-2xl p-4 mb-4 mx-auto w-32 h-32 flex items-center justify-center">
                                    <!-- QR Code placeholder for iOS -->
                                    <div class="w-24 h-24 bg-gray-900 rounded-lg flex items-center justify-center">
                                        <div class="grid grid-cols-8 gap-0.5">
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <!-- Repeat pattern for QR effect -->
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
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
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
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                            <div class="w-1 h-1 bg-white"></div>
                                            <div class="w-1 h-1 bg-black"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-center mb-2">
                                    <svg class="w-6 h-6 mr-2 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                                    </svg>
                                    <span class="text-white font-semibold">iOS</span>
                                </div>
                                <p class="text-gray-300 text-sm">App Store</p>
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

        <!-- Single Contact Form -->
        <div class="max-w-2xl mx-auto">
            <div class="group relative animate-on-scroll">
                <div class="absolute inset-0 bg-gradient-to-br from-crypto-purple/10 to-crypto-light-purple/5 rounded-3xl transform rotate-1 group-hover:rotate-0 transition-transform duration-300"></div>
                <div class="relative bg-white/80 backdrop-blur-lg rounded-3xl p-10 shadow-2xl border border-white/50 hover:shadow-crypto-purple/20 transition-all duration-300">
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 bg-gradient-to-br from-crypto-purple to-crypto-dark-purple rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-2">¡Hablemos!</h3>
                        <p class="text-gray-600">Cuéntanos sobre tu restaurante y cómo podemos ayudarte a digitalizarlo</p>
                    </div>

                    <form action="#" method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
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
                            <label for="empresa" class="block text-sm font-semibold text-gray-700 mb-2">Nombre del restaurante</label>
                            <input type="text" id="empresa" name="empresa" required
                                class="w-full px-4 py-4 bg-white/70 border border-gray-200 rounded-xl focus:ring-2 focus:ring-crypto-purple focus:border-crypto-purple focus:bg-white transition-all duration-300 hover:shadow-md">
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-crypto-purple/5 to-transparent opacity-0 hover:opacity-100 transition-opacity pointer-events-none"></div>
                        </div>

                        <div class="relative">
                            <label for="mensaje" class="block text-sm font-semibold text-gray-700 mb-2">Mensaje</label>
                            <textarea id="mensaje" name="mensaje" rows="5" required
                                class="w-full px-4 py-4 bg-white/70 border border-gray-200 rounded-xl focus:ring-2 focus:ring-crypto-purple focus:border-crypto-purple focus:bg-white transition-all duration-300 hover:shadow-md resize-none"
                                placeholder="Cuéntanos sobre tu restaurante: cuántas mesas tienen, qué tipo de comida sirven, qué problemas actuales necesitan resolver..."></textarea>
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-crypto-purple/5 to-transparent opacity-0 hover:opacity-100 transition-opacity pointer-events-none"></div>
                        </div>

                        <button type="submit" class="w-full bg-gradient-to-r from-crypto-purple to-crypto-light-purple hover:from-crypto-dark-purple hover:to-crypto-purple text-white py-5 px-8 rounded-xl font-semibold text-lg shadow-lg hover:shadow-crypto-purple/25 transition-all duration-300 transform hover:scale-105 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Enviar mensaje
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

<!-- WhatsApp Floating Button -->
<a href="https://wa.me/{{ str_replace(['+', '-', ' '], '', $whatsappNumber) }}?text=Hola!%20Me%20interesa%20MOZO%20QR%20para%20mi%20restaurante" target="_blank"
   class="fixed bottom-8 left-8 bg-green-500 hover:bg-green-600 text-white p-4 rounded-full shadow-lg transition-all duration-300 z-50 hover:scale-110 group">
    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.302"/>
    </svg>
    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 bg-gray-900 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
        ¡Chatea con nosotros!
    </div>
</a>

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
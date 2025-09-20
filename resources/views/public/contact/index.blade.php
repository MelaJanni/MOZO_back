@extends('layouts.mozo-public')

@section('title', 'Contacto - MOZO QR')
@section('description', 'Contáctanos para resolver tus dudas sobre MOZO QR. Estamos aquí para ayudarte a digitalizar tu restaurante.')

@section('content')
<!-- Hero Section -->
<section class="relative min-h-[60vh] bg-gradient-to-br from-crypto-blue via-crypto-dark-blue to-gray-900 overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-1/4 left-10 w-96 h-96 bg-crypto-purple/15 rounded-full filter blur-3xl animate-pulse-slow"></div>
        <div class="absolute bottom-1/4 right-10 w-72 h-72 bg-crypto-light-purple/10 rounded-full filter blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
    </div>

    <!-- Grid pattern overlay -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%239f54fd" fill-opacity="0.02"%3E%3Cpath d="M30 30h30v30H30zM0 0h30v30H0z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>

    <div class="container mx-auto px-4 py-20 relative z-10">
        <div class="text-center max-w-4xl mx-auto animate-on-scroll">
            <div class="inline-flex items-center glass rounded-full px-6 py-3 mb-6">
                <span class="text-sm font-medium text-crypto-purple mr-2">💬 Contacto</span>
                <span class="text-sm text-gray-300">Respuesta en 24h</span>
            </div>
            <h1 class="text-4xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                <span class="text-gradient">¿Necesitas Ayuda?</span><br class="hidden sm:block">
                <span class="text-white">Estamos Aquí para Ti</span>
            </h1>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto mb-8">
                Nuestro equipo de expertos está listo para ayudarte a digitalizar tu restaurante y resolver todas tus dudas sobre MOZO QR.
            </p>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="py-24 bg-gradient-to-br from-gray-50 via-gray-100 to-gray-200 relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cdefs%3E%3Cpattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"%3E%3Cpath d="M 10 0 L 0 0 0 10" fill="none" stroke="%23e5e7eb" stroke-width="0.5"/%3E%3C/pattern%3E%3C/defs%3E%3Crect width="100" height="100" fill="url(%23grid)"/%3E%3C/svg%3E')] opacity-40"></div>

    <!-- Animated elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-1/4 right-20 w-64 h-64 bg-crypto-purple/5 rounded-full filter blur-3xl animate-pulse-slow"></div>
        <div class="absolute bottom-1/4 left-20 w-80 h-80 bg-blue-500/5 rounded-full filter blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <!-- Contact Methods -->
        <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto mb-20">
            <!-- Email -->
            <div class="text-center glass rounded-2xl p-8 border border-crypto-purple/20 hover:border-crypto-purple/40 transition-all animate-on-scroll hover-lift">
                <div class="w-16 h-16 bg-gradient-to-br from-crypto-purple to-crypto-dark-purple rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Email</h3>
                <p class="text-gray-600 mb-4">Escríbenos directamente</p>
                <div class="space-y-2">
                    <a href="mailto:contacto@mozoqr.com" class="block text-crypto-purple hover:text-crypto-dark-purple font-medium">contacto@mozoqr.com</a>
                    <a href="mailto:soporte@mozoqr.com" class="block text-crypto-purple hover:text-crypto-dark-purple font-medium">soporte@mozoqr.com</a>
                </div>
            </div>

            <!-- WhatsApp -->
            <div class="text-center glass rounded-2xl p-8 border border-green-500/20 hover:border-green-500/40 transition-all animate-on-scroll hover-lift">
                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.302"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">WhatsApp</h3>
                <p class="text-gray-600 mb-4">Chatea con nosotros</p>
                <a href="https://wa.me/5491234567890?text=Hola!%20Me%20interesa%20MOZO%20QR%20para%20mi%20restaurante"
                   target="_blank"
                   class="inline-flex items-center bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.302"/>
                    </svg>
                    Iniciar Chat
                </a>
            </div>

            <!-- Office -->
            <div class="text-center glass rounded-2xl p-8 border border-blue-500/20 hover:border-blue-500/40 transition-all animate-on-scroll hover-lift">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Oficina</h3>
                <p class="text-gray-600 mb-4">Visítanos o contáctanos</p>
                <div class="space-y-2 text-sm text-gray-600">
                    <p>Buenos Aires, Argentina</p>
                    <p>Lun - Vie: 9:00 - 18:00</p>
                    <p>Soporte 24/7</p>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="max-w-4xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12">
                <!-- Form -->
                <div class="group relative animate-on-scroll">
                    <div class="absolute inset-0 bg-gradient-to-br from-crypto-purple/10 to-crypto-light-purple/5 rounded-3xl transform rotate-1 group-hover:rotate-0 transition-transform duration-300"></div>
                    <div class="relative bg-white/80 backdrop-blur-lg rounded-3xl p-10 shadow-2xl border border-white/50 hover:shadow-crypto-purple/20 transition-all duration-300">
                        <div class="text-center mb-8">
                            <div class="w-16 h-16 bg-gradient-to-br from-crypto-purple to-crypto-dark-purple rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                            <h2 class="text-3xl font-bold text-gray-900 mb-2">¡Hablemos!</h2>
                            <p class="text-gray-600">Cuéntanos sobre tu restaurante y cómo podemos ayudarte</p>
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
                                <label for="tipo_consulta" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de consulta</label>
                                <select id="tipo_consulta" name="tipo_consulta" required
                                    class="w-full px-4 py-4 bg-white/70 border border-gray-200 rounded-xl focus:ring-2 focus:ring-crypto-purple focus:border-crypto-purple focus:bg-white transition-all duration-300 hover:shadow-md">
                                    <option value="">Selecciona una opción</option>
                                    <option value="informacion">📋 Información general</option>
                                    <option value="demo">🎯 Solicitar demo</option>
                                    <option value="precios">💰 Consulta de precios</option>
                                    <option value="soporte">🔧 Soporte técnico</option>
                                    <option value="ventas">💼 Hablar con ventas</option>
                                    <option value="otro">❓ Otro</option>
                                </select>
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

                <!-- FAQ -->
                <div class="space-y-8 animate-on-scroll">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">
                            Preguntas <span class="text-gradient bg-gradient-to-r from-crypto-purple to-crypto-light-purple bg-clip-text text-transparent">Frecuentes</span>
                        </h2>
                        <p class="text-gray-600 mb-8">Encuentra respuestas rápidas a las consultas más comunes</p>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white/80 backdrop-blur-lg rounded-2xl p-6 shadow-lg border border-white/50 hover:shadow-xl transition-all">
                            <h3 class="font-bold text-gray-900 mb-3 flex items-center">
                                <span class="w-8 h-8 bg-crypto-purple/10 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-crypto-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                                ¿Cuánto tiempo toma implementar MOZO QR?
                            </h3>
                            <p class="text-gray-600">La implementación es muy rápida. Puedes tener tu restaurante funcionando con códigos QR en menos de 30 minutos.</p>
                        </div>

                        <div class="bg-white/80 backdrop-blur-lg rounded-2xl p-6 shadow-lg border border-white/50 hover:shadow-xl transition-all">
                            <h3 class="font-bold text-gray-900 mb-3 flex items-center">
                                <span class="w-8 h-8 bg-green-500/10 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </span>
                                ¿Hay costos ocultos o comisiones?
                            </h3>
                            <p class="text-gray-600">No, nuestros precios son transparentes. Solo pagas la suscripción mensual sin comisiones adicionales por transacciones.</p>
                        </div>

                        <div class="bg-white/80 backdrop-blur-lg rounded-2xl p-6 shadow-lg border border-white/50 hover:shadow-xl transition-all">
                            <h3 class="font-bold text-gray-900 mb-3 flex items-center">
                                <span class="w-8 h-8 bg-blue-500/10 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                </span>
                                ¿Qué soporte técnico ofrecen?
                            </h3>
                            <p class="text-gray-600">Ofrecemos soporte 24/7 por email, chat y WhatsApp. También incluimos capacitación gratuita para tu equipo.</p>
                        </div>

                        <div class="bg-white/80 backdrop-blur-lg rounded-2xl p-6 shadow-lg border border-white/50 hover:shadow-xl transition-all">
                            <h3 class="font-bold text-gray-900 mb-3 flex items-center">
                                <span class="w-8 h-8 bg-yellow-500/10 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                                ¿Puedo personalizar los códigos QR?
                            </h3>
                            <p class="text-gray-600">Sí, puedes personalizar completamente el diseño de los códigos QR con tu logo, colores y estilo de marca.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-br from-crypto-blue via-crypto-dark-blue to-gray-900 relative overflow-hidden">
    <div class="absolute inset-0">
        <div class="absolute top-1/4 left-10 w-72 h-72 bg-crypto-purple/10 rounded-full filter blur-3xl animate-pulse-slow"></div>
        <div class="absolute bottom-1/4 right-10 w-96 h-96 bg-crypto-light-purple/10 rounded-full filter blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
    </div>

    <div class="container mx-auto px-4 text-center relative z-10">
        <div class="max-w-4xl mx-auto animate-on-scroll">
            <h2 class="text-3xl lg:text-5xl font-bold text-white mb-6">
                ¿Listo para <span class="text-gradient">comenzar</span>?
            </h2>
            <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
                Más de 500 restaurantes ya confían en MOZO QR. Únete a ellos y transforma tu negocio hoy mismo.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('public.plans.index') }}"
                   class="btn-primary text-white px-8 py-4 rounded-xl font-semibold text-lg inline-flex items-center justify-center">
                    Ver Planes
                    <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
                <a href="https://wa.me/5491234567890?text=Hola!%20Me%20interesa%20una%20demo%20de%20MOZO%20QR"
                   target="_blank"
                   class="border-2 border-green-500 text-green-400 bg-white/10 backdrop-blur-sm px-8 py-4 rounded-xl font-semibold text-lg hover:bg-green-500 hover:text-white transition-all inline-flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.302"/>
                    </svg>
                    Chat WhatsApp
                </a>
            </div>
        </div>
    </div>
</section>

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
});
</script>
@endsection
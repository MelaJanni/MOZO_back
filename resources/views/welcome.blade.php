@extends('layouts.mozo-public')

@section('title', 'MOZO QR - Digitaliza tu restaurante')
@section('description', 'Transforma tu restaurante con códigos QR inteligentes. Gestión de mesas, menús digitales y notificaciones en tiempo real.')

@section('content')
<!-- Hero Section -->
<section class="bg-white py-16 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <!-- Left content -->
            <div class="space-y-8">
                <div class="space-y-6">
                    <span class="inline-block px-4 py-2 bg-mozo-100 text-mozo-700 text-sm font-medium rounded-full">
                        📱 Disponible en Play Store
                    </span>
                    <h1 class="text-4xl lg:text-6xl font-bold leading-tight text-gray-900">
                        Digitaliza tu
                        <span class="text-mozo-600">
                            restaurante
                        </span>
                        con QR
                    </h1>
                    <p class="text-xl text-gray-600 leading-relaxed">
                        Códigos QR inteligentes para mesas, menús digitales interactivos y notificaciones en tiempo real. Todo lo que necesitas para modernizar tu negocio.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="#download" class="inline-flex items-center justify-center px-8 py-4 bg-mozo-600 text-white font-semibold rounded-xl hover:bg-mozo-700 transition-all duration-300 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Descargar App
                    </a>
                    <a href="#demo" class="inline-flex items-center justify-center px-8 py-4 border-2 border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-all duration-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M15 11h1m-1 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Ver demo
                    </a>
                </div>

                <div class="flex flex-wrap gap-6 text-sm text-gray-500">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Sin instalación
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Configuración rápida
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Soporte incluido
                    </div>
                </div>
            </div>

            <!-- Right content - QR Demo -->
            <div class="relative">
                <div class="bg-white rounded-3xl p-8 border border-gray-200 shadow-xl">
                    <div class="text-center space-y-6">
                        <div class="w-20 h-20 bg-mozo-600 rounded-2xl flex items-center justify-center mx-auto">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">Mesa 5</h3>
                        <p class="text-gray-600">Escanea para ver el menú</p>

                        <div class="bg-gray-50 p-4 rounded-xl">
                            <div class="w-32 h-32 bg-gray-900 mx-auto rounded-lg flex items-center justify-center">
                                <span class="text-white text-xs">Código QR</span>
                            </div>
                        </div>

                        <button class="w-full bg-mozo-600 text-white py-3 px-6 rounded-xl font-semibold hover:bg-mozo-700 transition-colors">
                            Llamar mozo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center space-y-4 mb-16">
            <h2 class="text-3xl lg:text-5xl font-bold text-gray-900">
                Todo lo que necesitas para
                <span class="text-mozo-600">modernizar tu restaurante</span>
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Simplifica la gestión de tu restaurante con herramientas diseñadas para mejorar la experiencia de tus clientes
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all border border-gray-100">
                <div class="w-12 h-12 bg-mozo-600 rounded-xl flex items-center justify-center mb-6">
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
            <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all border border-gray-100">
                <div class="w-12 h-12 bg-mozo-600 rounded-xl flex items-center justify-center mb-6">
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
            <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all border border-gray-100">
                <div class="w-12 h-12 bg-mozo-600 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM12 17h-7a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v5"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Notificaciones Instantáneas</h3>
                <p class="text-gray-600 leading-relaxed">
                    Los mozos reciben alertas instantáneas en su móvil cuando un cliente necesita atención. Sin esperas.
                </p>
            </div>

            <!-- Feature 4 -->
            <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all border border-gray-100">
                <div class="w-12 h-12 bg-mozo-600 rounded-xl flex items-center justify-center mb-6">
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
            <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all border border-gray-100">
                <div class="w-12 h-12 bg-mozo-600 rounded-xl flex items-center justify-center mb-6">
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
            <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all border border-gray-100">
                <div class="w-12 h-12 bg-mozo-600 rounded-xl flex items-center justify-center mb-6">
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

<!-- Download Section -->
<section id="download" class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div class="space-y-6">
                <h2 class="text-3xl lg:text-5xl font-bold text-gray-900">
                    Descarga la app
                    <span class="text-mozo-600">MOZO QR</span>
                </h2>
                <p class="text-xl text-gray-600">
                    Disponible para Android en Google Play Store. Gestiona tu restaurante desde cualquier lugar.
                </p>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-gray-700">Panel de administración completo</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-gray-700">Notificaciones push en tiempo real</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-gray-700">Estadísticas y reportes detallados</span>
                    </div>
                </div>
                <a href="#" class="inline-flex items-center bg-gray-900 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-gray-800 transition-colors">
                    <svg class="w-6 h-6 mr-3" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.523 15.3414c-.5511-.8955-1.2488-1.5861-1.9789-2.0812-.0985-.2013-.2049-.4089-.3191-.6221l.0191-.0141c1.0193-1.0193 1.4628-2.4407 1.4628-3.8621 0-1.3047-.4435-2.6129-1.4628-3.6322C14.2239 4.1184 12.6104 3.6749 10.9969 3.6749c-1.6135 0-3.227.4435-4.2463 1.4628C5.7314 6.1569 5.2879 7.465 5.2879 8.7697c0 1.4214.4435 2.8428 1.4628 3.8621l.0191.0141c-.1142.2132-.2206.4208-.3191.6221-.7301.4951-1.4278 1.1857-1.9789 2.0812C3.6504 16.8176 3.4 18.4973 3.4 20.2009h1.4c0-1.4214.3255-2.7862 1.1857-3.8621.7301-1.0759 1.7253-1.6135 2.8428-1.6135h3.1683c1.1175 0 2.1127.5376 2.8428 1.6135.8602 1.0759 1.1857 2.4407 1.1857 3.8621h1.4c0-1.7036-.2504-3.3833-1.0896-4.8591zM7.0159 6.8884C7.7301 6.1742 8.7998 5.8746 9.9969 5.8746c1.1971 0 2.2668.2996 2.981.9138.7142.6142 1.1135 1.4214 1.1135 2.281 0 .8596-.3993 1.6668-1.1135 2.281-.7142.6142-1.7839.9138-2.981.9138-1.1971 0-2.2668-.2996-2.981-.9138C6.3017 10.436 5.9024 9.6288 5.9024 8.7692c0-.8596.3993-1.6668 1.1135-2.281z"/>
                    </svg>
                    Próximamente en Play Store
                </a>
            </div>
            <div class="text-center">
                <div class="bg-gray-100 rounded-3xl p-12 inline-block">
                    <div class="w-64 h-64 bg-white rounded-2xl shadow-xl mx-auto flex items-center justify-center">
                        <div class="text-center space-y-4">
                            <div class="w-16 h-16 bg-mozo-600 rounded-xl mx-auto flex items-center justify-center">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="text-gray-900 font-bold">MOZO QR</div>
                            <div class="text-sm text-gray-600">App Admin</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-16 bg-mozo-600 text-white">
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

<!-- Contact Section -->
<section id="contact" class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12">
            <!-- Contact Form -->
            <div class="bg-white rounded-2xl p-8 shadow-lg">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Contactanos</h3>
                <form action="#" method="POST" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                            <input type="text" id="nombre" name="nombre" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-mozo-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="apellido" class="block text-sm font-medium text-gray-700 mb-1">Apellido</label>
                            <input type="text" id="apellido" name="apellido" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-mozo-500 focus:border-transparent">
                        </div>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-mozo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-mozo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="mensaje" class="block text-sm font-medium text-gray-700 mb-1">Mensaje</label>
                        <textarea id="mensaje" name="mensaje" rows="4" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-mozo-500 focus:border-transparent"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-mozo-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-mozo-700 transition-colors">
                        Enviar mensaje
                    </button>
                </form>
            </div>

            <!-- Support Form -->
            <div class="bg-white rounded-2xl p-8 shadow-lg">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Soporte técnico</h3>
                <form action="#" method="POST" class="space-y-4">
                    <div>
                        <label for="empresa" class="block text-sm font-medium text-gray-700 mb-1">Nombre de la empresa</label>
                        <input type="text" id="empresa" name="empresa" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-mozo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="contacto_email" class="block text-sm font-medium text-gray-700 mb-1">Email de contacto</label>
                        <input type="email" id="contacto_email" name="contacto_email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-mozo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="tipo_problema" class="block text-sm font-medium text-gray-700 mb-1">Tipo de problema</label>
                        <select id="tipo_problema" name="tipo_problema" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-mozo-500 focus:border-transparent">
                            <option value="">Selecciona una opción</option>
                            <option value="tecnico">Problema técnico</option>
                            <option value="configuracion">Ayuda con configuración</option>
                            <option value="facturacion">Consulta de facturación</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción del problema</label>
                        <textarea id="descripcion" name="descripcion" rows="4" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-mozo-500 focus:border-transparent" placeholder="Describe el problema en detalle..."></textarea>
                    </div>
                    <button type="submit" class="w-full bg-mozo-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-mozo-700 transition-colors">
                        Solicitar soporte
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="space-y-8">
            <h2 class="text-3xl lg:text-5xl font-bold text-gray-900">
                ¿Listo para
                <span class="text-mozo-600">modernizar</span>
                tu restaurante?
            </h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Únete a cientos de restaurantes que ya digitalizaron sus operaciones con MOZO QR.
                Descarga la app y comienza hoy mismo.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#download" class="bg-mozo-600 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-mozo-700 transition-all duration-300 shadow-lg hover:shadow-xl">
                    Descargar App
                </a>
                <a href="#contact" class="border-2 border-mozo-600 text-mozo-600 px-8 py-4 rounded-xl font-semibold text-lg hover:bg-mozo-50 transition-all duration-300">
                    Contactar
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
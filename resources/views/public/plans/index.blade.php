@extends('layouts.mozo-public')

@section('title', 'Planes - MOZO QR')
@section('description', 'Descubre todos los planes disponibles de MOZO QR para tu restaurante. Encuentra la solución perfecta para digitalizar tu negocio.')

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
                <span class="text-sm font-medium text-crypto-purple mr-2">💰 Planes</span>
                <span class="text-sm text-gray-300">Soluciones para cada negocio</span>
            </div>
            <h1 class="text-4xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                <span class="text-gradient">Elige el Plan Perfecto</span><br class="hidden sm:block">
                <span class="text-white">para tu Restaurante</span>
            </h1>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto mb-8">
                Desde pequeños cafés hasta grandes cadenas. Encuentra la solución que se adapte a las necesidades específicas de tu negocio.
            </p>
        </div>
    </div>
</section>

<!-- Plans Section -->
<section class="py-20 bg-gradient-to-br from-gray-50 via-gray-100 to-gray-200 relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cdefs%3E%3Cpattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"%3E%3Cpath d="M 10 0 L 0 0 0 10" fill="none" stroke="%23e5e7eb" stroke-width="0.5"/%3E%3C/pattern%3E%3C/defs%3E%3Crect width="100" height="100" fill="url(%23grid)"/%3E%3C/svg%3E')] opacity-40"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="grid md:grid-cols-{{ $plans->count() > 2 ? '3' : $plans->count() }} gap-8 mb-20 max-w-7xl mx-auto">
            @foreach($plans as $plan)
            <div class="group relative animate-on-scroll">
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
                            <span class="text-5xl font-bold {{ $plan->is_popular ? 'text-crypto-purple' : 'text-gray-900' }}">${{ number_format($plan->price_ars, 0) }}</span>
                            <span class="text-gray-600 text-xl">/mes</span>
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
                            <li class="flex items-center">
                                <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <span class="text-gray-700">Hasta <strong>{{ $plan->getMaxTables() }}</strong> mesas</span>
                            </li>
                            <li class="flex items-center">
                                <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <span class="text-gray-700"><strong>{{ $plan->getMaxStaff() }}</strong> usuarios/mozos</span>
                            </li>
                            <li class="flex items-center">
                                <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <span class="text-gray-700"><strong>{{ $plan->getMaxBusinesses() }}</strong> {{ $plan->getMaxBusinesses() == 1 ? 'restaurante' : 'restaurantes' }}</span>
                            </li>
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
                            @endif
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-4">
                        <a href="{{ route('public.checkout.plan', $plan) }}"
                           class="w-full {{ $plan->is_popular ? 'bg-gradient-to-r from-crypto-purple to-crypto-light-purple hover:from-crypto-dark-purple hover:to-crypto-purple' : 'bg-gradient-to-r from-gray-700 to-gray-900 hover:from-gray-800 hover:to-gray-900' }} text-white font-bold py-4 px-8 rounded-xl transition-all duration-300 text-center block transform hover:scale-105 shadow-lg">
                            Empezar con {{ $plan->name }}
                        </a>

                        <a href="{{ route('public.plans.show', $plan) }}"
                           class="w-full border-2 {{ $plan->is_popular ? 'border-crypto-purple text-crypto-purple hover:bg-crypto-purple' : 'border-gray-300 text-gray-700 hover:bg-gray-100' }} hover:text-white font-semibold py-3 px-8 rounded-xl transition-all duration-300 text-center block">
                            Ver Detalles
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Comparison Table -->
        <div class="max-w-6xl mx-auto animate-on-scroll">
            <div class="bg-white/80 backdrop-blur-lg rounded-3xl shadow-2xl border border-white/50 overflow-hidden">
                <div class="bg-gradient-to-r from-crypto-purple to-crypto-light-purple p-8 text-center">
                    <h2 class="text-3xl font-bold text-white mb-2">Comparación Detallada</h2>
                    <p class="text-crypto-light text-lg">Encuentra las diferencias entre nuestros planes</p>
                </div>

                <div class="p-8">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b-2 border-gray-200">
                                    <th class="text-left py-6 px-4 font-bold text-gray-900 text-lg">Característica</th>
                                    @foreach($plans as $plan)
                                    <th class="text-center py-6 px-4">
                                        <div class="font-bold text-lg {{ $plan->is_popular ? 'text-crypto-purple' : 'text-gray-900' }}">{{ $plan->name }}</div>
                                        <div class="text-sm text-gray-600 mt-1">${{ number_format($plan->price_ars, 0) }}/mes</div>
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-4 px-4 font-medium">🪑 Mesas</td>
                                    @foreach($plans as $plan)
                                    <td class="text-center py-4 px-4 font-semibold">{{ $plan->getMaxTables() }}</td>
                                    @endforeach
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-4 px-4 font-medium">👥 Usuarios/Mozos</td>
                                    @foreach($plans as $plan)
                                    <td class="text-center py-4 px-4 font-semibold">{{ $plan->getMaxStaff() }}</td>
                                    @endforeach
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-4 px-4 font-medium">🏪 Restaurantes</td>
                                    @foreach($plans as $plan)
                                    <td class="text-center py-4 px-4 font-semibold">{{ $plan->getMaxBusinesses() }}</td>
                                    @endforeach
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-4 px-4 font-medium">🎁 Período de Prueba</td>
                                    @foreach($plans as $plan)
                                    <td class="text-center py-4 px-4">
                                        @if($plan->hasTrialEnabled())
                                            <div class="inline-flex items-center text-green-600">
                                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                {{ $plan->getTrialDays() }} días
                                            </div>
                                        @else
                                            <span class="text-red-500">—</span>
                                        @endif
                                    </td>
                                    @endforeach
                                </tr>
                                @if($plans->first()->features && is_array($plans->first()->features))
                                    @php
                                        $allFeatures = collect($plans->flatMap(function($plan) {
                                            return is_array($plan->features) ? $plan->features : [];
                                        }))->unique();
                                    @endphp
                                    @foreach($allFeatures as $feature)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-4 px-4 font-medium">{{ $feature }}</td>
                                        @foreach($plans as $plan)
                                        <td class="text-center py-4 px-4">
                                            @if($plan->hasFeature($feature))
                                                <svg class="w-5 h-5 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            @else
                                                <span class="text-red-500">—</span>
                                            @endif
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
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
                ¿Listo para <span class="text-gradient">transformar</span> tu restaurante?
            </h2>
            <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
                Únete a cientos de restaurantes que ya confían en MOZO QR para digitalizar sus operaciones.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('public.checkout.index') }}"
                   class="btn-primary text-white px-8 py-4 rounded-xl font-semibold text-lg inline-flex items-center justify-center">
                    Empezar Ahora
                    <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
                <a href="#contact"
                   class="border-2 border-crypto-purple text-crypto-purple bg-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-crypto-purple hover:text-white transition-all inline-flex items-center justify-center">
                    Contactar Ventas
                    <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-20 bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16 animate-on-scroll">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">
                Preguntas <span class="text-gradient bg-gradient-to-r from-crypto-purple to-crypto-light-purple bg-clip-text text-transparent">Frecuentes</span>
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Resolvemos las dudas más comunes sobre nuestros planes
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-8 max-w-6xl mx-auto">
            <div class="bg-white/80 backdrop-blur-lg rounded-2xl p-8 shadow-lg border border-white/50 hover:shadow-xl transition-all animate-on-scroll">
                <div class="flex items-start">
                    <div class="w-12 h-12 bg-crypto-purple/10 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-crypto-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-3 text-lg">¿Puedo cambiar de plan?</h3>
                        <p class="text-gray-600">Sí, puedes actualizar o degradar tu plan en cualquier momento desde tu panel de control. Los cambios se aplican inmediatamente.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white/80 backdrop-blur-lg rounded-2xl p-8 shadow-lg border border-white/50 hover:shadow-xl transition-all animate-on-scroll">
                <div class="flex items-start">
                    <div class="w-12 h-12 bg-green-500/10 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-3 text-lg">¿Hay permanencia?</h3>
                        <p class="text-gray-600">No, puedes cancelar tu suscripción en cualquier momento sin penalizaciones. Tus datos se mantienen seguros durante 30 días adicionales.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white/80 backdrop-blur-lg rounded-2xl p-8 shadow-lg border border-white/50 hover:shadow-xl transition-all animate-on-scroll">
                <div class="flex items-start">
                    <div class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-3 text-lg">¿Qué incluye el soporte?</h3>
                        <p class="text-gray-600">Soporte técnico 24/7 por email, chat en vivo y teléfono. Además, acceso completo a nuestra base de conocimientos y videotutoriales.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white/80 backdrop-blur-lg rounded-2xl p-8 shadow-lg border border-white/50 hover:shadow-xl transition-all animate-on-scroll">
                <div class="flex items-start">
                    <div class="w-12 h-12 bg-yellow-500/10 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-3 text-lg">¿Cómo funciona el período de prueba?</h3>
                        <p class="text-gray-600">Puedes probar todas las funciones sin costo durante los días indicados. No se requiere tarjeta de crédito para comenzar tu prueba gratuita.</p>
                    </div>
                </div>
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
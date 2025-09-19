<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MOZO QR')</title>
    <meta name="description" content="@yield('description', 'Digitaliza tu restaurante con MOZO QR')">

    <!-- Filament Styles -->
    @filamentStyles

    <!-- Custom Styles -->
    <style>
        [x-cloak] { display: none !important; }
        .fi-color-primary { --c-50: 239 246 255; --c-100: 219 234 254; --c-200: 191 219 254; --c-300: 147 197 253; --c-400: 96 165 250; --c-500: 59 130 246; --c-600: 37 99 235; --c-700: 29 78 216; --c-800: 30 64 175; --c-900: 30 58 138; --c-950: 23 37 84; }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 font-sans antialiased">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                        MOZO QR
                    </a>
                </div>
                <nav class="hidden md:flex space-x-8">
                    <a href="{{ route('public.plans.index') }}" class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 font-medium">
                        Planes
                    </a>
                    <a href="#features" class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 font-medium">
                        Características
                    </a>
                    <a href="#contact" class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 font-medium">
                        Contacto
                    </a>
                    <a href="/admin" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 font-medium transition">
                        Iniciar Sesión
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h5 class="text-xl font-bold mb-4 text-primary-400">MOZO QR</h5>
                    <p class="text-gray-400">La plataforma de gestión de restaurantes más simple y eficiente.</p>
                </div>
                <div>
                    <h6 class="font-semibold mb-4">Producto</h6>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('public.plans.index') }}" class="hover:text-white transition">Planes</a></li>
                        <li><a href="#" class="hover:text-white transition">Características</a></li>
                        <li><a href="#" class="hover:text-white transition">Demo</a></li>
                    </ul>
                </div>
                <div>
                    <h6 class="font-semibold mb-4">Soporte</h6>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition">Centro de Ayuda</a></li>
                        <li><a href="#" class="hover:text-white transition">Contacto</a></li>
                        <li><a href="#" class="hover:text-white transition">Estado del Servicio</a></li>
                    </ul>
                </div>
                <div>
                    <h6 class="font-semibold mb-4">Legal</h6>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition">Términos de Uso</a></li>
                        <li><a href="#" class="hover:text-white transition">Privacidad</a></li>
                        <li><a href="#" class="hover:text-white transition">Cookies</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} MOZO QR. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    @filamentScripts

    @stack('scripts')
</body>
</html>
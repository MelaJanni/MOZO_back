<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MOZO QR')</title>
    <meta name="description" content="@yield('description', 'Digitaliza tu restaurante con MOZO QR')">

    <!-- Tailwind CSS (Base de Filament) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom Styles -->
    <style>
        [x-cloak] { display: none !important; }

        /* Colores primary (azul) inspirados en Filament */
        :root {
            --primary-50: 239 246 255;
            --primary-100: 219 234 254;
            --primary-200: 191 219 254;
            --primary-300: 147 197 253;
            --primary-400: 96 165 250;
            --primary-500: 59 130 246;
            --primary-600: 37 99 235;
            --primary-700: 29 78 216;
            --primary-800: 30 64 175;
            --primary-900: 30 58 138;
            --primary-950: 23 37 84;
        }

        /* Clases personalizadas para colores primary */
        .text-primary-50 { color: rgb(var(--primary-50)); }
        .text-primary-100 { color: rgb(var(--primary-100)); }
        .text-primary-200 { color: rgb(var(--primary-200)); }
        .text-primary-300 { color: rgb(var(--primary-300)); }
        .text-primary-400 { color: rgb(var(--primary-400)); }
        .text-primary-500 { color: rgb(var(--primary-500)); }
        .text-primary-600 { color: rgb(var(--primary-600)); }
        .text-primary-700 { color: rgb(var(--primary-700)); }
        .text-primary-800 { color: rgb(var(--primary-800)); }
        .text-primary-900 { color: rgb(var(--primary-900)); }
        .text-primary-950 { color: rgb(var(--primary-950)); }

        .bg-primary-50 { background-color: rgb(var(--primary-50)); }
        .bg-primary-100 { background-color: rgb(var(--primary-100)); }
        .bg-primary-200 { background-color: rgb(var(--primary-200)); }
        .bg-primary-300 { background-color: rgb(var(--primary-300)); }
        .bg-primary-400 { background-color: rgb(var(--primary-400)); }
        .bg-primary-500 { background-color: rgb(var(--primary-500)); }
        .bg-primary-600 { background-color: rgb(var(--primary-600)); }
        .bg-primary-700 { background-color: rgb(var(--primary-700)); }
        .bg-primary-800 { background-color: rgb(var(--primary-800)); }
        .bg-primary-900 { background-color: rgb(var(--primary-900)); }
        .bg-primary-950 { background-color: rgb(var(--primary-950)); }

        .border-primary-50 { border-color: rgb(var(--primary-50)); }
        .border-primary-100 { border-color: rgb(var(--primary-100)); }
        .border-primary-200 { border-color: rgb(var(--primary-200)); }
        .border-primary-300 { border-color: rgb(var(--primary-300)); }
        .border-primary-400 { border-color: rgb(var(--primary-400)); }
        .border-primary-500 { border-color: rgb(var(--primary-500)); }
        .border-primary-600 { border-color: rgb(var(--primary-600)); }
        .border-primary-700 { border-color: rgb(var(--primary-700)); }
        .border-primary-800 { border-color: rgb(var(--primary-800)); }
        .border-primary-900 { border-color: rgb(var(--primary-900)); }
        .border-primary-950 { border-color: rgb(var(--primary-950)); }

        .ring-primary-100 { --tw-ring-color: rgb(var(--primary-100)); }
        .ring-primary-200 { --tw-ring-color: rgb(var(--primary-200)); }
        .ring-primary-300 { --tw-ring-color: rgb(var(--primary-300)); }
        .ring-primary-400 { --tw-ring-color: rgb(var(--primary-400)); }
        .ring-primary-500 { --tw-ring-color: rgb(var(--primary-500)); }
        .ring-primary-600 { --tw-ring-color: rgb(var(--primary-600)); }
        .ring-primary-700 { --tw-ring-color: rgb(var(--primary-700)); }

        .focus\:ring-primary-500:focus { --tw-ring-color: rgb(var(--primary-500)); }
        .focus\:border-primary-500:focus { border-color: rgb(var(--primary-500)); }

        .hover\:bg-primary-50:hover { background-color: rgb(var(--primary-50)); }
        .hover\:bg-primary-100:hover { background-color: rgb(var(--primary-100)); }
        .hover\:bg-primary-600:hover { background-color: rgb(var(--primary-600)); }
        .hover\:bg-primary-700:hover { background-color: rgb(var(--primary-700)); }
        .hover\:bg-primary-800:hover { background-color: rgb(var(--primary-800)); }

        .hover\:text-primary-300:hover { color: rgb(var(--primary-300)); }
        .hover\:text-primary-400:hover { color: rgb(var(--primary-400)); }
        .hover\:text-primary-600:hover { color: rgb(var(--primary-600)); }
        .hover\:text-primary-700:hover { color: rgb(var(--primary-700)); }

        /* Dark mode support */
        .dark .dark\:text-primary-400 { color: rgb(var(--primary-400)); }
        .dark .dark\:bg-primary-900 { background-color: rgb(var(--primary-900)); }
        .dark .dark\:border-primary-800 { border-color: rgb(var(--primary-800)); }
        .dark .dark\:hover\:bg-primary-900\/20:hover { background-color: rgba(var(--primary-900), 0.2); }
        .dark .dark\:hover\:text-primary-300:hover { color: rgb(var(--primary-300)); }
        .dark .dark\:hover\:text-primary-400:hover { color: rgb(var(--primary-400)); }

        /* Estilos adicionales inspirados en Filament */
        .shadow-xl {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .shadow-2xl {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .transition {
            transition-property: all;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 300ms;
        }
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
    @stack('scripts')
</body>
</html>
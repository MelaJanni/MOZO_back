<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MOZO QR - Digitaliza tu restaurante')</title>
    <meta name="description" content="@yield('description', 'Transforma tu restaurante con códigos QR inteligentes. Gestión de mesas, menús digitales y notificaciones en tiempo real.')">

    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        'purple': {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
                            950: '#2e1065'
                        },
                        'mozo': {
                            // Dark Purple Base
                            900: '#10002b',
                            800: '#240046',
                            700: '#3c096c',
                            600: '#5a189a',
                            // Primary Purple (Logo)
                            500: '#9f54fd',
                            // Light Purple Variants
                            400: '#7b2cbf',
                            300: '#9d4edd',
                            200: '#c77dff',
                            100: '#e0aaff',
                            50: '#f3e8ff'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Custom Styles -->
    <style>
        [x-cloak] { display: none !important; }

        /* Smooth animations */
        .transition-all {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Gradient backgrounds */
        .bg-gradient-mozo {
            background: linear-gradient(135deg, #10002b 0%, #3c096c 50%, #9f54fd 100%);
        }

        .bg-gradient-mozo-light {
            background: linear-gradient(135deg, #9d4edd 0%, #c77dff 50%, #e0aaff 100%);
        }

        /* Glass morphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Hover effects */
        .hover-lift {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(159, 84, 253, 0.15);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #9f54fd;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #7b2cbf;
        }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 font-sans antialiased">

    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-md dark:bg-gray-900/80 shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-mozo-500 to-mozo-600 rounded-lg"></div>
                        <span class="text-2xl font-bold bg-gradient-to-r from-mozo-700 to-mozo-500 bg-clip-text text-transparent">
                            MOZO QR
                        </span>
                    </a>
                </div>

                <nav class="hidden md:flex space-x-8">
                    <a href="#features" class="text-gray-700 dark:text-gray-300 hover:text-mozo-600 dark:hover:text-mozo-400 font-medium transition-all">
                        Características
                    </a>
                    <a href="{{ route('public.plans.index') }}" class="text-gray-700 dark:text-gray-300 hover:text-mozo-600 dark:hover:text-mozo-400 font-medium transition-all">
                        Planes
                    </a>
                    <a href="#contact" class="text-gray-700 dark:text-gray-300 hover:text-mozo-600 dark:hover:text-mozo-400 font-medium transition-all">
                        Contacto
                    </a>
                    @auth
                        <a href="/admin" class="bg-mozo-500 text-white px-4 py-2 rounded-lg hover:bg-mozo-600 font-medium transition-all hover-lift">
                            Dashboard
                        </a>
                    @else
                        <a href="/admin" class="bg-mozo-500 text-white px-4 py-2 rounded-lg hover:bg-mozo-600 font-medium transition-all hover-lift">
                            Iniciar Sesión
                        </a>
                    @endauth
                </nav>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button" class="text-gray-700 dark:text-gray-300 hover:text-mozo-600 dark:hover:text-mozo-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gradient-mozo text-white py-16 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div class="md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-8 h-8 bg-white/20 rounded-lg"></div>
                        <span class="text-2xl font-bold">MOZO QR</span>
                    </div>
                    <p class="text-gray-300 mb-6 max-w-md">
                        Transforma tu restaurante con tecnología QR inteligente.
                        Gestión simple, clientes felices, mayor eficiencia.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-white/20 transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-white/20 transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-white/20 transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <div>
                    <h6 class="font-semibold mb-4">Producto</h6>
                    <ul class="space-y-3 text-gray-300">
                        <li><a href="{{ route('public.plans.index') }}" class="hover:text-white transition-all">Planes y Precios</a></li>
                        <li><a href="#features" class="hover:text-white transition-all">Características</a></li>
                        <li><a href="#" class="hover:text-white transition-all">Demo en Vivo</a></li>
                        <li><a href="#" class="hover:text-white transition-all">Integración</a></li>
                    </ul>
                </div>

                <div>
                    <h6 class="font-semibold mb-4">Soporte</h6>
                    <ul class="space-y-3 text-gray-300">
                        <li><a href="#" class="hover:text-white transition-all">Centro de Ayuda</a></li>
                        <li><a href="#contact" class="hover:text-white transition-all">Contactar Soporte</a></li>
                        <li><a href="#" class="hover:text-white transition-all">API Docs</a></li>
                        <li><a href="#" class="hover:text-white transition-all">Estado del Sistema</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-white/20 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-300">
                    &copy; {{ date('Y') }} MOZO QR. Todos los derechos reservados.
                </p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-300 hover:text-white transition-all">Términos</a>
                    <a href="#" class="text-gray-300 hover:text-white transition-all">Privacidad</a>
                    <a href="#" class="text-gray-300 hover:text-white transition-all">Cookies</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
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
    </script>
    @stack('scripts')
</body>
</html>
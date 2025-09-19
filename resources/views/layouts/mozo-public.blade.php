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
<body class="h-full bg-white font-sans antialiased">

    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-mozo-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <span class="text-2xl font-bold text-mozo-600">
                            MOZO QR
                        </span>
                    </a>
                </div>

                <div class="flex items-center space-x-8">
                    <nav class="hidden md:flex items-center space-x-8">
                        <a href="#features" class="text-gray-700 hover:text-mozo-600 font-medium transition-colors">
                            Características
                        </a>
                        <a href="{{ route('public.plans.index') }}" class="text-gray-700 hover:text-mozo-600 font-medium transition-colors">
                            Planes
                        </a>
                        <a href="#contact" class="text-gray-700 hover:text-mozo-600 font-medium transition-colors">
                            Contacto
                        </a>
                    </nav>

                    @auth
                        <a href="/admin" class="bg-mozo-600 text-white px-4 py-2 rounded-lg hover:bg-mozo-700 font-medium transition-colors">
                            Dashboard
                        </a>
                    @else
                        <a href="/admin" class="bg-mozo-600 text-white px-4 py-2 rounded-lg hover:bg-mozo-700 font-medium transition-colors">
                            Iniciar Sesión
                        </a>
                    @endauth

                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button type="button" class="text-gray-700 hover:text-mozo-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-mozo-600 text-white py-16 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-mozo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <span class="text-2xl font-bold">MOZO QR</span>
                    </div>
                    <p class="text-mozo-100 mb-6 max-w-md">
                        Transforma tu restaurante con tecnología QR inteligente.
                        Gestión simple, clientes felices, mayor eficiencia.
                    </p>
                </div>

                <div>
                    <h6 class="font-semibold mb-4">Producto</h6>
                    <ul class="space-y-3 text-mozo-100">
                        <li><a href="{{ route('public.plans.index') }}" class="hover:text-white transition-colors">Planes y Precios</a></li>
                        <li><a href="#features" class="hover:text-white transition-colors">Características</a></li>
                        <li><a href="#demo" class="hover:text-white transition-colors">Demo en Vivo</a></li>
                        <li><a href="#download" class="hover:text-white transition-colors">Descargar App</a></li>
                    </ul>
                </div>

                <div>
                    <h6 class="font-semibold mb-4">Soporte</h6>
                    <ul class="space-y-3 text-mozo-100">
                        <li><a href="#contact" class="hover:text-white transition-colors">Centro de Ayuda</a></li>
                        <li><a href="#contact" class="hover:text-white transition-colors">Contactar Soporte</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">API Docs</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Estado del Sistema</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-mozo-500 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-mozo-100">
                    &copy; {{ date('Y') }} MOZO QR. Todos los derechos reservados.
                </p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-mozo-100 hover:text-white transition-colors">Términos</a>
                    <a href="#" class="text-mozo-100 hover:text-white transition-colors">Privacidad</a>
                    <a href="#" class="text-mozo-100 hover:text-white transition-colors">Cookies</a>
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
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
            darkMode: ['class'],
            theme: {
                container: {
                    center: true,
                    padding: '2rem',
                    screens: {
                        'sm': '640px',
                        'md': '768px',
                        'lg': '1024px',
                        'xl': '1280px',
                        '2xl': '1400px'
                    }
                },
                extend: {
                    fontFamily: {
                        'sans': ['Inter var', 'Inter', 'system-ui', 'sans-serif'],
                        'display': ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        border: 'hsl(var(--border))',
                        input: 'hsl(var(--input))',
                        ring: 'hsl(var(--ring))',
                        background: 'hsl(var(--background))',
                        foreground: 'hsl(var(--foreground))',
                        primary: {
                            DEFAULT: 'hsl(var(--primary))',
                            foreground: 'hsl(var(--primary-foreground))'
                        },
                        secondary: {
                            DEFAULT: 'hsl(var(--secondary))',
                            foreground: 'hsl(var(--secondary-foreground))'
                        },
                        muted: {
                            DEFAULT: 'hsl(var(--muted))',
                            foreground: 'hsl(var(--muted-foreground))'
                        },
                        accent: {
                            DEFAULT: 'hsl(var(--accent))',
                            foreground: 'hsl(var(--accent-foreground))'
                        },
                        card: {
                            DEFAULT: 'hsl(var(--card))',
                            foreground: 'hsl(var(--card-foreground))'
                        },
                        'mozo': {
                            900: '#10002b',
                            800: '#240046',
                            700: '#3c096c',
                            600: '#5a189a',
                            500: '#9f54fd',
                            400: '#7b2cbf',
                            300: '#9d4edd',
                            200: '#c77dff',
                            100: '#e0aaff',
                            50: '#f3e8ff'
                        },
                        'crypto': {
                            'blue': '#1A1F2C',
                            'purple': '#9f54fd',
                            'light-purple': '#e0aaff',
                            'dark-purple': '#5a189a',
                            'accent': '#F97316',
                        }
                    },
                    borderRadius: {
                        'xs': '0.125rem',
                        'sm': '0.25rem',
                        'md': '0.375rem',
                        'lg': '0.5rem',
                        'xl': '0.75rem',
                        '2xl': '1rem',
                        '3xl': '1.5rem',
                        'circle': '50%',
                        'pill': '9999px'
                    },
                    keyframes: {
                        'float': {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' }
                        },
                        'pulse-slow': {
                            '0%, 100%': { opacity: '1' },
                            '50%': { opacity: '0.8' }
                        },
                        'fade-in': {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        'fade-in-left': {
                            '0%': { opacity: '0', transform: 'translateX(-20px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' }
                        },
                        'fade-in-right': {
                            '0%': { opacity: '0', transform: 'translateX(20px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' }
                        },
                        'gradient-shift': {
                            '0%, 100%': { backgroundPosition: '0% 50%' },
                            '50%': { backgroundPosition: '100% 50%' }
                        }
                    },
                    animation: {
                        'float': 'float 3s ease-in-out infinite',
                        'pulse-slow': 'pulse-slow 3s ease-in-out infinite',
                        'fade-in': 'fade-in 0.7s ease-out',
                        'fade-in-left': 'fade-in-left 0.7s ease-out',
                        'fade-in-right': 'fade-in-right 0.7s ease-out',
                        'gradient-shift': 'gradient-shift 3s ease infinite'
                    },
                    backgroundImage: {
                        'gradient-hero': 'linear-gradient(135deg, #10002b 0%, #240046 25%, #3c096c 50%, #5a189a 75%, #9f54fd 100%)',
                        'text-gradient': 'linear-gradient(135deg, #9f54fd 0%, #e0aaff 100%)'
                    }
                }
            }
        }
    </script>

    <!-- Custom Styles -->
    <style>
        [x-cloak] { display: none !important; }

        :root {
            --border: 214.3 31.8% 91.4%;
            --input: 214.3 31.8% 91.4%;
            --ring: 221.2 83.2% 53.3%;
            --background: 0 0% 100%;
            --foreground: 222.2 84% 4.9%;
            --primary: 221.2 83.2% 53.3%;
            --primary-foreground: 210 40% 98%;
            --secondary: 210 40% 96%;
            --secondary-foreground: 222.2 84% 4.9%;
            --muted: 210 40% 96%;
            --muted-foreground: 215.4 16.3% 46.9%;
            --accent: 210 40% 96%;
            --accent-foreground: 222.2 84% 4.9%;
            --card: 0 0% 100%;
            --card-foreground: 222.2 84% 4.9%;
        }

        .dark {
            --border: 217.2 32.6% 17.5%;
            --input: 217.2 32.6% 17.5%;
            --ring: 224.3 76.3% 94.1%;
            --background: 222.2 84% 4.9%;
            --foreground: 210 40% 98%;
            --primary: 224.3 76.3% 94.1%;
            --primary-foreground: 220.9 39.3% 11%;
            --secondary: 217.2 32.6% 17.5%;
            --secondary-foreground: 210 40% 98%;
            --muted: 217.2 32.6% 17.5%;
            --muted-foreground: 215 20.2% 65.1%;
            --accent: 217.2 32.6% 17.5%;
            --accent-foreground: 210 40% 98%;
            --card: 222.2 84% 4.9%;
            --card-foreground: 210 40% 98%;
        }

        /* Smooth animations */
        .transition-all {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Text gradient effect */
        .text-gradient {
            background: linear-gradient(135deg, #9f54fd 0%, #e0aaff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            background-size: 200% 200%;
            animation: gradient-shift 3s ease infinite;
        }

        /* Hero background with glow effect */
        .hero-glow {
            position: relative;
        }

        .hero-glow::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(ellipse at center, rgba(159, 84, 253, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Glass morphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
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
            background: #1a1f2c;
        }

        ::-webkit-scrollbar-thumb {
            background: #9f54fd;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #7b2cbf;
        }

        /* Button styles */
        .btn-primary {
            background: linear-gradient(135deg, #9f54fd 0%, #7b2cbf 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #7b2cbf 0%, #5a189a 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(159, 84, 253, 0.4);
        }

        /* Scroll animation setup */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.7s ease-out;
        }

        .animate-on-scroll.animated {
            opacity: 1;
            transform: translateY(0);
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
                        <img src="{{ asset('images/logo.svg') }}" alt="MOZO QR" class="h-10 w-auto">
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
                        <a href="#plans" class="text-gray-700 hover:text-mozo-600 font-medium transition-colors">
                            Planes
                        </a>
                        <a href="#download" class="text-gray-700 hover:text-mozo-600 font-medium transition-colors">
                            Descarga
                        </a>
                        <a href="#contact" class="text-gray-700 hover:text-mozo-600 font-medium transition-colors">
                            Contacto
                        </a>
                    </nav>

                    <a href="/admin" class="bg-mozo-600 text-white px-4 py-2 rounded-lg hover:bg-mozo-700 font-medium transition-colors">
                        Iniciar Sesión
                    </a>

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
    <footer class="relative overflow-hidden">
        <!-- Background with gradient -->
        <div class="bg-gradient-to-br from-crypto-blue via-crypto-dark-blue to-gray-900 text-white py-20">
            <!-- Animated background elements -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute top-10 left-10 w-64 h-64 bg-crypto-purple/10 rounded-full filter blur-3xl animate-pulse-slow"></div>
                <div class="absolute bottom-10 right-10 w-80 h-80 bg-crypto-light-purple/10 rounded-full filter blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
            </div>

            <!-- Grid pattern overlay -->
            <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%239f54fd" fill-opacity="0.02"%3E%3Cpath d="M30 30h30v30H30zM0 0h30v30H0z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid md:grid-cols-4 gap-12">
                    <!-- Company Info -->
                    <div class="md:col-span-2">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-12 h-12 bg-gradient-to-br from-crypto-purple to-crypto-dark-purple rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <span class="text-3xl font-bold">MOZO QR</span>
                        </div>
                        <p class="text-gray-300 mb-8 max-w-md text-lg leading-relaxed">
                            Transforma tu restaurante con tecnología QR inteligente.
                            Gestión simple, clientes felices, mayor eficiencia.
                        </p>

                        <!-- Social Links -->
                        <div class="flex space-x-4">
                            <a href="#" class="w-10 h-10 bg-white/10 hover:bg-crypto-purple/20 rounded-lg flex items-center justify-center transition-all hover:scale-110">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                                </svg>
                            </a>
                            <a href="#" class="w-10 h-10 bg-white/10 hover:bg-crypto-purple/20 rounded-lg flex items-center justify-center transition-all hover:scale-110">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
                                </svg>
                            </a>
                            <a href="#" class="w-10 h-10 bg-white/10 hover:bg-crypto-purple/20 rounded-lg flex items-center justify-center transition-all hover:scale-110">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                            </a>
                            <a href="https://wa.me/5491234567890" class="w-10 h-10 bg-green-500/20 hover:bg-green-500 rounded-lg flex items-center justify-center transition-all hover:scale-110">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.302"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Product Links -->
                    <div>
                        <h6 class="font-bold text-xl mb-6 text-gradient bg-gradient-to-r from-crypto-purple to-crypto-light-purple bg-clip-text text-transparent">Producto</h6>
                        <ul class="space-y-4">
                            <li><a href="{{ route('public.plans.index') }}" class="text-gray-300 hover:text-crypto-purple transition-colors text-lg hover:translate-x-1 transform duration-200 inline-block">Planes y Precios</a></li>
                            <li><a href="#features" class="text-gray-300 hover:text-crypto-purple transition-colors text-lg hover:translate-x-1 transform duration-200 inline-block">Características</a></li>
                            <li><a href="#download" class="text-gray-300 hover:text-crypto-purple transition-colors text-lg hover:translate-x-1 transform duration-200 inline-block">Descargar App</a></li>
                        </ul>
                    </div>

                    <!-- Support Links -->
                    <div>
                        <h6 class="font-bold text-xl mb-6 text-gradient bg-gradient-to-r from-crypto-purple to-crypto-light-purple bg-clip-text text-transparent">Soporte</h6>
                        <ul class="space-y-4">
                            <li><a href="#contact" class="text-gray-300 hover:text-crypto-purple transition-colors text-lg hover:translate-x-1 transform duration-200 inline-block">Centro de Ayuda</a></li>
                            <li><a href="#contact" class="text-gray-300 hover:text-crypto-purple transition-colors text-lg hover:translate-x-1 transform duration-200 inline-block">Contactar Soporte</a></li>
                            <li><a href="#" class="text-gray-300 hover:text-crypto-purple transition-colors text-lg hover:translate-x-1 transform duration-200 inline-block">API Docs</a></li>
                            <li><a href="#" class="text-gray-300 hover:text-crypto-purple transition-colors text-lg hover:translate-x-1 transform duration-200 inline-block">Estado del Sistema</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Bottom Section -->
                <div class="border-t border-white/10 mt-16 pt-8 flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-300 text-lg">
                        &copy; {{ date('Y') }} MOZO QR. Todos los derechos reservados.
                    </p>
                    <div class="flex space-x-8 mt-4 md:mt-0">
                        <a href="#" class="text-gray-300 hover:text-crypto-purple transition-colors text-lg">Términos</a>
                        <a href="#" class="text-gray-300 hover:text-crypto-purple transition-colors text-lg">Privacidad</a>
                        <a href="#" class="text-gray-300 hover:text-crypto-purple transition-colors text-lg">Cookies</a>
                    </div>
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
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
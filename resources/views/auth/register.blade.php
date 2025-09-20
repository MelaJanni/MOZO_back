@extends('layouts.app')

@section('title', 'Registrarse - MOZO QR')
@section('description', 'Regístrate en MOZO QR y comienza a digitalizar tu restaurante con códigos QR inteligentes.')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-gray-100 to-gray-200 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <a href="/" class="inline-block">
                    <img src="{{ asset('images/mozo-logo.png') }}" alt="MOZO QR" class="h-12 w-auto mx-auto">
                </a>
                <h2 class="mt-6 text-3xl font-bold text-gray-900">
                    Crear Cuenta
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    ¿Ya tienes cuenta?
                    <a href="{{ route('login') }}" class="font-medium text-crypto-purple hover:text-crypto-dark-purple transition-colors">
                        Inicia sesión aquí
                    </a>
                </p>
            </div>

            <!-- Form -->
            <div class="bg-white/80 backdrop-blur-lg rounded-2xl shadow-2xl border border-white/50 p-8">
                <form class="space-y-6" action="{{ route('register') }}" method="POST">
                    @csrf

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre completo
                        </label>
                        <input type="text" id="name" name="name" required
                               class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-crypto-purple focus:border-crypto-purple transition-colors"
                               value="{{ old('name') }}" placeholder="Tu nombre completo">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Correo electrónico
                        </label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-crypto-purple focus:border-crypto-purple transition-colors"
                               value="{{ old('email') }}" placeholder="tu@email.com">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Contraseña
                        </label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-crypto-purple focus:border-crypto-purple transition-colors"
                               placeholder="Mínimo 8 caracteres">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                            Confirmar contraseña
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-crypto-purple focus:border-crypto-purple transition-colors"
                               placeholder="Confirma tu contraseña">
                    </div>

                    <!-- Terms -->
                    <div class="flex items-start">
                        <input type="checkbox" id="terms" name="terms" required
                               class="mt-1 h-4 w-4 text-crypto-purple focus:ring-crypto-purple border-gray-300 rounded">
                        <label for="terms" class="ml-2 text-sm text-gray-700">
                            Acepto los <a href="#" class="text-crypto-purple hover:text-crypto-dark-purple">términos y condiciones</a>
                            y la <a href="#" class="text-crypto-purple hover:text-crypto-dark-purple">política de privacidad</a>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            class="w-full bg-gradient-to-r from-crypto-purple to-crypto-light-purple text-white font-bold py-3 px-4 rounded-lg hover:from-crypto-dark-purple hover:to-crypto-purple transform hover:scale-105 transition-all duration-300 shadow-lg">
                        Crear Cuenta
                    </button>

                    <!-- Divider -->
                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">o regístrate con</span>
                        </div>
                    </div>

                    <!-- Google Register -->
                    <a href="{{ route('auth.google') }}"
                       class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition duration-300">
                        <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Continuar con Google
                    </a>
                </form>
            </div>

            <!-- Back to home -->
            <div class="text-center">
                <a href="/" class="text-sm text-gray-600 hover:text-crypto-purple transition-colors">
                    ← Volver al inicio
                </a>
            </div>
        </div>
    </div>
@endsection
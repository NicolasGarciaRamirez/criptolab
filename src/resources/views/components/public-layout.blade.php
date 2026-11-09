<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CriptoLab') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
        <header class="border-b border-gray-200 bg-white/90 backdrop-blur dark:border-gray-800 dark:bg-gray-900/90">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="text-lg font-bold text-indigo-600 dark:text-indigo-400">CriptoLab</a>

                <nav class="flex items-center gap-5 text-sm">
                    <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Inicio</a>
                    <a href="{{ route('theory') }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Teoría</a>

                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Panel</a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Iniciar sesión</a>
                        <a href="{{ route('register') }}" class="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Crear cuenta</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="border-t border-gray-200 bg-white py-8 dark:border-gray-800 dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-4 text-sm text-gray-500 dark:text-gray-400 sm:px-6 lg:px-8">
                <p>CriptoLab · Actividad académica de criptografía aplicada con Laravel y PostgreSQL.</p>
                <p class="mt-2 text-xs">Proyecto educativo. No usar para proteger credenciales reales. El login real usa Argon2id; la pantalla de autenticación cifrada es solo una demostración.</p>
            </div>
        </footer>
    </body>
</html>

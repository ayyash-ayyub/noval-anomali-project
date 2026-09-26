<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-mono text-matrix-green antialiased bg-black">
        <canvas id="matrix-rain" class="matrix-rain-canvas"></canvas>

        <div class="relative z-10 min-h-screen flex flex-col justify-center items-center px-4">
            <div class="mb-6 flex flex-col items-center text-center">
                <img
                    src="{{ asset('images/logo.jpeg') }}"
                    alt="Alcom"
                    class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl object-cover shadow-matrix-glow-lg mb-3"
                >
                <p class="text-xs tracking-[0.4em] text-matrix-green-dim uppercase">System Access</p>
                <h1 class="mt-1 text-2xl sm:text-3xl font-bold tracking-widest text-matrix-green drop-shadow-[0_0_8px_rgba(0,255,65,0.6)]">
                    ALCOM
                </h1>
                <p class="text-[11px] text-matrix-green-dark mt-1">WiFi Voucher Management System</p>
            </div>

            <div class="w-full sm:max-w-md bg-matrix-panel/90 border border-matrix-border shadow-matrix-glow-lg backdrop-blur-sm px-6 py-8 sm:rounded-md">
                {{ $slot }}
            </div>

            <p class="mt-6 text-[11px] text-matrix-green-deep">
                &copy; {{ date('Y') }} Alcom &mdash; authorized access only
            </p>
        </div>
    </body>
</html>

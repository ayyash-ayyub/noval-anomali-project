<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($header) ? $header . ' - ' : '' }}{{ config('app.name', 'Laravel') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-mono antialiased bg-black text-matrix-green" x-data="{ sidebarOpen: false }">
        <div class="min-h-screen flex">
            @include('layouts.sidebar')

            <div
                x-show="sidebarOpen"
                x-cloak
                @click="sidebarOpen = false"
                class="fixed inset-0 z-30 bg-black/70 lg:hidden"
            ></div>

            <div class="flex-1 flex flex-col min-w-0">
                @include('layouts.topbar', ['header' => $header ?? null])

                <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                    @if (session('status'))
                        <div class="mb-4 px-4 py-2.5 rounded border border-matrix-green/40 bg-matrix-green/10 text-matrix-green text-sm">
                            &gt; {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 px-4 py-2.5 rounded border border-red-500/40 bg-red-500/10 text-red-400 text-sm">
                            &gt; {{ session('error') }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>

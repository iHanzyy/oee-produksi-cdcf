<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>OEE Produksi</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <main class="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 py-8">
            <div class="h-20 w-56 overflow-hidden">
                <a href="/" aria-label="OEE Produksi">
                    <img src="{{ asset('brand/logo-oee.png') }}" alt="" class="w-56 -translate-y-7">
                </a>
            </div>

            <div class="mt-6 w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white px-6 py-6 shadow-lg shadow-slate-900/5 sm:px-8 sm:py-8">
                {{ $slot }}
            </div>
        </main>
    </body>
</html>

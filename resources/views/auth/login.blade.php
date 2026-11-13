<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Login | OEE Produksi</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-oee-ice font-sans text-slate-950 antialiased">
        <main class="grid min-h-[100dvh] lg:grid-cols-2">
            <section class="flex min-h-[100dvh] flex-col bg-oee-ice px-6 py-5 sm:px-12 sm:py-6 xl:px-24 2xl:px-32" aria-labelledby="login-title">
                <header class="mt-auto flex justify-center">
                    <div class="aspect-[3/1] w-full max-w-64 overflow-hidden sm:max-w-80">
                        <img src="{{ asset('brand/logo-oee.png') }}" alt="OEE, Overall Equipment Effectiveness" width="1672" height="941" class="w-full -translate-y-[15%]">
                    </div>
                </header>

                <div class="pt-4 sm:pt-6">
                    <div class="mx-auto w-full max-w-2xl">
                        <div class="mb-8">
                            <h1 id="login-title" class="text-4xl font-bold tracking-tight">Login</h1>
                            <p class="mt-1 text-xl font-medium sm:text-2xl">Selamat datang di OEE</p>
                        </div>

                        @if (session('status'))
                            <div class="mb-6 rounded-lg border border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900" role="status">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}" x-data="{ submitting: false }" @submit="submitting = true" class="space-y-5 sm:space-y-6">
                            @csrf

                            <div>
                                <label for="username" class="block text-lg font-bold sm:text-xl">Username</label>
                                <input
                                    id="username"
                                    type="text"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    @error('email') aria-invalid="true" aria-describedby="username-error" @enderror
                                    class="mt-2 block h-14 w-full rounded-lg border-oee-blue bg-white/95 px-5 text-base text-slate-950 shadow-none transition-colors placeholder:text-slate-500 hover:border-oee-navy focus:border-oee-navy focus:ring-oee-navy sm:h-16 sm:text-lg"
                                    placeholder="Masukkan Username"
                                >
                                @error('email')
                                    <p id="username-error" class="mt-2 text-sm font-semibold text-red-800" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password" class="block text-lg font-bold sm:text-xl">Password</label>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                                    class="mt-2 block h-14 w-full rounded-lg border-oee-blue bg-white/95 px-5 text-base text-slate-950 shadow-none transition-colors placeholder:text-slate-500 hover:border-oee-navy focus:border-oee-navy focus:ring-oee-navy sm:h-16 sm:text-lg"
                                    placeholder="Masukkan Password"
                                >
                                @error('password')
                                    <p id="password-error" class="mt-2 text-sm font-semibold text-red-800" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="pt-4 sm:pt-6">
                                <button type="submit" :disabled="submitting" class="flex h-14 w-full cursor-pointer items-center justify-center rounded-lg bg-oee-navy px-5 text-base font-bold text-white transition-colors hover:bg-oee-blue active:bg-oee-navy focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-oee-blue focus-visible:ring-offset-2 focus-visible:ring-offset-oee-ice disabled:cursor-not-allowed disabled:opacity-60 sm:h-16 sm:text-lg">
                                    <span x-show="!submitting">Login</span>
                                    <span x-cloak x-show="submitting" role="status" aria-live="polite">Sedang masuk...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <footer class="mt-auto pt-4 text-center text-sm font-medium sm:text-base">
                    Copyright &copy; 2026 | Created by GJT
                </footer>
            </section>

            <aside class="relative hidden min-h-[100dvh] overflow-hidden lg:block" aria-label="Mesin produksi OEE">
                <img src="{{ asset('brand/login-machine.jpg') }}" alt="Mesin produksi dengan jalur pengisian otomatis" width="2560" height="3200" fetchpriority="high" class="absolute inset-0 h-full w-full object-cover">
            </aside>
        </main>
    </body>
</html>

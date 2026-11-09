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
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased [font-family:Manrope,sans-serif]">
        <main class="flex min-h-screen items-center justify-center p-4 sm:p-8">
            <section class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-blue-950/10" aria-labelledby="login-title">
                <div class="h-1.5 bg-[#01c5f0]" aria-hidden="true"></div>

                <div class="px-6 py-8 sm:px-10 sm:py-10">
                    <header class="mb-8 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-[#005cb9] text-[10px] font-bold tracking-[0.18em] text-white">
                            LOGO
                        </div>
                        <p class="mt-4 text-sm font-semibold text-[#005cb9]">OEE Produksi</p>
                        <h1 id="login-title" class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Masuk</h1>
                    </header>

                    @if (session('status'))
                        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="username" class="block text-sm font-semibold text-slate-800">Username</label>
                            <input
                                id="username"
                                type="text"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                @error('email') aria-invalid="true" aria-describedby="username-error" @enderror
                                class="mt-2 block h-12 w-full rounded-lg border-slate-300 bg-white px-4 text-sm text-slate-950 shadow-sm transition placeholder:text-slate-400 hover:border-slate-400 focus:border-[#005cb9] focus:ring-[#005cb9]"
                                placeholder="Masukkan username"
                            >
                            @error('email')
                                <p id="username-error" class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-800">Password</label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                                class="mt-2 block h-12 w-full rounded-lg border-slate-300 bg-white px-4 text-sm text-slate-950 shadow-sm transition placeholder:text-slate-400 hover:border-slate-400 focus:border-[#005cb9] focus:ring-[#005cb9]"
                                placeholder="Masukkan password"
                            >
                            @error('password')
                                <p id="password-error" class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="flex h-12 w-full cursor-pointer items-center justify-center rounded-lg bg-[#005cb9] px-5 text-sm font-bold text-white transition hover:bg-[#004c99] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#005cb9] focus-visible:ring-offset-2 active:translate-y-px disabled:cursor-not-allowed disabled:opacity-60">
                            Masuk
                        </button>
                    </form>
                </div>
            </section>
        </main>
    </body>
</html>

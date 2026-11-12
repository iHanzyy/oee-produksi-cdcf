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
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
        <main class="flex min-h-screen items-center justify-center p-4 sm:p-8">
            <section class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg shadow-slate-900/5" aria-labelledby="login-title">
                <div class="px-6 py-8 sm:px-10 sm:py-10">
                    <header class="mb-8 text-center">
                        <div class="mx-auto h-20 w-56 overflow-hidden" aria-hidden="true">
                            <img src="{{ asset('brand/logo-oee.png') }}" alt="" width="1672" height="941" class="w-56 -translate-y-7">
                        </div>
                        <h1 id="login-title" class="mt-3 text-2xl font-bold tracking-tight text-slate-950">Masuk</h1>
                    </header>

                    @if (session('status'))
                        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" x-data="{ submitting: false }" @submit="submitting = true" class="space-y-5">
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
                                class="mt-2 block h-12 w-full rounded-xl border-slate-300 bg-white px-4 text-sm text-slate-950 shadow-sm transition placeholder:text-slate-400 hover:border-slate-400 focus:border-brand-700 focus:ring-brand-700"
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
                                class="mt-2 block h-12 w-full rounded-xl border-slate-300 bg-white px-4 text-sm text-slate-950 shadow-sm transition placeholder:text-slate-400 hover:border-slate-400 focus:border-brand-700 focus:ring-brand-700"
                                placeholder="Masukkan password"
                            >
                            @error('password')
                                <p id="password-error" class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" :disabled="submitting" class="flex h-12 w-full cursor-pointer items-center justify-center rounded-xl bg-brand-700 px-5 text-sm font-bold text-white transition-colors hover:bg-brand-800 active:bg-brand-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                            <span x-show="!submitting">Masuk</span>
                            <span x-cloak x-show="submitting" role="status" aria-live="polite">Sedang masuk...</span>
                        </button>
                    </form>
                </div>
            </section>
        </main>
    </body>
</html>

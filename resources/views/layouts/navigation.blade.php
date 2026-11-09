<nav x-data="{ open: false }" class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-4 sm:gap-8">
                <!-- Logo Aplikasi Pabrik -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ auth()->user()->role === 'operator' ? route('dashboard') : route('history.index') }}" class="block h-12 w-36 overflow-hidden" aria-label="OEE Produksi">
                        <img src="{{ asset('brand/logo-oee.png') }}" alt="" class="w-36 -translate-y-4">
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="flex space-x-4 sm:space-x-6">
                    @if (auth()->user()->role === 'operator')
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-sm sm:text-base font-semibold">
                            Kontrol Sesi
                        </x-nav-link>
                    @else
                        <x-nav-link :href="route('history.index')" :active="request()->routeIs('history.index')" class="text-sm sm:text-base font-semibold">
                            Riwayat & Arsip
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- User Info & Logout Button - Always visible & comfortable for tablet touch -->
            <div class="flex items-center gap-2 sm:gap-3">
                <span class="hidden items-center rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-700 sm:inline-flex">
                    <span class="mr-1.5 font-bold uppercase text-[#005cb9]">{{ auth()->user()->role ?? 'Operator' }}</span>
                    <span>| {{ Auth::user()->name }}</span>
                </span>
                
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="min-h-[42px] rounded-lg border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#005cb9] focus-visible:ring-offset-2">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

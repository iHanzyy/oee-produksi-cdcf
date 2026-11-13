<nav x-data="{ open: false }" class="sticky top-0 z-30 border-b {{ auth()->user()->role === 'operator' ? 'border-oee-blue bg-oee-blue shadow-md' : 'border-slate-200 bg-white/95 backdrop-blur' }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-4 sm:gap-8">
                <!-- Logo Aplikasi Pabrik -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ auth()->user()->role === 'operator' ? route('dashboard') : route('history.index') }}" class="block h-12 w-32 overflow-hidden sm:w-36" aria-label="OEE Produksi">
                        <img src="{{ asset('brand/logo-oee.png') }}" alt="" width="1672" height="941" class="w-32 -translate-y-3.5 sm:w-36 sm:-translate-y-4 {{ auth()->user()->role === 'operator' ? 'brightness-0 invert' : '' }}">
                    </a>
                </div>

                <!-- Navigation Links -->
                @if (auth()->user()->role !== 'operator')
                    <div class="flex space-x-4 sm:space-x-6">
                        <x-nav-link :href="route('history.index')" :active="request()->routeIs('history.*')" class="text-sm sm:text-base font-semibold">
                            Riwayat & Arsip
                        </x-nav-link>
                    </div>
                @endif
            </div>

            <!-- User Info & Logout Button - Always visible & comfortable for tablet touch -->
            <div class="flex items-center gap-2 sm:gap-3">
                <span class="hidden items-center rounded-lg {{ auth()->user()->role === 'operator' ? 'bg-white text-oee-navy' : 'bg-slate-100 text-slate-700' }} px-3 py-1.5 text-sm font-semibold sm:inline-flex">
                    <span class="mr-1.5 font-bold uppercase {{ auth()->user()->role === 'operator' ? 'text-oee-blue' : 'text-brand-700' }}">{{ auth()->user()->role ?? 'Operator' }}</span>
                    <span>| {{ Auth::user()->name }}</span>
                </span>
                
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex min-h-[40px] items-center justify-center gap-1.5 rounded-xl bg-red-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition-colors hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2 {{ auth()->user()->role === 'operator' ? 'focus-visible:ring-offset-oee-blue' : '' }}">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 15l4-5-4-5M17 10H7M10 17H5a2 2 0 01-2-2V5a2 2 0 012-2h5" />
                        </svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

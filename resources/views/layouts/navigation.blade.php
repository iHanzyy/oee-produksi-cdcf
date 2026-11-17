<nav class="sticky top-0 z-30 border-b border-secondary bg-secondary shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-16 flex-wrap items-center justify-between gap-x-3 gap-y-1 py-2 sm:h-16 sm:flex-nowrap sm:py-0">
            <div class="contents sm:flex sm:items-center sm:gap-8">
                <!-- Logo Aplikasi Pabrik -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ auth()->user()->role === 'operator' ? route('dashboard') : route('history.index') }}" class="block h-12 w-32 overflow-hidden sm:w-36" aria-label="OEE Produksi">
                        <img src="{{ asset('brand/logo-oee.png') }}" alt="" width="1672" height="941" class="w-32 -translate-y-3.5 brightness-0 invert sm:w-36 sm:-translate-y-4">
                    </a>
                </div>

                <!-- Navigation Links -->
                @if (auth()->user()->role !== 'operator')
                    <div class="order-3 flex w-full gap-5 sm:order-none sm:w-auto sm:gap-6">
                        <x-nav-link :href="route('history.index')" :active="request()->routeIs('history.*')" class="text-sm sm:text-base font-semibold">
                            Riwayat Produksi
                        </x-nav-link>
                        <x-nav-link :href="route('oee.index')" :active="request()->routeIs('oee.*')" class="text-sm sm:text-base font-semibold">
                            Analisis OEE
                        </x-nav-link>
                    </div>
                @endif
            </div>

            <!-- User Info & Logout Button - Always visible & comfortable for tablet touch -->
            <div class="ml-auto flex items-center gap-2 sm:gap-3">
                <span class="hidden items-center rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-primary sm:inline-flex">
                    <span class="mr-1.5 font-bold uppercase text-secondary">{{ auth()->user()->role ?? 'Operator' }}</span>
                    <span>| {{ Auth::user()->name }}</span>
                </span>
                
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <x-action-button type="submit" variant="danger" class="min-h-[40px] px-3.5 py-2 text-xs focus-visible:ring-offset-secondary">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 15l4-5-4-5M17 10H7M10 17H5a2 2 0 01-2-2V5a2 2 0 012-2h5" />
                        </svg>
                        <span>Keluar</span>
                    </x-action-button>
                </form>
            </div>
        </div>
    </div>
</nav>

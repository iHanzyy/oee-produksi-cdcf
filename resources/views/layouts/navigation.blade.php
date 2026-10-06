<nav x-data="{ open: false }" class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 sm:h-18">
            <div class="flex items-center space-x-4 sm:space-x-8">
                <!-- Logo Aplikasi Pabrik -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ auth()->user()->role === 'operator' ? route('dashboard') : route('history.index') }}" class="font-extrabold text-lg sm:text-xl text-indigo-700 tracking-wider flex items-center gap-2">
                        <span class="inline-block w-3 h-3 rounded-full bg-indigo-600"></span>
                        KONTROL PRODUKSI
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
            <div class="flex items-center space-x-2 sm:space-x-4">
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                    <span class="font-bold text-indigo-700 mr-1.5 uppercase">{{ auth()->user()->role ?? 'Operator' }}</span>
                    <span class="text-slate-600 hidden xs:inline">| {{ Auth::user()->name }}</span>
                </span>
                
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="min-h-[42px] px-3.5 py-1.5 rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 font-bold text-xs uppercase tracking-wider transition-colors">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

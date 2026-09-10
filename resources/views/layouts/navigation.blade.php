<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo Aplikasi Pabrik -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="font-bold text-xl text-indigo-600 tracking-wider">
                        OEE & MTTR SYSTEM
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Dashboard OEE
                    </x-nav-link>
                    <x-nav-link :href="route('history.index')" :active="request()->routeIs('history.index')">
                        Riwayat & Arsip
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown / User Info -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <div class="ml-3 relative">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                        {{ ucfirst(auth()->user()->role ?? 'Operator') }} : {{ Auth::user()->name }}
                    </span>
                    
                    <!-- Tombol Logout bawaan Breeze -->
                    <form method="POST" action="{{ route('logout') }}" class="inline ml-3">
                        @csrf
                        <button type="submit" class="text-xs text-red-600 hover:text-red-900 font-bold uppercase">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>
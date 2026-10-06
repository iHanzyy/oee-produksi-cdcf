<x-app-layout>
    <div
        x-data="productionControl({
            initial: @js($dashboardState),
            areas: @js($areaOptions),
            categories: @js($categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->category_name])->values()),
            personnels: @js($personnels->map(fn ($personnel) => ['id' => $personnel->id, 'name' => $personnel->name])->values()),
            urls: {
                start: @js(route('machine.start')),
                status: @js(route('production.status')),
                base: @js(url('/production-sessions')),
            },
        })"
        class="mx-auto max-w-5xl px-3 py-4 sm:px-6 sm:py-6 lg:px-8"
    >
        <!-- Header Layar Tablet -->
        <header class="mb-5 flex flex-col gap-1 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-500">Stasiun Produksi Pabrik</p>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">Kontrol Sesi Mesin</h1>
            </div>
            <div class="text-xs sm:text-sm font-medium text-slate-500">
                Satu layar kontrol untuk operator & tablet mesin
            </div>
        </header>



        <!-- Pesan Sukses / Stabilisasi Stasiun -->
        <div x-cloak x-show="successBanner" role="status" class="mb-5 rounded-xl border-l-4 border-emerald-600 bg-emerald-50 p-4 text-sm sm:text-base font-bold text-emerald-900 shadow-sm flex items-center justify-between transition-all">
            <div class="flex items-center gap-2">
                <span class="text-lg">✓</span>
                <span x-text="successBanner"></span>
            </div>
            <button type="button" @click="successBanner = ''" class="text-emerald-700 hover:text-emerald-900 font-bold text-sm px-2 py-1">Tutup</button>
        </div>

        <!-- 1. STATUS READY (Mulai Produksi Baru) -->
        <section x-show="state === 'ready'" aria-labelledby="start-title" class="space-y-6">
            <!-- Pemantauan Mesin Sedang Berjalan di Seluruh Pabrik -->
            <div class="rounded-2xl border-2 border-indigo-200 bg-indigo-50/50 p-5 sm:p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-indigo-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-white font-black text-sm">🏭</span>
                        <div>
                            <h2 class="text-lg font-bold text-indigo-950">Monitoring Mesin Sedang Berjalan di Pabrik</h2>
                            <p class="text-xs sm:text-sm text-indigo-800">Daftar seluruh mesin yang sedang aktif beroperasi saat ini.</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center self-start sm:self-auto px-3 py-1 rounded-full text-xs font-bold"
                        :class="availableSessions.length > 0 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-200 text-slate-700'">
                        <span class="inline-block h-2 w-2 rounded-full mr-1.5" :class="availableSessions.length > 0 ? 'bg-emerald-600 animate-ping' : 'bg-slate-500'"></span>
                        <span x-text="availableSessions.length > 0 ? availableSessions.length + ' Mesin Aktif' : '0 Mesin Aktif'"></span>
                    </span>
                </div>

                <!-- Jika tidak ada mesin berjalan -->
                <div x-show="availableSessions.length === 0" class="py-5 text-center text-slate-500 text-sm font-medium">
                    Belum ada mesin yang sedang berjalan di pabrik saat ini. Silakan mulai sesi produksi baru di bawah.
                </div>

                <!-- Daftar mesin berjalan -->
                <ul x-show="availableSessions.length > 0" class="mt-4 space-y-3" role="list">
                    <template x-for="item in availableSessions" :key="item.id">
                        <li class="rounded-xl border bg-white p-4 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 transition-all"
                            :class="item.status === 'running' ? 'border-emerald-300 hover:border-emerald-400' : 'border-red-300 hover:border-red-400'">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                        :class="item.status === 'running' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'">
                                        <span class="inline-block h-1.5 w-1.5 rounded-full mr-1" :class="item.status === 'running' ? 'bg-emerald-600' : 'bg-red-600'"></span>
                                        <span x-text="item.status === 'running' ? 'BERJALAN' : 'DOWNTIME'"></span>
                                    </span>
                                    <span class="text-xs font-semibold text-slate-500" x-text="item.area.name"></span>
                                </div>
                                <h3 class="mt-1 text-lg font-bold text-slate-900" x-text="item.machine.name"></h3>
                                <p class="text-xs sm:text-sm text-slate-600 mt-0.5">
                                    Operator: <strong class="text-slate-800" x-text="item.operatorName"></strong> |
                                    Produk: <strong class="text-slate-800" x-text="item.product.name"></strong> |
                                    Mulai: <span class="font-mono font-medium" x-text="item.startTime ? new Date(item.startTime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-'"></span>
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="takeover(item.id)"
                                :disabled="busy || cooldown"
                                class="min-h-[46px] shrink-0 rounded-xl bg-indigo-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-700 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                            >
                                Lanjutkan Sesi di Tablet Ini
                            </button>
                        </li>
                    </template>
                </ul>
            </div>

            <!-- Form Mulai Sesi Produksi -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="border-b border-slate-100 pb-4">
                    <h2 id="start-title" class="text-xl sm:text-2xl font-black text-slate-900">Mulai Sesi Produksi</h2>
                    <p class="mt-1 text-sm text-slate-600">Pilih area operasional, lalu tentukan operator, produk, dan mesin yang akan dijalankan.</p>
                </div>

                <form class="mt-6 grid gap-5 sm:grid-cols-2" @submit.prevent="start">
                    <!-- Area Operasional -->
                    <div>
                        <label for="area_id" class="block text-sm font-bold text-slate-800">Area Operasional <span class="text-red-600">*</span></label>
                        <select id="area_id" x-model="areaId" @change="changeArea" required class="mt-2 block min-h-[50px] w-full rounded-xl border-slate-300 text-base font-semibold text-slate-900 focus:border-indigo-600 focus:ring-indigo-600">
                            <option value="">-- Pilih Area Operasional --</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->area_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Nama Operator -->
                    <div>
                        <label for="operator_name" class="block text-sm font-bold text-slate-800">Nama Operator <span class="text-red-600">*</span></label>
                        <input id="operator_name" x-model.trim="operatorName" type="text" maxlength="100" autocomplete="name" required class="mt-2 block min-h-[50px] w-full rounded-xl border-slate-300 text-base font-medium text-slate-900 focus:border-indigo-600 focus:ring-indigo-600" placeholder="Ketik nama petugas aktual">
                    </div>

                    <!-- Nama Produk (Ketik & Dropdown) -->
                    <div class="relative" @click.outside="productMenuOpen = false">
                        <label for="product_name" class="block text-sm font-bold text-slate-800">
                            Nama Produk <span class="text-red-600">*</span>
                            <span class="text-xs font-normal text-slate-500">(Bisa diketik atau pilih dari list)</span>
                        </label>
                        <div class="mt-2 relative">
                            <input
                                id="product_name"
                                x-model="productName"
                                @input="matchProduct(); productMenuOpen = true"
                                @click="if (areaId) productMenuOpen = true"
                                @focus="if (areaId) productMenuOpen = true"
                                type="text"
                                autocomplete="off"
                                required
                                :disabled="!areaId"
                                class="block min-h-[50px] w-full rounded-xl border-slate-300 pr-10 text-base font-medium text-slate-900 focus:border-indigo-600 focus:ring-indigo-600 disabled:bg-slate-100 disabled:cursor-not-allowed cursor-pointer"
                                :placeholder="areaId ? 'Ketik atau klik untuk pilih produk...' : 'Pilih Area terlebih dahulu'"
                            >
                            <button
                                type="button"
                                @click.stop="if (areaId) productMenuOpen = !productMenuOpen"
                                :disabled="!areaId"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 disabled:cursor-not-allowed"
                                tabindex="-1"
                                aria-label="Buka daftar produk"
                            >
                                <svg class="h-5 w-5 transition-transform duration-200" :class="productMenuOpen ? 'rotate-180 text-indigo-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                        </div>

                        <!-- Dropdown List Produk Touch-Friendly untuk Tablet -->
                        <div
                            x-show="productMenuOpen && areaId"
                            x-cloak
                            class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-2xl"
                        >
                            <template x-for="prod in filteredProducts" :key="prod.id">
                                <button
                                    type="button"
                                    @click="selectProduct(prod)"
                                    class="w-full rounded-lg px-4 py-3 text-left text-base font-semibold text-slate-800 hover:bg-indigo-50 hover:text-indigo-900 flex items-center justify-between border-b border-slate-100 last:border-0"
                                >
                                    <span x-text="prod.name"></span>
                                    <span x-show="productId === prod.id" class="text-indigo-600 font-bold">✓</span>
                                </button>
                            </template>
                            <div x-show="filteredProducts.length === 0" class="px-4 py-3 text-sm text-slate-500 text-center">
                                Produk tidak ditemukan dalam area ini.
                            </div>
                        </div>

                        <!-- Datalist untuk kompatibilitas browser dan test suite -->
                        <datalist id="product-options" class="hidden">
                            @foreach ($areas as $areaItem)
                                @foreach ($areaItem->activeProducts as $prodItem)
                                    <option value="{{ $prodItem->product_name }}">{{ $areaItem->area_name }}</option>
                                @endforeach
                            @endforeach
                        </datalist>
                    </div>

                    <!-- Mesin (Ketik & Dropdown) -->
                    <div class="relative" @click.outside="machineMenuOpen = false">
                        <label for="machine_name" class="block text-sm font-bold text-slate-800">
                            Mesin <span class="text-red-600">*</span>
                            <span class="text-xs font-normal text-slate-500">(Bisa diketik atau pilih dari list)</span>
                        </label>
                        <div class="mt-2 relative">
                            <input
                                id="machine_name"
                                x-model="machineName"
                                @input="matchMachine(); machineMenuOpen = true"
                                @click="if (areaId) machineMenuOpen = true"
                                @focus="if (areaId) machineMenuOpen = true"
                                type="text"
                                autocomplete="off"
                                required
                                :disabled="!areaId"
                                class="block min-h-[50px] w-full rounded-xl border-slate-300 pr-10 text-base font-medium text-slate-900 focus:border-indigo-600 focus:ring-indigo-600 disabled:bg-slate-100 disabled:cursor-not-allowed cursor-pointer"
                                :placeholder="areaId ? 'Ketik atau klik untuk pilih mesin...' : 'Pilih Area terlebih dahulu'"
                            >
                            <button
                                type="button"
                                @click.stop="if (areaId) machineMenuOpen = !machineMenuOpen"
                                :disabled="!areaId"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 disabled:cursor-not-allowed"
                                tabindex="-1"
                                aria-label="Buka daftar mesin"
                            >
                                <svg class="h-5 w-5 transition-transform duration-200" :class="machineMenuOpen ? 'rotate-180 text-indigo-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                        </div>

                        <!-- Dropdown List Mesin Touch-Friendly untuk Tablet -->
                        <div
                            x-show="machineMenuOpen && areaId"
                            x-cloak
                            class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-2xl"
                        >
                            <template x-for="mch in filteredMachines" :key="mch.id">
                                <button
                                    type="button"
                                    @click="selectMachine(mch)"
                                    class="w-full rounded-lg px-4 py-3 text-left text-base font-semibold text-slate-800 hover:bg-indigo-50 hover:text-indigo-900 flex items-center justify-between border-b border-slate-100 last:border-0"
                                >
                                    <span x-text="mch.name"></span>
                                    <span x-show="machineId === mch.id" class="text-indigo-600 font-bold">✓</span>
                                </button>
                            </template>
                            <div x-show="filteredMachines.length === 0" class="px-4 py-3 text-sm text-slate-500 text-center">
                                Mesin tidak ditemukan dalam area ini.
                            </div>
                        </div>

                        <!-- Datalist untuk kompatibilitas browser dan test suite -->
                        <datalist id="machine-options" class="hidden">
                            @foreach ($areas as $areaItem)
                                @foreach ($areaItem->activeMachines as $mchItem)
                                    <option value="{{ $mchItem->machine_name }}">{{ $areaItem->area_name }}</option>
                                @endforeach
                            @endforeach
                        </datalist>
                    </div>

                    <!-- Tombol Start Produksi -->
                    <div class="sm:col-span-2 pt-2">
                        <button type="submit" :disabled="busy" class="min-h-[56px] w-full rounded-xl bg-indigo-700 px-6 py-3.5 text-base sm:text-lg font-bold text-white shadow-md hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-700 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60 transition-all flex items-center justify-center gap-2">
                            <span x-show="!busy">▶ Start Produksi</span>
                            <span x-show="busy">Memulai Sesi...</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- 2. STATUS RUNNING / DOWNTIME / COMPLETED -->
        <section x-show="session && state !== 'ready'" aria-labelledby="session-title" class="space-y-6">
            <!-- Peringatan jika kontrol ada di tablet lain -->
            <div x-show="!isController" role="alert" class="rounded-2xl border-2 border-amber-400 bg-amber-50 p-5 text-amber-950 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="font-bold text-base sm:text-lg">Kontrol sesi ini sedang aktif di tablet lain.</p>
                    <p class="text-sm text-amber-800 mt-0.5">Tekan tombol di samping jika Anda ingin mengendalikan mesin ini dari tablet sekarang.</p>
                </div>
                <button type="button" @click="takeover(session.id)" :disabled="busy" class="min-h-[50px] shrink-0 rounded-xl bg-amber-700 px-6 py-3 font-bold text-white hover:bg-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-700 focus:ring-offset-2">
                    Lanjutkan Sesi
                </button>
            </div>

            <!-- Kartu Kendali Mesin Aktif -->
            <div class="rounded-2xl border bg-white p-5 shadow-sm sm:p-7 transition-all"
                :class="{
                    'border-emerald-300 ring-2 ring-emerald-500/10': state === 'running',
                    'border-red-400 ring-2 ring-red-500/15': state === 'downtime',
                    'border-slate-300': state === 'completed'
                }"
            >
                <!-- Bagian Atas: Status & Nama Mesin & Timer -->
                <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <!-- Pill Status Mesin -->
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs sm:text-sm font-extrabold uppercase tracking-wider"
                            :class="{
                                'bg-emerald-100 text-emerald-800 border border-emerald-300': state === 'running',
                                'bg-red-100 text-red-800 border border-red-300': state === 'downtime',
                                'bg-slate-100 text-slate-800 border border-slate-300': state === 'completed'
                            }"
                        >
                            <span class="inline-block h-2.5 w-2.5 rounded-full"
                                :class="{
                                    'bg-emerald-600 animate-ping': state === 'running',
                                    'bg-red-600 animate-pulse': state === 'downtime',
                                    'bg-slate-500': state === 'completed'
                                }"
                            ></span>
                            <span x-text="state === 'downtime' ? '🛑 MESIN BERHENTI (DOWNTIME)' : state === 'completed' ? '✓ PRODUKSI SELESAI' : '● MESIN BERJALAN'"></span>
                        </div>

                        <!-- Nama Mesin Besar -->
                        <h2 id="session-title" class="mt-2 text-2xl sm:text-4xl font-black text-slate-900 tracking-tight" x-text="session?.machine.name"></h2>
                    </div>

                    <!-- Panel Timer untuk Tablet -->
                    <div class="flex flex-col sm:items-end gap-2">
                        <!-- Tampilan Timer Running -->
                        <div x-show="state === 'running'" class="rounded-xl border border-emerald-200 bg-emerald-50/70 px-5 py-3 text-center sm:text-right min-w-[200px]">
                            <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Total Waktu Mesin Berjalan</p>
                            <p class="mt-0.5 font-mono text-3xl sm:text-4xl font-extrabold text-slate-900 tabular-nums" x-text="elapsed" aria-live="off"></p>
                        </div>

                        <!-- Tampilan Dual Timer Saat Downtime -->
                        <div x-show="state === 'downtime'" class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                            <!-- Hero Timer: Durasi Downtime Berjalan -->
                            <div class="rounded-xl border-2 border-red-400 bg-red-100/80 px-5 py-3 text-center sm:text-right min-w-[200px] shadow-sm">
                                <p class="text-xs font-black uppercase tracking-wider text-red-800 flex items-center justify-center sm:justify-end gap-1.5">
                                    <span class="inline-block h-2 w-2 rounded-full bg-red-600 animate-ping"></span>
                                    Total Waktu Mesin Berhenti
                                </p>
                                <p class="mt-0.5 font-mono text-3xl sm:text-4xl font-black text-red-800 tabular-nums animate-pulse" x-text="downtimeElapsed" aria-live="off"></p>
                            </div>
                            <!-- Sekunder Timer: Total Sesi -->
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-center sm:text-right min-w-[150px]">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Waktu Mesin Berjalan</p>
                                <p class="mt-0.5 font-mono text-2xl font-bold text-slate-700 tabular-nums" x-text="elapsed" aria-live="off"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Glanceable Crosscheck Data Grid (Untuk dicek Operator di Tablet) -->
                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-200">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Area</p>
                        <p class="mt-1 text-base font-bold text-slate-900" x-text="session?.area.name"></p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-200">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Nama Operator</p>
                        <p class="mt-1 text-base font-bold text-slate-900" x-text="session?.operatorName"></p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-200">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Nama Produk</p>
                        <p class="mt-1 text-base font-bold text-slate-900" x-text="session?.product.name"></p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-200">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Waktu Mulai</p>
                        <p class="mt-1 text-base font-bold text-slate-900" x-text="session?.startTime ? new Date(session.startTime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) : '-'"></p>
                    </div>
                </div>

                <!-- Tombol Kendali Saat Mesin Berjalan -->
                <div x-show="state === 'running' && isController" class="mt-6 flex flex-col sm:flex-row gap-4" :class="{ 'pointer-events-none opacity-50': cooldown || busy }">
                    <button type="button" @click="stop" :disabled="busy || cooldown" class="min-h-[58px] flex-1 rounded-xl bg-red-700 px-6 py-3.5 text-base sm:text-lg font-bold tracking-wider text-white shadow-md hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2 disabled:opacity-50 transition-colors flex items-center justify-center gap-2">
                        <span>🛑 STOP MESIN</span>
                    </button>
                    <button type="button" @click="askFinish" :disabled="busy || cooldown" class="min-h-[58px] sm:w-56 rounded-xl border-2 border-slate-300 bg-white px-6 py-3.5 text-base font-bold text-slate-700 shadow-sm hover:bg-slate-100 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 disabled:opacity-50 transition-colors flex items-center justify-center gap-2">
                        <span>Selesai Produksi</span>
                    </button>
                </div>

                <!-- Tampilan & Form Saat Mesin Downtime -->
                <div x-show="state === 'downtime'" class="mt-6 space-y-4">
                    <!-- Banner Pengingat Downtime & Tombol Selesai Downtime -->
                    <div x-show="!showDowntimeForm" class="rounded-xl border-2 border-red-300 bg-red-50 p-5 text-center sm:text-left flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <p class="text-base sm:text-lg font-black text-red-900">Mesin Sedang Dalam Perbaikan (Downtime)</p>
                            <p class="mt-1 text-sm text-red-700">Jika perbaikan mesin telah selesai, tekan tombol di samping untuk mengisi data kendala dan menyalakan mesin.</p>
                        </div>
                        <button
                            x-show="isController"
                            type="button"
                            @click="openDowntimeForm"
                            class="min-h-[52px] shrink-0 rounded-xl bg-red-700 px-7 py-3 text-base font-bold text-white shadow-md hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2 transition-all"
                        >
                            🔧 Selesai Downtime
                        </button>
                    </div>

                    <!-- Form Kendala & Perbaikan -->
                    <div x-show="isController && showDowntimeForm" class="rounded-2xl border-2 border-red-400 bg-white p-5 sm:p-7 shadow-lg">
                        <div class="border-b border-red-100 pb-4">
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-700 font-bold">📝</span>
                                <h3 class="text-lg sm:text-xl font-bold text-slate-900">Form Pencatatan Kendala & Perbaikan</h3>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">Lengkapi data berikut sebelum menyalakan mesin kembali.</p>
                        </div>

                        <form @submit.prevent="resume" class="mt-5 grid gap-4 sm:grid-cols-2">
                            <!-- Kategori Kendala (Render Blade Native agar 100% stabil) -->
                            <div>
                                <label for="category_id" class="block text-sm font-bold text-slate-800">
                                    Kategori Kendala <span class="text-red-600">*</span>
                                </label>
                                <select id="category_id" x-model="categoryId" required class="mt-2 block min-h-[50px] w-full rounded-xl border-slate-300 text-base font-medium text-slate-900 focus:border-red-600 focus:ring-red-600">
                                    <option value="">-- Pilih Kategori Kendala --</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Pelaksana Perbaikan (Render Blade Native agar 100% stabil) -->
                            <div>
                                <label for="personnel_id" class="block text-sm font-bold text-slate-800">
                                    Pelaksana Perbaikan <span class="text-red-600">*</span>
                                </label>
                                <select id="personnel_id" x-model="personnelId" required class="mt-2 block min-h-[50px] w-full rounded-xl border-slate-300 text-base font-medium text-slate-900 focus:border-red-600 focus:ring-red-600">
                                    <option value="">-- Pilih Pelaksana Perbaikan --</option>
                                    @foreach ($personnels as $personnel)
                                        <option value="{{ $personnel->id }}">{{ $personnel->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Komponen Diganti -->
                            <div class="sm:col-span-2">
                                <label for="replaced_component" class="block text-sm font-bold text-slate-800">
                                    Komponen Diganti <span class="text-xs font-normal text-slate-500">(Opsional, kosongkan bila tidak ada penggantian)</span>
                                </label>
                                <input id="replaced_component" x-model.trim="replacedComponent" type="text" maxlength="255" class="mt-2 block min-h-[50px] w-full rounded-xl border-slate-300 text-base text-slate-900 focus:border-red-600 focus:ring-red-600" placeholder="Contoh: Sensor switch, seal bearing, nozzle, dsb.">
                            </div>

                            <!-- Tindakan Perbaikan -->
                            <div class="sm:col-span-2">
                                <label for="repair_action" class="block text-sm font-bold text-slate-800">
                                    Tindakan Perbaikan <span class="text-red-600">*</span>
                                </label>
                                <textarea id="repair_action" x-model.trim="repairAction" required maxlength="1000" rows="3" class="mt-2 block w-full rounded-xl border-slate-300 text-base text-slate-900 focus:border-red-600 focus:ring-red-600" placeholder="Jelaskan tindakan perbaikan yang telah dilakukan secara singkat dan jelas..."></textarea>
                            </div>

                            <!-- Pesan Error Langsung di Form (Mudah dilihat di tablet tanpa scroll) -->
                            <div x-show="formError" x-cloak class="sm:col-span-2 rounded-xl border-l-4 border-red-600 bg-red-50 p-4 text-sm font-bold text-red-900">
                                <span x-text="formError"></span>
                            </div>

                            <!-- Tombol Eksekusi Mulai Mesin -->
                            <div class="sm:col-span-2 flex flex-col-reverse sm:flex-row justify-end gap-3 pt-2">
                                <button type="button" @click="closeDowntimeForm" :disabled="busy" class="min-h-[50px] rounded-xl border border-slate-300 bg-white px-5 py-3 text-base font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                                    Batal / Tutup
                                </button>
                                <button type="submit" :disabled="busy" class="min-h-[54px] rounded-xl bg-emerald-700 px-8 py-3.5 text-base sm:text-lg font-bold text-white shadow-md hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2 disabled:opacity-60 transition-all flex items-center justify-center gap-2">
                                    <span x-show="!busy">▶ Mulai Mesin</span>
                                    <span x-show="busy">Memulai Mesin...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 3. STATUS COMPLETED (Ringkasan Akhir) -->
                <div x-show="state === 'completed'" role="status" class="mt-6 rounded-2xl border-2 border-emerald-600 bg-emerald-50/80 p-5 sm:p-7 text-emerald-950">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white font-black text-xl">✓</span>
                        <div>
                            <h3 class="text-xl sm:text-2xl font-black text-emerald-950">Sesi Produksi Telah Selesai</h3>
                            <p class="text-xs sm:text-sm text-emerald-800">Berikut rekapitulasi sesi produksi sebelum kembali ke stasiun awal.</p>
                        </div>
                    </div>

                    <dl class="mt-5 grid gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-xl bg-white p-4 shadow-sm border border-emerald-200">
                            <dt class="text-xs font-bold uppercase text-emerald-800">Waktu Selesai</dt>
                            <dd class="mt-1 text-base font-bold text-slate-900" x-text="session?.endTime ? new Date(session.endTime).toLocaleString('id-ID') : '-'"></dd>
                        </div>
                        <div class="rounded-xl bg-white p-4 shadow-sm border border-emerald-200">
                            <dt class="text-xs font-bold uppercase text-emerald-800">Total Waktu Mesin Berjalan</dt>
                            <dd class="mt-1 text-base font-bold text-slate-900" x-text="duration(session?.summary.totalDurationSeconds)"></dd>
                        </div>
                        <div class="rounded-xl bg-white p-4 shadow-sm border border-emerald-200">
                            <dt class="text-xs font-bold uppercase text-emerald-800">Ada Kendala Downtime</dt>
                            <dd class="mt-1 text-base font-bold" :class="session?.summary.hadDowntime ? 'text-amber-700' : 'text-emerald-700'" x-text="session?.summary.hadDowntime ? 'Ya' : 'Tidak'"></dd>
                        </div>
                        <div class="rounded-xl bg-white p-4 shadow-sm border border-emerald-200">
                            <dt class="text-xs font-bold uppercase text-emerald-800">Jumlah Downtime</dt>
                            <dd class="mt-1 text-base font-bold text-slate-900" x-text="session?.summary.downtimeCount + ' kali'"></dd>
                        </div>
                        <div class="rounded-xl bg-white p-4 shadow-sm border border-emerald-200 sm:col-span-2 lg:col-span-2">
                            <dt class="text-xs font-bold uppercase text-emerald-800">Total Downtime</dt>
                            <dd class="mt-1 text-base font-bold text-red-700" x-text="duration(session?.summary.totalDowntimeSeconds)"></dd>
                        </div>
                    </dl>

                    <button x-show="isController" type="button" @click="acknowledge" :disabled="busy" class="mt-6 min-h-[56px] w-full rounded-xl bg-emerald-800 px-6 py-3.5 text-lg font-bold text-white shadow-md hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-800 focus:ring-offset-2 disabled:opacity-60 transition-colors">
                        OKE
                    </button>
                </div>
            </div>

            <!-- Monitoring Mesin Pabrik Lainnya (Untuk Operator saat mengontrol mesin) -->
            <div x-cloak class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between cursor-pointer select-none" @click="showOtherSessions = !showOtherSessions">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 font-black text-sm">🏭</span>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <span>Monitoring Mesin Pabrik Lainnya</span>
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-800" x-text="otherSessions.length + ' Aktif'"></span>
                            </h3>
                            <p class="text-xs text-slate-500">Pantau mesin-mesin lain yang sedang beroperasi di pabrik secara live.</p>
                        </div>
                    </div>
                    <button type="button" class="text-xs font-bold text-indigo-700 hover:text-indigo-900 px-3 py-1.5 rounded-lg bg-indigo-50 border border-indigo-200 flex items-center gap-1.5 transition-colors">
                        <span x-text="showOtherSessions ? 'Tutup Pantauan' : 'Lihat Mesin Lain'"></span>
                        <svg class="h-4 w-4 transition-transform duration-200" :class="showOtherSessions ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                </div>

                <div x-show="showOtherSessions" x-cloak class="mt-4 border-t border-slate-100 pt-4">
                    <div x-show="otherSessions.length === 0" class="py-4 text-center text-slate-500 text-xs sm:text-sm">
                        Tidak ada mesin lain yang sedang beroperasi saat ini.
                    </div>
                    <div x-show="otherSessions.length > 0" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <template x-for="item in otherSessions" :key="item.id">
                            <div class="rounded-xl border p-3.5 shadow-xs"
                                :class="item.status === 'running' ? 'border-emerald-200 bg-emerald-50/40' : 'border-red-200 bg-red-50/40'">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase text-slate-500" x-text="item.area.name"></span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
                                        :class="item.status === 'running' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'">
                                        <span class="h-1.5 w-1.5 rounded-full mr-1" :class="item.status === 'running' ? 'bg-emerald-600' : 'bg-red-600'"></span>
                                        <span x-text="item.status === 'running' ? 'RUNNING' : 'DOWNTIME'"></span>
                                    </span>
                                </div>
                                <h4 class="mt-1 text-base font-bold text-slate-900" x-text="item.machine.name"></h4>
                                <div class="mt-2 text-xs text-slate-600 space-y-0.5">
                                    <p>Operator: <strong class="text-slate-800" x-text="item.operatorName"></strong></p>
                                    <p>Produk: <span class="text-slate-800 font-medium" x-text="item.product.name"></span></p>
                                    <p>Waktu Berjalan: <span class="font-mono font-bold text-slate-900" x-text="formatSessionDuration(item.startTime)"></span></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </section>

        <!-- Dialog Konfirmasi In-Page Tablet-Friendly -->
        <div x-show="confirmAction" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" role="dialog" aria-modal="true" :aria-label="confirmTitle">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-200">
                <h3 class="text-xl font-bold text-slate-900" x-text="confirmTitle"></h3>
                <p class="mt-2 text-base text-slate-600 leading-relaxed" x-text="confirmMessage"></p>
                <div class="mt-6 flex flex-col-reverse sm:flex-row justify-end gap-3">
                    <button type="button" @click="cancelConfirm" class="min-h-[48px] rounded-xl border border-slate-300 px-5 py-2.5 text-base font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button type="button" @click="executeConfirm" :disabled="!confirmReady || busy" class="min-h-[48px] rounded-xl bg-red-700 px-6 py-2.5 text-base font-bold text-white shadow hover:bg-red-800 transition-colors disabled:opacity-40 disabled:cursor-not-allowed" x-text="confirmButtonText">
                    </button>
                </div>
            </div>
        </div>

        <!-- Dialog Pemberitahuan Sistem In-Page Tablet-Friendly -->
        <div x-show="alertDialogMessage" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" role="dialog" aria-modal="true" :aria-label="alertTitle || 'Pemberitahuan Sistem'">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-200">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 text-2xl font-black">⚠️</span>
                    <div>
                        <h3 class="text-xl font-bold text-slate-900" x-text="alertTitle || 'Pemberitahuan Sistem'"></h3>
                        <p class="text-xs text-slate-500 font-medium">Stasiun Kontrol Produksi</p>
                    </div>
                </div>
                <p class="mt-4 text-base font-medium text-slate-700 leading-relaxed" x-text="alertDialogMessage"></p>
                <div class="mt-6 flex justify-end">
                    <button type="button" @click="alertDialogMessage = ''" class="min-h-[48px] w-full rounded-xl bg-slate-800 px-6 py-2.5 text-base font-bold text-white shadow-md hover:bg-slate-900 active:scale-[0.98] transition-all">
                        Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

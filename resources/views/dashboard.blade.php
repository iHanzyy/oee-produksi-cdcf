<x-app-layout>
    <div
        x-data="productionControl({
            initial: @js($dashboardState),
            deviceClockTestMode: @js(config('app.device_clock_test_mode')),
            areas: @js($areaOptions),
            categories: @js($categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->category_name])->values()),
            personnels: @js($personnels->map(fn ($personnel) => ['id' => $personnel->id, 'name' => $personnel->name])->values()),
            urls: {
                start: @js(route('machine.start')),
                status: @js(route('production.status')),
                base: @js(url('/production-sessions')),
            },
        })"
        class="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-7xl flex-col justify-center px-4 py-5 sm:px-6 sm:py-8 lg:px-8"
    >
        <div
            x-cloak
            x-show="busy"
            role="status"
            aria-live="polite"
            aria-atomic="true"
            class="fixed inset-x-4 bottom-4 z-50 mx-auto max-w-md rounded-xl border border-oee-blue bg-white px-4 py-3 text-sm font-bold text-oee-navy shadow-lg sm:inset-x-auto sm:right-6 sm:w-96"
        >
            <span x-text="busyMessage">Menyimpan perubahan...</span>
        </div>



        <!-- Pesan Sukses / Stabilisasi Stasiun -->
        <div x-cloak x-show="successBanner" role="status" class="mb-5 rounded-xl border-l-4 border-oee-blue bg-oee-ice/30 p-4 text-sm sm:text-base font-bold text-oee-navy shadow-sm flex items-center justify-between transition-all">
            <div class="flex items-center gap-2">
                <svg class="h-6 w-6 shrink-0 text-oee-blue" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                <span x-text="successBanner"></span>
            </div>
            <button type="button" @click="successBanner = ''" class="rounded-lg px-2 py-1 text-sm font-bold text-oee-blue hover:bg-oee-ice/50 hover:text-oee-navy">Tutup</button>
        </div>

        <!-- 1. STATUS READY (Mulai Produksi Baru) -->
        <section x-show="state === 'ready'" aria-labelledby="start-title">
            <!-- Form Mulai Sesi Produksi -->
            <div class="rounded-2xl border border-oee-ice bg-white p-5 shadow-sm sm:p-8">
                <div class="border-b border-oee-ice/70 pb-4">
                    <h2 id="start-title" class="text-xl font-bold text-oee-navy sm:text-2xl">Mulai Sesi Produksi</h2>
                </div>

                <form class="mt-6 grid gap-5 sm:grid-cols-2" @submit.prevent="start">
                    <!-- Area Operasional -->
                    <div>
                        <label for="area_id" class="block text-sm font-bold text-slate-800">Area Operasional <span class="text-red-600">*</span></label>
                        <select id="area_id" x-model="areaId" @change="changeArea" required class="mt-2 block min-h-[50px] w-full rounded-xl border-oee-cyan/70 text-base font-semibold text-oee-navy focus:border-oee-blue focus:ring-oee-blue">
                            <option value="">-- Pilih Area Operasional --</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->area_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Nama Operator -->
                    <div>
                        <label for="operator_name" class="block text-sm font-bold text-slate-800">Nama Operator <span class="text-red-600">*</span></label>
                        <input id="operator_name" x-model.trim="operatorName" type="text" maxlength="100" autocomplete="name" required class="mt-2 block min-h-[50px] w-full rounded-xl border-oee-cyan/70 text-base font-medium text-oee-navy focus:border-oee-blue focus:ring-oee-blue" placeholder="Nama operator">
                    </div>

                    <!-- Nama Produk (Ketik & Dropdown) -->
                    <div class="relative" @click.outside="productMenuOpen = false">
                        <label for="product_name" class="block text-sm font-bold text-slate-800">
                            Nama Produk <span class="text-red-600">*</span>
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
                                class="block min-h-[50px] w-full rounded-xl border-oee-cyan/70 pr-10 text-base font-medium text-oee-navy focus:border-oee-blue focus:ring-oee-blue disabled:cursor-not-allowed disabled:bg-oee-ice/25"
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
                                <svg class="h-5 w-5 transition-transform duration-200" :class="productMenuOpen ? 'rotate-180 text-oee-blue' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                        </div>

                        <!-- Dropdown List Produk Touch-Friendly untuk Tablet -->
                        <div
                            x-show="productMenuOpen && areaId"
                            x-cloak
                            class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-oee-ice bg-white p-1 shadow-2xl"
                        >
                            <template x-for="prod in filteredProducts" :key="prod.id">
                                <button
                                    type="button"
                                    @click="selectProduct(prod)"
                                    class="flex w-full items-center justify-between rounded-lg border-b border-oee-ice/70 px-4 py-3 text-left text-base font-semibold text-oee-navy last:border-0 hover:bg-oee-ice/40 hover:text-oee-blue"
                                >
                                    <span x-text="prod.name"></span>
                                    <span x-show="productId === prod.id" class="font-bold text-oee-blue">✓</span>
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

                        <div class="mt-5">
                            <label for="batch_number" class="block text-sm font-bold text-slate-800">Nomor Batch <span class="text-red-600">*</span></label>
                            <input id="batch_number" x-model.trim="batchNumber" type="text" maxlength="255" autocomplete="off" required class="mt-2 block min-h-[50px] w-full rounded-xl border-oee-cyan/70 text-base font-medium text-oee-navy focus:border-oee-blue focus:ring-oee-blue" placeholder="Nomor batch">
                        </div>
                    </div>

                    <!-- Mesin (Ketik & Dropdown) -->
                    <div class="relative" @click.outside="machineMenuOpen = false">
                        <label for="machine_name" class="block text-sm font-bold text-slate-800">
                            Mesin <span class="text-red-600">*</span>
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
                                class="block min-h-[50px] w-full rounded-xl border-oee-cyan/70 pr-10 text-base font-medium text-oee-navy focus:border-oee-blue focus:ring-oee-blue disabled:cursor-not-allowed disabled:bg-oee-ice/25"
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
                                <svg class="h-5 w-5 transition-transform duration-200" :class="machineMenuOpen ? 'rotate-180 text-oee-blue' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                        </div>

                        <!-- Dropdown List Mesin Touch-Friendly untuk Tablet -->
                        <div
                            x-show="machineMenuOpen && areaId"
                            x-cloak
                            class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-oee-ice bg-white p-1 shadow-2xl"
                        >
                            <template x-for="mch in filteredMachines" :key="mch.id">
                                <button
                                    type="button"
                                    @click="selectMachine(mch)"
                                    class="flex w-full items-center justify-between rounded-lg border-b border-oee-ice/70 px-4 py-3 text-left text-base font-semibold text-oee-navy last:border-0 hover:bg-oee-ice/40 hover:text-oee-blue"
                                >
                                    <span x-text="mch.name"></span>
                                    <span x-show="machineId === mch.id" class="font-bold text-oee-blue">✓</span>
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
                        <button type="submit" :disabled="busy" class="flex min-h-[56px] w-full items-center justify-center rounded-xl bg-primary px-6 py-3.5 text-base font-bold text-white transition-colors hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:text-lg">
                            <span x-show="!busy">Mulai Produksi</span>
                            <span x-cloak x-show="busy">Menyiapkan sesi...</span>
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
                    'border-oee-cyan ring-2 ring-oee-cyan/20': state === 'running',
                    'border-red-400 ring-2 ring-red-500/15': state === 'downtime',
                    'border-oee-ice': state === 'completed'
                }"
            >
                <!-- Bagian Atas: Status & Nama Mesin & Timer -->
                <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <!-- Pill Status Mesin -->
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs sm:text-sm font-extrabold uppercase tracking-wider"
                            :class="{
                                'bg-oee-ice/40 text-oee-navy border border-oee-cyan': state === 'running',
                                'bg-red-100 text-red-800 border border-red-300': state === 'downtime',
                                'bg-oee-ice/30 text-oee-navy border border-oee-ice': state === 'completed'
                            }"
                        >
                            <span class="inline-block h-2.5 w-2.5 rounded-full"
                                :class="{
                                    'bg-oee-blue animate-ping': state === 'running',
                                    'bg-red-600 animate-pulse': state === 'downtime',
                                    'bg-oee-navy': state === 'completed'
                                }"
                            ></span>
                            <span x-text="state === 'downtime' ? 'MESIN BERHENTI (DOWNTIME)' : state === 'completed' ? 'PRODUKSI SELESAI' : 'MESIN BERJALAN'"></span>
                        </div>

                        <!-- Nama Mesin Besar -->
                        <h2 id="session-title" class="mt-2 text-2xl sm:text-4xl font-black text-oee-navy tracking-tight" x-text="session?.machine.name"></h2>
                    </div>

                    <!-- Panel Timer untuk Tablet -->
                    <div class="flex flex-col sm:items-end gap-2">
                        <!-- Tampilan Timer Running -->
                        <div x-show="state === 'running'" class="min-w-[200px] rounded-xl border border-oee-cyan bg-oee-ice/20 px-5 py-3 text-center sm:text-right">
                            <p class="text-xs font-bold uppercase tracking-wider text-oee-blue">Durasi Sesi</p>
                            <p class="mt-0.5 font-mono text-3xl sm:text-4xl font-extrabold text-oee-navy tabular-nums" x-text="elapsed" aria-live="off"></p>
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
                            <!-- Sekunder Timer: Durasi Sesi -->
                            <div class="rounded-xl border border-oee-cyan bg-oee-ice/20 px-4 py-3 text-center sm:text-right min-w-[150px]">
                                <p class="text-xs font-bold uppercase tracking-wider text-oee-blue">Durasi Sesi</p>
                                <p class="mt-0.5 font-mono text-2xl font-bold text-oee-navy tabular-nums" x-text="elapsed" aria-live="off"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Glanceable Crosscheck Data Grid (Untuk dicek Operator di Tablet) -->
                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-3.5">
                        <p class="text-xs font-bold uppercase tracking-wider text-oee-blue">Area</p>
                        <p class="mt-1 text-base font-bold text-oee-navy" x-text="session?.area.name"></p>
                    </div>
                    <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-3.5">
                        <p class="text-xs font-bold uppercase tracking-wider text-oee-blue">Nama Operator</p>
                        <p class="mt-1 text-base font-bold text-oee-navy" x-text="session?.operatorName"></p>
                    </div>
                    <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-3.5">
                        <p class="text-xs font-bold uppercase tracking-wider text-oee-blue">Nama Produk</p>
                        <p class="mt-1 text-base font-bold text-oee-navy" x-text="session?.product.name"></p>
                        <p class="mt-2 text-xs font-bold uppercase tracking-wider text-oee-blue">Nomor Batch</p>
                        <p class="mt-1 text-sm font-bold text-oee-navy" x-text="session?.batchNumber || '-'"></p>
                    </div>
                    <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-3.5">
                        <p class="text-xs font-bold uppercase tracking-wider text-oee-blue">Waktu Mulai</p>
                        <p class="mt-1 text-base font-bold text-oee-navy" x-text="session?.startTime ? new Date(session.startTime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) : '-'"></p>
                    </div>
                </div>

                <!-- Tombol Kendali Saat Mesin Berjalan -->
                <div x-show="state === 'running' && isController" class="mt-6 flex flex-col sm:flex-row gap-4" :class="{ 'pointer-events-none opacity-50': cooldown || busy }">
                    <button type="button" @click="askStop" :disabled="busy || cooldown" class="min-h-[58px] flex-1 rounded-xl bg-red-700 px-6 py-3.5 text-base sm:text-lg font-bold tracking-wider text-white shadow-md hover:bg-red-800 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 transition-colors flex items-center justify-center gap-2">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="5" y="5" width="10" height="10" rx="1" />
                        </svg>
                        <span>STOP MESIN</span>
                    </button>
                    <button type="button" @click="askFinish" :disabled="busy || cooldown" class="flex min-h-[58px] items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-base font-bold text-white shadow-sm transition-colors hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:w-56">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 10.5l4 4L16 6.5" />
                        </svg>
                        <span>Selesai Produksi</span>
                    </button>
                </div>

                <!-- Tampilan & Form Saat Mesin Downtime -->
                <div x-show="state === 'downtime'" class="mt-6 space-y-4">
                    <!-- Banner Pengingat Downtime & Tombol Selesai Downtime -->
                    <div class="flex flex-col gap-4 rounded-xl border border-red-300 bg-red-50 p-5 text-center sm:flex-row sm:items-center sm:justify-between sm:text-left">
                        <div class="flex flex-col items-center gap-3 sm:flex-row sm:items-start">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                            </span>
                            <div>
                            <p class="text-base sm:text-lg font-black text-red-900">Mesin Sedang Dalam Perbaikan (Downtime)</p>
                            <p class="mt-1 text-sm text-red-700">Jika perbaikan mesin telah selesai, tekan tombol di samping untuk mengisi data kendala dan menyalakan mesin.</p>
                            </div>
                        </div>
                        <button
                            x-show="isController"
                            type="button"
                            @click="openDowntimeForm(); $nextTick(() => $refs.categorySelect.focus())"
                            class="inline-flex min-h-[52px] shrink-0 items-center justify-center gap-2 rounded-xl bg-primary px-7 py-3 text-base font-bold text-white shadow-md transition-colors hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Selesai Downtime
                        </button>
                    </div>

                    <!-- Form Kendala & Perbaikan -->
                    <div x-show="isController && showDowntimeForm" x-cloak @click.self="if (!busy) closeDowntimeForm()" @keydown.escape.window="if (!busy) closeDowntimeForm()" class="fixed inset-0 z-50 flex items-center justify-center bg-oee-navy/65 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="downtime-form-title">
                        <div class="max-h-[calc(100vh-2rem)] w-full max-w-3xl overflow-y-auto rounded-2xl border border-oee-cyan bg-white p-5 shadow-2xl sm:p-7">
                        <div class="border-b border-oee-ice pb-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-oee-ice/40 text-oee-blue">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 9.34l-2.59-2.59-5.83 5.83m2.59 2.59-3.53 3.53a2.121 2.121 0 0 1-3-3l3.94-3.94m2.59 3.41 2.59 2.59m.65-11.01 1.06-1.06a2.121 2.121 0 0 1 3 3l-1.47 1.47" />
                                    </svg>
                                </span>
                                <div>
                                    <h3 id="downtime-form-title" class="text-lg font-bold text-oee-navy sm:text-xl">Catat Kendala & Perbaikan</h3>
                                    <p class="mt-0.5 text-sm text-slate-600">Lengkapi data perbaikan sebelum mesin dijalankan kembali.</p>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wider text-red-700">Mesin</p>
                                    <p class="text-base font-bold text-oee-navy" x-text="session?.machine.name"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-bold uppercase tracking-wider text-red-700">Lama Berhenti</p>
                                    <p class="font-mono text-xl font-black text-red-800 tabular-nums" x-text="downtimeElapsed"></p>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="resume" class="mt-5 grid gap-4 sm:grid-cols-2">
                            <!-- Kategori Kendala (Render Blade Native agar 100% stabil) -->
                            <div>
                                <label for="category_id" class="block text-sm font-bold text-slate-800">
                                    Kategori Kendala <span class="text-red-600">*</span>
                                </label>
                                <select id="category_id" x-ref="categorySelect" x-model="categoryId" required class="mt-2 block min-h-[50px] w-full rounded-xl border-oee-cyan/70 text-base font-medium text-oee-navy focus:border-oee-blue focus:ring-oee-blue">
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
                                <select id="personnel_id" x-model="personnelId" required class="mt-2 block min-h-[50px] w-full rounded-xl border-oee-cyan/70 text-base font-medium text-oee-navy focus:border-oee-blue focus:ring-oee-blue">
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
                                <input id="replaced_component" x-model.trim="replacedComponent" type="text" maxlength="255" class="mt-2 block min-h-[50px] w-full rounded-xl border-oee-cyan/70 text-base text-oee-navy focus:border-oee-blue focus:ring-oee-blue" placeholder="Contoh: sensor switch, seal bearing, atau nozzle">
                            </div>

                            <!-- Tindakan Perbaikan -->
                            <div class="sm:col-span-2">
                                <label for="repair_action" class="block text-sm font-bold text-slate-800">
                                    Tindakan Perbaikan <span class="text-red-600">*</span>
                                </label>
                                <textarea id="repair_action" x-model.trim="repairAction" required maxlength="1000" rows="3" class="mt-2 block w-full rounded-xl border-oee-cyan/70 text-base text-oee-navy focus:border-oee-blue focus:ring-oee-blue" placeholder="Contoh: membersihkan sensor dan mengencangkan konektor"></textarea>
                            </div>

                            <!-- Pesan Error Langsung di Form (Mudah dilihat di tablet tanpa scroll) -->
                            <div x-show="formError" x-cloak class="sm:col-span-2 rounded-xl border-l-4 border-red-600 bg-red-50 p-4 text-sm font-bold text-red-900">
                                <span x-text="formError"></span>
                            </div>

                            <!-- Tombol Eksekusi Mulai Mesin -->
                            <div class="sm:col-span-2 flex flex-col-reverse sm:flex-row justify-end gap-3 pt-2">
                                <button type="button" @click="closeDowntimeForm" :disabled="busy" class="flex min-h-[50px] items-center justify-center gap-2 rounded-xl border border-oee-cyan bg-white px-5 py-3 text-base font-semibold text-oee-navy transition-colors hover:bg-oee-ice/30 disabled:cursor-not-allowed">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                    Batal
                                </button>
                                <button type="submit" :disabled="busy" class="flex min-h-[54px] items-center justify-center gap-2 rounded-xl bg-primary px-8 py-3.5 text-base font-bold text-white shadow-md transition-colors hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-60 sm:text-lg">
                                    <svg x-show="!busy" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v9m6.364-6.364a9 9 0 1 1-12.728 0" />
                                    </svg>
                                    <span x-show="!busy">Simpan & Mulai Mesin</span>
                                    <span x-cloak x-show="busy">Menjalankan mesin...</span>
                                </button>
                            </div>
                        </form>
                        </div>
                    </div>
                </div>

                <!-- 3. STATUS COMPLETED (Popup Ringkasan Akhir) -->
                <div x-show="state === 'completed'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-oee-navy/65 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="completed-summary-title">
                    <div role="status" class="max-h-[calc(100vh-2rem)] w-full max-w-4xl overflow-y-auto rounded-2xl border border-oee-cyan bg-white p-5 text-oee-navy shadow-2xl sm:p-7">
                    <div class="flex items-center gap-3 border-b border-oee-ice pb-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-oee-blue text-white">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </span>
                        <div>
                            <h3 id="completed-summary-title" class="text-xl font-black text-oee-navy sm:text-2xl">Sesi Produksi Telah Selesai</h3>
                            <p class="text-xs text-slate-600 sm:text-sm">Periksa hasil produksi ini lalu tekan OKE untuk membuka sesi berikutnya.</p>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs font-bold">
                                <span class="rounded-full bg-oee-navy px-3 py-1 text-white" x-text="session?.machine.name"></span>
                                <span class="rounded-full bg-oee-ice/50 px-3 py-1 text-oee-navy" x-text="session?.product.name"></span>
                                <span x-show="session?.batchNumber" class="rounded-full border border-oee-cyan px-3 py-1 text-oee-blue" x-text="'Batch ' + session?.batchNumber"></span>
                            </div>
                        </div>
                    </div>

                    <dl class="mt-5 grid gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-4">
                            <dt class="text-xs font-bold uppercase text-oee-blue">Waktu Selesai</dt>
                            <dd class="mt-1 text-base font-bold text-oee-navy" x-text="session?.endTime ? new Date(session.endTime).toLocaleString('id-ID') : '-'"></dd>
                        </div>
                        <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-4">
                            <dt class="text-xs font-bold uppercase text-oee-blue">Durasi Sesi</dt>
                            <dd class="mt-1 text-base font-bold text-oee-navy" x-text="duration(session?.summary.totalDurationSeconds)"></dd>
                        </div>
                        <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-4">
                            <dt class="text-xs font-bold uppercase text-oee-blue">Ada Kendala Downtime</dt>
                            <dd class="mt-1 text-base font-bold" :class="session?.summary.hadDowntime ? 'text-amber-700' : 'text-oee-blue'" x-text="session?.summary.hadDowntime ? 'Ya' : 'Tidak'"></dd>
                        </div>
                        <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-4">
                            <dt class="text-xs font-bold uppercase text-oee-blue">Jumlah Downtime</dt>
                            <dd class="mt-1 text-base font-bold text-oee-navy" x-text="session?.summary.downtimeCount + ' kali'"></dd>
                        </div>
                        <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-4">
                            <dt class="text-xs font-bold uppercase text-oee-blue">Total Downtime</dt>
                            <dd class="mt-1 text-base font-bold text-red-700" x-text="duration(session?.summary.totalDowntimeSeconds)"></dd>
                        </div>
                        <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-4">
                            <dt class="text-xs font-bold uppercase text-oee-blue">Total Output</dt>
                            <dd class="mt-1 text-base font-bold text-oee-navy tabular-nums" x-text="formatOutput(session?.oee?.totalOutput)"></dd>
                        </div>
                        <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-4">
                            <dt class="text-xs font-bold uppercase text-oee-blue">Reject</dt>
                            <dd class="mt-1 text-base font-bold text-red-700 tabular-nums" x-text="formatOutput(session?.oee?.rejectOutput)"></dd>
                        </div>
                        <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-4">
                            <dt class="text-xs font-bold uppercase text-oee-blue">Good Output</dt>
                            <dd class="mt-1 text-base font-bold text-oee-blue tabular-nums" x-text="formatOutput(session?.oee?.goodOutput)"></dd>
                        </div>
                        <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-4">
                            <dt class="text-xs font-bold uppercase text-oee-blue">Planned Production Time</dt>
                            <dd class="mt-1 text-base font-bold text-oee-navy" x-text="duration((session?.oee?.plannedTimeMinutes ?? 0) * 60)"></dd>
                        </div>
                        <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-4">
                            <dt class="text-xs font-bold uppercase text-oee-blue">Availability</dt>
                            <dd class="mt-1 text-base font-bold text-oee-navy tabular-nums" x-text="formatPercent(session?.oee?.availabilityPercent)"></dd>
                        </div>
                        <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-4">
                            <dt class="text-xs font-bold uppercase text-oee-blue">Quality</dt>
                            <dd class="mt-1 text-base font-bold text-oee-navy tabular-nums" x-text="formatPercent(session?.oee?.qualityPercent)"></dd>
                        </div>
                        <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-4">
                            <dt class="text-xs font-bold uppercase text-oee-blue">Performance</dt>
                            <dd class="mt-1 text-base font-bold text-oee-navy tabular-nums" x-text="formatPercent(session?.oee?.performancePercent)"></dd>
                        </div>
                        <div class="rounded-xl border border-oee-cyan bg-oee-ice/30 p-4">
                            <dt class="text-xs font-bold uppercase text-oee-blue">OEE</dt>
                            <dd class="mt-1 text-lg font-black text-oee-navy tabular-nums" x-text="formatPercent(session?.oee?.oeePercent)"></dd>
                        </div>
                        <div class="rounded-xl border border-oee-ice bg-oee-ice/15 p-4 sm:col-span-2 lg:col-span-2">
                            <dt class="text-xs font-bold uppercase text-oee-blue">Peringatan</dt>
                            <dd class="mt-1 text-sm font-bold" :class="session?.oee?.warnings?.length ? 'text-amber-800' : 'text-slate-700'" x-text="session?.oee?.warnings?.length ? session.oee.warnings.join(' ') : '-'"></dd>
                        </div>
                    </dl>

                    <button x-show="isController" type="button" @click="acknowledge" :disabled="busy" class="mt-6 flex min-h-[56px] w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-lg font-bold text-white shadow-md transition-colors hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-60">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        OKE
                    </button>
                    </div>
                </div>
            </div>

        </section>

        <!-- Dialog Konfirmasi In-Page Tablet-Friendly -->
        <div x-show="confirmAction" x-cloak @click.self="if (!busy) cancelConfirm()" @keydown.escape.window="cancelConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-oee-navy/60 backdrop-blur-sm p-4" role="dialog" aria-modal="true" :aria-label="confirmTitle">
            <div class="max-h-[calc(100vh-2rem)] w-full max-w-md overflow-y-auto rounded-2xl border border-oee-ice bg-white p-6 shadow-2xl">
                <div class="flex items-start gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full" :class="confirmAction === 'stop' ? 'bg-red-700 text-white' : 'bg-oee-ice/50 text-oee-blue'">
                        <svg x-show="confirmAction === 'stop'" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="7" y="7" width="10" height="10" rx="2" /></svg>
                        <svg x-show="confirmAction === 'finish'" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-xl font-bold text-oee-navy" x-text="confirmTitle"></h3>
                        <p class="mt-1 truncate text-sm font-semibold text-oee-blue" x-text="(session?.machine.name || '') + (session?.batchNumber ? ' · Batch ' + session.batchNumber : '')"></p>
                    </div>
                </div>
                <p class="mt-4 text-base text-slate-600 leading-relaxed" x-text="confirmMessage"></p>
                <div x-show="confirmAction === 'finish'" class="mt-4 space-y-4">
                    <div>
                        <label for="total_output" class="block text-sm font-bold text-slate-800">
                            Total Output (<span x-text="outputUnitLabel"></span>) <span class="text-red-600">*</span>
                        </label>
                        <input
                            id="total_output"
                            x-model="totalOutput"
                            :type="outputUnitLabel === 'kg' ? 'text' : 'number'"
                            :step="outputStep"
                            min="0"
                            inputmode="decimal"
                            required
                            class="mt-2 block min-h-[50px] w-full rounded-xl border-oee-cyan/70 text-base font-semibold text-oee-navy tabular-nums focus:border-oee-blue focus:ring-oee-blue"
                            placeholder="Masukkan seluruh output"
                        >
                    </div>
                    <div>
                        <label for="reject_output" class="block text-sm font-bold text-slate-800">
                            Reject (<span x-text="outputUnitLabel"></span>) <span class="text-red-600">*</span>
                        </label>
                        <input
                            id="reject_output"
                            x-model="rejectOutput"
                            :type="outputUnitLabel === 'kg' ? 'text' : 'number'"
                            :step="outputStep"
                            min="0"
                            :max="totalOutput"
                            inputmode="decimal"
                            required
                            class="mt-2 block min-h-[50px] w-full rounded-xl border-oee-cyan/70 text-base font-semibold text-oee-navy tabular-nums focus:border-oee-blue focus:ring-oee-blue"
                            placeholder="Masukkan output reject"
                        >
                    </div>
                    <div class="rounded-xl border border-oee-cyan bg-oee-ice/25 p-4" aria-live="polite">
                        <p class="text-xs font-bold uppercase tracking-wide text-oee-blue">Good Output</p>
                        <p class="mt-1 text-lg font-black text-oee-navy tabular-nums" x-text="formatOutput(goodOutput)"></p>
                    </div>
                </div>
                <div class="mt-6 flex flex-col-reverse sm:flex-row justify-end gap-3">
                    <button type="button" @click="cancelConfirm" :disabled="busy" class="flex min-h-[48px] items-center justify-center gap-2 rounded-xl border border-oee-cyan px-5 py-2.5 text-base font-semibold text-oee-navy transition-colors hover:bg-oee-ice/30 disabled:cursor-not-allowed">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        Batal
                    </button>
                    <button type="button" @click="executeConfirm" :disabled="!canConfirm() || busy" class="flex min-h-[48px] items-center justify-center gap-2 rounded-xl px-6 py-2.5 text-base font-bold text-white shadow transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed" :class="confirmAction === 'stop' ? 'bg-red-700 hover:bg-red-800 focus:ring-red-700' : 'bg-primary hover:bg-secondary focus:ring-primary'">
                        <svg x-show="confirmAction === 'stop'" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="7" y="7" width="10" height="10" rx="2" /></svg>
                        <svg x-show="confirmAction === 'finish'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        <span x-text="confirmButtonText"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Dialog Pemberitahuan Sistem In-Page Tablet-Friendly -->
        <div x-show="alertDialogMessage" x-cloak @keydown.escape.window="alertDialogMessage = ''" class="fixed inset-0 z-50 flex items-center justify-center bg-oee-navy/60 backdrop-blur-sm p-4" role="dialog" aria-modal="true" :aria-label="alertTitle || 'Pemberitahuan Sistem'">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-oee-ice">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                        <svg class="h-6 w-6" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 3L2.5 16h15L10 3zM10 7v4m0 2.5h.01" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-xl font-bold text-oee-navy" x-text="alertTitle || 'Pemberitahuan Sistem'"></h3>
                    </div>
                </div>
                <p class="mt-4 text-base font-medium text-slate-700 leading-relaxed" x-text="alertDialogMessage"></p>
                <div class="mt-6 flex justify-end">
                    <button type="button" @click="alertDialogMessage = ''" class="flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-base font-bold text-white shadow-md hover:bg-secondary active:scale-[0.98] transition-all">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

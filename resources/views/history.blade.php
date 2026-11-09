<x-app-layout>
    <div
        x-data="historyMonitoring({
            initial: @js($activeSessions ?? []),
            url: @js(route('history.active_sessions')),
        })"
        class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8"
    >
        <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-[#005cb9]">Panel Supervisor &amp; Manager</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Pemantauan &amp; Riwayat Produksi</h1>
            </div>

            <a
                href="{{ route('history.export', request()->query()) }}"
                class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 3v9m0 0l-3.5-3.5M10 12l3.5-3.5M4 14v2a1 1 0 001 1h10a1 1 0 001-1v-2" />
                </svg>
                <span>Unduh Excel</span>
            </a>
        </header>

        <!-- Live Plant Monitoring Section -->
        <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="live-monitoring-title">
            <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2.5">
                    <h2 id="live-monitoring-title" class="text-lg font-bold tracking-tight text-slate-950 sm:text-xl">Monitoring Mesin Aktif (Live)</h2>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-2.5 py-0.5 text-xs font-bold text-[#005cb9]">
                        <span class="h-2 w-2 rounded-full bg-[#01c5f0]"></span>
                        Live
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-emerald-800 tabular-nums">
                        <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                        <span x-text="runningCount + ' Berjalan'"></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-red-800 tabular-nums">
                        <span class="h-2 w-2 rounded-full bg-red-600"></span>
                        <span x-text="downtimeCount + ' Kendala'"></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-700 tabular-nums">
                        <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                        <span x-text="sessions.length + ' Total Aktif'"></span>
                    </span>
                </div>
            </div>

            <div x-show="sessions.length === 0" x-cloak class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-6 text-center">
                <p class="text-sm font-bold text-slate-800">Semua Mesin Standby</p>
                <p class="mt-1 text-xs font-medium text-slate-500">Belum ada sesi produksi aktif saat ini.</p>
            </div>

            <div x-show="sessions.length > 0" x-cloak class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <template x-for="item in sessions" :key="item.id">
                    <div
                        class="rounded-xl border bg-white p-4"
                        :class="item.status === 'running' ? 'border-slate-200' : 'border-red-200 bg-red-50/20'"
                    >
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-3">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-bold uppercase tracking-wider"
                                :class="item.status === 'running' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200'"
                            >
                                <span class="h-2 w-2 rounded-full" :class="item.status === 'running' ? 'bg-emerald-600' : 'bg-red-600 animate-pulse'"></span>
                                <span x-text="item.status === 'running' ? 'BERJALAN' : 'DOWNTIME'"></span>
                            </span>
                            <span class="rounded-md bg-sky-50 px-2 py-0.5 text-xs font-semibold text-[#005cb9]" x-text="item.area"></span>
                        </div>

                        <h3 class="mt-3 text-lg font-bold tracking-tight text-slate-950" x-text="item.machine"></h3>

                        <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div class="rounded-lg border border-slate-100 bg-slate-50 p-2.5">
                                <dt class="text-[11px] font-semibold text-slate-500">Operator</dt>
                                <dd class="mt-0.5 truncate font-bold text-slate-900" x-text="item.operatorName"></dd>
                            </div>
                            <div class="rounded-lg border border-slate-100 bg-slate-50 p-2.5">
                                <dt class="text-[11px] font-semibold text-slate-500">Nomor Batch</dt>
                                <dd class="mt-0.5 truncate font-bold text-slate-900" x-text="item.batchNumber || '-'"></dd>
                            </div>
                            <div class="col-span-2 rounded-lg border border-slate-100 bg-slate-50 p-2.5">
                                <dt class="text-[11px] font-semibold text-slate-500">Produk</dt>
                                <dd class="mt-0.5 truncate font-bold text-slate-900" x-text="item.product"></dd>
                            </div>
                        </dl>

                        <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
                            <span class="text-xs font-semibold text-slate-500">Waktu Berjalan</span>
                            <span class="font-mono text-sm font-bold text-slate-950 tabular-nums" x-text="calcElapsed(item.startTime)"></span>
                        </div>

                        <div x-show="item.status === 'downtime' && item.downtime" class="mt-2 flex items-center justify-between rounded-lg border border-red-200 bg-red-50 px-2.5 py-2 text-xs">
                            <span class="font-bold text-red-800">Durasi Downtime</span>
                            <span class="font-mono font-bold text-red-900 tabular-nums" x-text="calcElapsed(item.downtime?.stopTime)"></span>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <!-- Panel Filter Pencarian -->
        <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-label="Filter Riwayat">
            @if($errors->any())
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('history.index') }}" method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                <div>
                    <label for="start_date" class="block text-xs font-bold uppercase tracking-wider text-slate-600">Dari Tanggal</label>
                    <input id="start_date" type="date" name="start_date" value="{{ request('start_date') }}" class="mt-1.5 block min-h-[44px] w-full rounded-xl border-slate-300 text-sm font-medium text-slate-900 focus:border-[#005cb9] focus:ring-[#005cb9]">
                </div>
                <div>
                    <label for="end_date" class="block text-xs font-bold uppercase tracking-wider text-slate-600">Sampai Tanggal</label>
                    <input id="end_date" type="date" name="end_date" value="{{ request('end_date') }}" class="mt-1.5 block min-h-[44px] w-full rounded-xl border-slate-300 text-sm font-medium text-slate-900 focus:border-[#005cb9] focus:ring-[#005cb9]">
                </div>
                <div>
                    <label for="area_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600">Area Produksi</label>
                    <select id="area_id" name="area_id" class="mt-1.5 block min-h-[44px] w-full rounded-xl border-slate-300 text-sm font-medium text-slate-900 focus:border-[#005cb9] focus:ring-[#005cb9]">
                        <option value="">-- Semua Area --</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->area_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="machine_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600">Pilih Mesin</label>
                    <select id="machine_id" name="machine_id" class="mt-1.5 block min-h-[44px] w-full rounded-xl border-slate-300 text-sm font-medium text-slate-900 focus:border-[#005cb9] focus:ring-[#005cb9]">
                        <option value="">-- Semua Mesin --</option>
                        @foreach($machines as $machine)
                            <option value="{{ $machine->id }}" {{ request('machine_id') == $machine->id ? 'selected' : '' }}>{{ $machine->machine_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                    <button type="submit" class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-1.5 rounded-xl bg-[#005cb9] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#004a94] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#005cb9] focus-visible:ring-offset-2">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17l-3.8-3.8m1.3-4.2a5.5 5.5 0 11-11 0 5.5 5.5 0 0111 0z" />
                        </svg>
                        <span>Cari</span>
                    </button>
                    <a href="{{ route('history.index') }}" class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-1.5 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 10a6 6 0 101.76-4.24L4 7.5M4 4v3.5h3.5" />
                        </svg>
                        <span>Reset</span>
                    </a>
                </div>
            </form>
        </section>

        <!-- Panel Tabel Riwayat Produksi/OEE -->
        <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="oee-history-title">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 id="oee-history-title" class="text-base font-bold text-slate-950">Riwayat Produksi/OEE</h2>
                <span class="rounded-lg bg-sky-50 px-2.5 py-1 text-xs font-bold text-[#005cb9] tabular-nums">{{ $completedSessions->total() }} Sesi</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Waktu Selesai</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Identitas Sesi</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Output Aktual</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Availability</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Quality</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Performance</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">OEE</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Peringatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($completedSessions as $session)
                        @php($warnings = $session->oeeWarnings())
                        <tr class="transition-colors hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-5 py-4 sm:px-6 tabular-nums">
                                <div class="text-sm font-bold text-slate-900">{{ $session->end_time?->timezone('Asia/Jakarta')->format('d M Y') ?? '-' }}</div>
                                <div class="mt-0.5 text-xs font-medium text-slate-500">{{ $session->end_time?->timezone('Asia/Jakarta')->format('H:i:s') ?? '-' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                <div class="text-sm font-bold text-slate-900">{{ $session->machine?->machine_name ?? '-' }}</div>
                                <div class="mt-0.5 text-xs font-medium text-slate-500">{{ $session->machine?->area?->area_name ?? '-' }} · {{ $session->product?->product_name ?? '-' }}</div>
                                <div class="mt-1 text-xs font-semibold text-[#005cb9]">Batch: {{ $session->batch_number ?? '-' }} · Operator: {{ $session->operator_name ?? '-' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-slate-800 sm:px-6 tabular-nums">
                                {{ $session->actual_output_kg !== null ? rtrim(rtrim(number_format((float) $session->actual_output_kg, 3, ',', '.'), '0'), ',') . ' kg' : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-slate-800 sm:px-6 tabular-nums">
                                {{ $session->availability_percent !== null ? number_format((float) $session->availability_percent, 2, ',', '.') . '%' : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-slate-800 sm:px-6 tabular-nums">
                                {{ $session->quality_percent !== null ? number_format((float) $session->quality_percent, 2, ',', '.') . '%' : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-slate-800 sm:px-6 tabular-nums">
                                {{ $session->performance_percent !== null ? number_format((float) $session->performance_percent, 2, ',', '.') . '%' : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold text-emerald-700 sm:px-6 tabular-nums">
                                {{ $session->oee_percent !== null ? number_format((float) $session->oee_percent, 2, ',', '.') . '%' : '-' }}
                            </td>
                            <td class="px-5 py-4 text-xs font-semibold sm:px-6 {{ count($warnings) ? 'text-amber-700' : 'text-slate-400' }}">
                                {{ count($warnings) ? implode(' ', $warnings) : '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                                Belum ada riwayat sesi produksi selesai pada filter ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($completedSessions->hasPages())
                <div class="border-t border-slate-200 bg-slate-50 px-6 py-4">
                    {{ $completedSessions->links() }}
                </div>
            @endif
        </section>

        <!-- Panel Tabel Riwayat Kendala -->
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="downtime-history-title">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 id="downtime-history-title" class="text-base font-bold text-slate-950">Riwayat Kendala &amp; Downtime</h2>
                <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 tabular-nums">{{ $logs->total() }} Data</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Waktu Stop</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Area &amp; Mesin</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Detail Perbaikan</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Pelaksana</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Total Durasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($logs as $log)
                        <tr class="transition-colors hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-5 py-4 sm:px-6 tabular-nums">
                                <div class="text-sm font-bold text-slate-900">{{ $log->stop_time->timezone('Asia/Jakarta')->format('d M Y') }}</div>
                                <div class="mt-0.5 text-xs font-medium text-slate-500">{{ $log->stop_time->timezone('Asia/Jakarta')->format('H:i:s') }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                <div class="text-sm font-bold text-slate-900">{{ optional(optional($log->productionSession)->machine)->machine_name ?? '-' }}</div>
                                <div class="mt-0.5 text-xs font-medium text-slate-500">{{ optional(optional($log->productionSession)->machine)->area->area_name ?? '-' }}</div>
                                <div class="mt-1 text-xs font-semibold text-[#005cb9]">Nomor Batch: {{ optional($log->productionSession)->batch_number ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <span class="inline-flex items-center rounded-md border border-red-200 bg-red-50 px-2 py-0.5 text-xs font-bold text-red-700">
                                    {{ optional($log->downtimeCategory)->category_name ?? '-' }}
                                </span>
                                <div class="mt-1.5 text-sm font-medium text-slate-800">{{ $log->repair_action ?? '-' }}</div>
                                <div class="mt-1 text-xs font-medium text-slate-500">Komponen: {{ $log->replaced_component ?? 'Tidak ada' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-800 sm:px-6">
                                {{ optional($log->personnel)->name ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 sm:px-6 tabular-nums">
                                @if($log->status_label === 'Selesai')
                                    <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-700">
                                        {{ round($log->duration_seconds / 60, 1) }} Menit · Selesai
                                    </span>
                                @elseif($log->status_label === 'Berlangsung')
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700">
                                        <span class="h-2 w-2 rounded-full bg-red-600 animate-pulse"></span>
                                        Berlangsung
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">
                                        Data tidak lengkap
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                                Tidak ada data histori kendala pada filter ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="border-t border-slate-200 bg-slate-50 px-6 py-4">
                    {{ $logs->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
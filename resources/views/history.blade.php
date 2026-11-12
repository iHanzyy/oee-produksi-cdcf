<x-app-layout>
    <div
        x-data="historyMonitoring({
            initial: @js($activeSessions ?? []),
            serverNow: @js($serverNow),
            url: @js(route('history.active_sessions')),
        })"
        class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8"
    >
        <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-brand-700">Panel Supervisor &amp; Manager</p>
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
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-2.5 py-0.5 text-xs font-bold text-brand-700">
                        <span class="h-2 w-2 rounded-full bg-brand-400"></span>
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
                            <span class="rounded-md bg-sky-50 px-2 py-0.5 text-xs font-semibold text-brand-700" x-text="item.area"></span>
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

            <form action="{{ route('history.index') }}" method="GET" x-data="{ loading: false }" @submit="loading = true" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                <div>
                    <label for="start_date" class="block text-xs font-bold uppercase tracking-wider text-slate-600">Dari Tanggal</label>
                    <input id="start_date" type="date" name="start_date" value="{{ request('start_date') }}" class="mt-1.5 block min-h-[44px] w-full rounded-xl border-slate-300 text-sm font-medium text-slate-900 focus:border-brand-700 focus:ring-brand-700">
                </div>
                <div>
                    <label for="end_date" class="block text-xs font-bold uppercase tracking-wider text-slate-600">Sampai Tanggal</label>
                    <input id="end_date" type="date" name="end_date" value="{{ request('end_date') }}" class="mt-1.5 block min-h-[44px] w-full rounded-xl border-slate-300 text-sm font-medium text-slate-900 focus:border-brand-700 focus:ring-brand-700">
                </div>
                <div>
                    <label for="area_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600">Area Produksi</label>
                    <select id="area_id" name="area_id" class="mt-1.5 block min-h-[44px] w-full rounded-xl border-slate-300 text-sm font-medium text-slate-900 focus:border-brand-700 focus:ring-brand-700">
                        <option value="">-- Semua Area --</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->area_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="machine_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600">Pilih Mesin</label>
                    <select id="machine_id" name="machine_id" class="mt-1.5 block min-h-[44px] w-full rounded-xl border-slate-300 text-sm font-medium text-slate-900 focus:border-brand-700 focus:ring-brand-700">
                        <option value="">-- Semua Mesin --</option>
                        @foreach($machines as $machine)
                            <option value="{{ $machine->id }}" {{ request('machine_id') == $machine->id ? 'selected' : '' }}>{{ $machine->machine_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                    <button type="submit" :disabled="loading" class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-1.5 rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-brand-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                        <svg x-show="!loading" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17l-3.8-3.8m1.3-4.2a5.5 5.5 0 11-11 0 5.5 5.5 0 0111 0z" />
                        </svg>
                        <span x-show="!loading">Cari</span>
                        <span x-cloak x-show="loading" role="status" aria-live="polite">Memuat riwayat...</span>
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
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="oee-history-title">
            <div class="flex flex-col gap-1 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 id="oee-history-title" class="text-base font-bold text-slate-950">Riwayat Produksi/OEE</h2>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">Klik baris sesi atau tombol <span class="font-semibold text-slate-700">Lihat detail</span> untuk membuka halaman rincian produksi, komponen OEE, dan kendala sesi.</p>
                </div>
                <span class="self-start rounded-lg bg-sky-50 px-2.5 py-1 text-xs font-bold text-brand-700 tabular-nums sm:self-auto">{{ $completedSessions->total() }} Sesi</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Waktu Selesai</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Identitas Sesi</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Output Produksi</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">OEE</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Kendala &amp; Catatan</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @php
                            $filterQuery = array_filter(
                                request()->only(['start_date', 'end_date', 'area_id', 'machine_id', 'session_page']),
                                fn ($v) => is_scalar($v) && trim((string) $v) !== ''
                            );
                        @endphp
                        @forelse($completedSessions as $session)
                            @php
                                $warnings = $session->oeeWarnings();
                                $hasOeeSnapshot = $session->oee_percent !== null
                                    || $session->availability_percent !== null
                                    || $session->quality_percent !== null
                                    || $session->performance_percent !== null;
                                $hasGenericOutput = $session->total_output !== null;
                                $outputUnit = $hasGenericOutput ? ($session->output_unit ?? 'unit') : 'kg';
                                $formatOutput = fn ($value) => $value !== null
                                    ? rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',') . ' ' . $outputUnit
                                    : '-';
                                $totalOutputValue = $hasGenericOutput ? $session->total_output : $session->actual_output_kg;
                                $rejectOutputValue = $hasGenericOutput ? $session->reject_output : null;
                                $goodOutputValue = $hasGenericOutput && $session->reject_output !== null
                                    ? (float) $session->total_output - (float) $session->reject_output
                                    : $session->actual_output_kg;
                                $theoreticalOutputValue = $hasGenericOutput ? $session->theoretical_output : $session->theoretical_output_kg;
                                $totalOutputText = $formatOutput($totalOutputValue);
                                $rejectOutputText = $formatOutput($rejectOutputValue);
                                $goodOutputText = $formatOutput($goodOutputValue);
                                $theoreticalOutputText = $formatOutput($theoreticalOutputValue);
                                $diffOutputValue = ($goodOutputValue !== null && $theoreticalOutputValue !== null)
                                    ? (float) $goodOutputValue - (float) $theoreticalOutputValue
                                    : null;
                                $diffOutputText = $diffOutputValue !== null
                                    ? ($diffOutputValue > 0 ? '+' : ($diffOutputValue < 0 ? '-' : '')) . $formatOutput(abs($diffOutputValue))
                                    : '-';
                                $downtimeCount = (int) ($session->downtime_logs_count ?? 0);
                                $totalDowntimeSeconds = (int) ($session->downtime_logs_sum_duration_seconds ?? 0);
                                $totalDowntimeMinutesText = rtrim(rtrim(number_format($totalDowntimeSeconds / 60, 1, ',', '.'), '0'), ',') . ' menit';
                                $sessionDurationSeconds = ($session->start_time && $session->end_time)
                                    ? max(0, $session->start_time->diffInSeconds($session->end_time))
                                    : null;
                                $sessionDurationMinutesText = $sessionDurationSeconds !== null
                                    ? rtrim(rtrim(number_format($sessionDurationSeconds / 60, 1, ',', '.'), '0'), ',') . ' menit'
                                    : '-';
                                $detailUrl = route('history.show', array_merge(['productionSession' => $session->id], $filterQuery));
                            @endphp
                            <tr
                                @click="if (!$event.target.closest('a')) window.location.href = @js($detailUrl)"
                                class="cursor-pointer transition-colors hover:bg-slate-50/80"
                            >
                                <td class="whitespace-nowrap px-5 py-4 align-top sm:px-6 tabular-nums">
                                    <div class="text-sm font-bold text-slate-900">{{ $session->end_time?->timezone('Asia/Jakarta')->format('d M Y') ?? '-' }}</div>
                                    <div class="mt-0.5 text-xs font-medium text-slate-500">{{ $session->end_time?->timezone('Asia/Jakarta')->format('H:i:s') ?? '-' }} WIB</div>
                                    <div class="mt-1 text-[11px] font-medium text-slate-500">Durasi: {{ $sessionDurationMinutesText }}</div>
                                </td>
                                <td class="px-5 py-4 align-top sm:px-6">
                                    <div class="text-sm font-bold text-slate-900">{{ $session->machine?->machine_name ?? '-' }}</div>
                                    <div class="mt-0.5 text-xs font-medium text-slate-500">{{ $session->machine?->area?->area_name ?? '-' }} · {{ $session->product?->product_name ?? '-' }}</div>
                                    <div class="mt-1 text-xs font-semibold text-brand-700">Nomor Batch: {{ $session->batch_number ?? '-' }} · Operator: {{ $session->operator_name ?? '-' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right align-top sm:px-6 tabular-nums">
                                    <div class="text-sm font-bold text-slate-900">{{ $totalOutputText }}</div>
                                    @if($hasGenericOutput)
                                        <div class="mt-0.5 text-xs font-medium text-emerald-700">Good: {{ $goodOutputText }}</div>
                                        <div class="mt-0.5 text-xs font-medium text-red-700">Reject: {{ $rejectOutputText }}</div>
                                    @endif
                                    <div class="mt-0.5 text-xs font-medium text-slate-500">Teoritis: {{ $theoreticalOutputText }}</div>
                                    @if($diffOutputValue !== null)
                                        <div class="mt-1 text-xs font-semibold {{ $diffOutputValue > 0 ? 'text-amber-700' : ($diffOutputValue < 0 ? 'text-slate-600' : 'text-emerald-700') }}">
                                            Selisih: {{ $diffOutputText }}
                                        </div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right align-top sm:px-6 tabular-nums">
                                    <div class="text-base font-bold {{ count($warnings) ? 'text-amber-700' : 'text-emerald-700' }}">
                                        {{ $session->oee_percent !== null ? number_format((float) $session->oee_percent, 2, ',', '.') . '%' : '-' }}
                                    </div>
                                    @if($hasOeeSnapshot)
                                        <div class="mt-1 text-[11px] font-medium text-slate-500">
                                            A: {{ $session->availability_percent !== null ? number_format((float) $session->availability_percent, 2, ',', '.') . '%' : '-' }}
                                            · Q: {{ $session->quality_percent !== null ? number_format((float) $session->quality_percent, 2, ',', '.') . '%' : '-' }}
                                            · P: {{ $session->performance_percent !== null ? number_format((float) $session->performance_percent, 2, ',', '.') . '%' : '-' }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 align-top text-xs sm:px-6">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if($downtimeCount > 0)
                                            <span class="inline-flex items-center rounded-md border border-red-200 bg-red-50 px-2 py-0.5 font-bold text-red-700 tabular-nums">
                                                {{ $downtimeCount }} kendala · {{ $totalDowntimeMinutesText }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 font-bold text-emerald-700">
                                                Tanpa kendala
                                            </span>
                                        @endif
                                    </div>
                                    @if(count($warnings))
                                        <p class="mt-1.5 font-semibold text-amber-700">{{ implode(' ', $warnings) }}</p>
                                    @elseif(!$hasOeeSnapshot)
                                        <p class="mt-1.5 font-medium text-slate-500">Data OEE sesi ini belum lengkap.</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right align-top sm:px-6">
                                    <a
                                        href="{{ $detailUrl }}"
                                        class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-800 shadow-sm transition-colors hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2"
                                    >
                                        <span>Lihat detail</span>
                                        <svg class="h-4 w-4 shrink-0 text-slate-500" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 5.5L12 10l-4.5 4.5" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                                    Belum ada sesi produksi selesai pada filter ini.
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
    </div>
</x-app-layout>

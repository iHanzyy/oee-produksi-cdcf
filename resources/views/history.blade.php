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
                <p class="text-sm font-semibold text-secondary">Panel Supervisor, Manager &amp; AMD</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Pemantauan &amp; Riwayat Produksi</h1>
            </div>

            <x-action-button href="{{ route('history.export', request()->query()) }}" class="min-h-[44px] px-4 py-2.5 text-sm">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 3v9m0 0l-3.5-3.5M10 12l3.5-3.5M4 14v2a1 1 0 001 1h10a1 1 0 001-1v-2" />
                </svg>
                <span>Unduh Excel</span>
            </x-action-button>
        </header>

        <!-- Live Plant Monitoring Section -->
        <section class="mb-6 rounded-2xl border border-accent/70 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="live-monitoring-title">
            <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2.5">
                    <h2 id="live-monitoring-title" class="text-lg font-bold tracking-tight text-slate-950 sm:text-xl">Monitoring Mesin Aktif (Live)</h2>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-accent bg-background/30 px-2.5 py-0.5 text-xs font-bold text-secondary">
                        <span class="h-2 w-2 rounded-full bg-secondary"></span>
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
                            <span class="rounded-md bg-background/30 px-2 py-0.5 text-xs font-semibold text-secondary" x-text="item.area"></span>
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

        <x-report-filters
            :action="route('history.index')"
            :reset-url="route('history.index')"
            :areas="$areas"
            :machines="$machines"
            label="Filter Riwayat Produksi"
            submit-label="Cari"
            loading-text="Memuat riwayat..."
            class="mb-6"
        />

        <!-- Panel Tabel Riwayat Produksi -->
        <section class="overflow-hidden rounded-2xl border border-accent/70 bg-white shadow-sm" aria-labelledby="oee-history-title">
            <div class="flex flex-col gap-1 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 id="oee-history-title" class="text-base font-bold text-slate-950">Riwayat Produksi</h2>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">Pilih Lihat Detail untuk melihat hasil produksi, komponen OEE, dan kendala sesi.</p>
                </div>
                <span class="self-start rounded-lg bg-background/30 px-2.5 py-1 text-xs font-bold text-secondary tabular-nums sm:self-auto">{{ $completedSessions->total() }} Sesi</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Waktu Selesai</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Identitas Sesi</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Output Produksi</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">OEE</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Kendala Produksi</th>
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
                                $sessionDurationSeconds = $session->operating_time_seconds !== null
                                    ? (int) $session->operating_time_seconds
                                    : (($session->start_time && $session->end_time)
                                        ? max(0, $session->start_time->diffInSeconds($session->end_time) - $totalDowntimeSeconds)
                                        : null);
                                $sessionDurationMinutesText = $sessionDurationSeconds !== null
                                    ? rtrim(rtrim(number_format($sessionDurationSeconds / 60, 1, ',', '.'), '0'), ',') . ' menit'
                                    : '-';
                                $detailUrl = route('history.show', array_merge(['productionSession' => $session->id], $filterQuery));
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-5 py-4 align-top sm:px-6 tabular-nums">
                                    <div class="text-sm font-bold text-slate-900">{{ $session->end_time?->timezone('Asia/Jakarta')->format('d M Y') ?? '-' }}</div>
                                    <div class="mt-0.5 text-xs font-medium text-slate-500">{{ $session->end_time?->timezone('Asia/Jakarta')->format('H:i:s') ?? '-' }} WIB</div>
                                    <div class="mt-1 text-[11px] font-medium text-slate-500">Durasi: {{ $sessionDurationMinutesText }}</div>
                                </td>
                                <td class="px-5 py-4 align-top sm:px-6">
                                    <div class="text-sm font-bold text-slate-900">{{ $session->machine?->machine_name ?? '-' }}</div>
                                    <div class="mt-0.5 text-xs font-medium text-slate-500">{{ $session->machine?->area?->area_name ?? '-' }} · {{ $session->product?->product_name ?? '-' }}</div>
                                    <div class="mt-1 text-xs font-semibold text-secondary">Nomor Batch: {{ $session->batch_number ?? '-' }} · Operator: {{ $session->operator_name ?? '-' }}</div>
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
                                            <span class="inline-flex whitespace-nowrap items-center rounded-md border border-red-200 bg-red-50 px-2 py-0.5 font-bold text-red-700 tabular-nums">
                                                {{ $downtimeCount }} kendala · {{ $totalDowntimeMinutesText }}
                                            </span>
                                        @else
                                            <span class="inline-flex whitespace-nowrap items-center rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 font-bold text-emerald-700">
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
                                    <x-action-button href="{{ $detailUrl }}" variant="secondary" class="min-h-[44px] px-3.5 py-2 text-xs">
                                        <span>Lihat Detail</span>
                                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 5.5L12 10l-4.5 4.5" />
                                        </svg>
                                    </x-action-button>
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

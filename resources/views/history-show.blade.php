<x-app-layout>
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
        $downtimeCount = $session->downtimeLogs->count();
        $totalDowntimeSeconds = (int) $session->downtimeLogs->sum('duration_seconds');
        $totalDowntimeMinutesText = rtrim(rtrim(number_format($totalDowntimeSeconds / 60, 1, ',', '.'), '0'), ',') . ' menit';
        $sessionDurationSeconds = ($session->start_time && $session->end_time)
            ? max(0, $session->start_time->diffInSeconds($session->end_time))
            : null;
        $sessionDurationMinutesText = $sessionDurationSeconds !== null
            ? rtrim(rtrim(number_format($sessionDurationSeconds / 60, 1, ',', '.'), '0'), ',') . ' menit'
            : '-';
        $operatingMinutesText = $session->operating_time_seconds !== null
            ? rtrim(rtrim(number_format($session->operating_time_seconds / 60, 1, ',', '.'), '0'), ',') . ' menit'
            : '-';
        $plannedMinutesText = $session->planned_time_minutes !== null
            ? number_format((int) $session->planned_time_minutes, 0, ',', '.') . ' menit'
            : '-';
        $idealCycleText = $session->ideal_cycle_time_minutes_per_unit !== null
            ? rtrim(rtrim(number_format((float) $session->ideal_cycle_time_minutes_per_unit, 10, ',', '.'), '0'), ',') . ' menit/' . $outputUnit
            : ($session->ideal_cycle_time_hours_per_kg !== null
                ? rtrim(rtrim(number_format((float) $session->ideal_cycle_time_hours_per_kg * 60, 2, ',', '.'), '0'), ',') . ' menit/kg'
                : '-');
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-brand-700">Detail Sesi Produksi</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                    {{ $session->machine?->machine_name ?? '-' }}
                </h1>
                <p class="mt-1 text-sm font-medium text-slate-600">
                    {{ $session->machine?->area?->area_name ?? '-' }} · {{ $session->product?->product_name ?? '-' }} · Nomor Batch: <span class="font-bold text-slate-900">{{ $session->batch_number ?? '-' }}</span> · Operator: <span class="font-bold text-slate-900">{{ $session->operator_name ?? '-' }}</span>
                </p>
            </div>

            <a
                href="{{ $backUrl }}"
                class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-800 shadow-sm transition-colors hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2"
            >
                <svg class="h-4 w-4 shrink-0 text-slate-600" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12.5 14.5L8 10l4.5-4.5" />
                </svg>
                <span>Kembali ke Riwayat</span>
            </a>
        </header>

        @if(count($warnings))
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-800" role="status">
                {{ implode(' ', $warnings) }}
            </div>
        @elseif(!$hasOeeSnapshot)
            <div class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm font-medium text-slate-600" role="status">
                Data OEE sesi ini belum lengkap.
            </div>
        @endif

        <div class="mb-6 grid gap-6 lg:grid-cols-3">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="detail-oee-title">
                <h2 id="detail-oee-title" class="text-sm font-bold uppercase tracking-wider text-slate-600">Komponen OEE</h2>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-xs tabular-nums">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                        <dt class="font-semibold text-slate-500">Availability</dt>
                        <dd class="mt-1 text-lg font-bold text-slate-900">
                            {{ $session->availability_percent !== null ? number_format((float) $session->availability_percent, 2, ',', '.') . '%' : '-' }}
                        </dd>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                        <dt class="font-semibold text-slate-500">Quality</dt>
                        <dd class="mt-1 text-lg font-bold text-slate-900">
                            {{ $session->quality_percent !== null ? number_format((float) $session->quality_percent, 2, ',', '.') . '%' : '-' }}
                        </dd>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                        <dt class="font-semibold text-slate-500">Performance</dt>
                        <dd class="mt-1 text-lg font-bold text-slate-900">
                            {{ $session->performance_percent !== null ? number_format((float) $session->performance_percent, 2, ',', '.') . '%' : '-' }}
                        </dd>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                        <dt class="font-semibold text-slate-500">Total OEE</dt>
                        <dd class="mt-1 text-lg font-bold {{ count($warnings) ? 'text-amber-700' : 'text-emerald-700' }}">
                            {{ $session->oee_percent !== null ? number_format((float) $session->oee_percent, 2, ',', '.') . '%' : '-' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="detail-output-title">
                <h2 id="detail-output-title" class="text-sm font-bold uppercase tracking-wider text-slate-600">Output &amp; Standar Produksi</h2>
                <dl class="mt-4 grid grid-cols-2 gap-4 text-xs tabular-nums">
                    <div>
                        <dt class="font-semibold text-slate-500">{{ $hasGenericOutput ? 'Total Output' : 'Output Aktual' }}</dt>
                        <dd class="mt-1 text-base font-bold text-slate-900">{{ $totalOutputText }}</dd>
                    </div>
                    @if($hasGenericOutput)
                        <div>
                            <dt class="font-semibold text-slate-500">Reject</dt>
                            <dd class="mt-1 text-base font-bold text-red-700">{{ $rejectOutputText }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">Good Output</dt>
                            <dd class="mt-1 text-base font-bold text-emerald-700">{{ $goodOutputText }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="font-semibold text-slate-500">Output Teoritis</dt>
                        <dd class="mt-1 text-base font-bold text-slate-900">{{ $theoreticalOutputText }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Selisih Output</dt>
                        <dd class="mt-1 text-sm font-bold {{ $diffOutputValue !== null && $diffOutputValue > 0 ? 'text-amber-700' : 'text-slate-900' }}">{{ $diffOutputText }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Siklus Ideal</dt>
                        <dd class="mt-1 text-sm font-bold text-slate-900">{{ $idealCycleText }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Nomor Batch</dt>
                        <dd class="mt-1 text-sm font-bold text-slate-900">{{ $session->batch_number ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Operator</dt>
                        <dd class="mt-1 text-sm font-bold text-slate-900">{{ $session->operator_name ?? '-' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="detail-time-title">
                <h2 id="detail-time-title" class="text-sm font-bold uppercase tracking-wider text-slate-600">Waktu &amp; Durasi Sesi</h2>
                <dl class="mt-4 grid grid-cols-2 gap-4 text-xs tabular-nums">
                    <div>
                        <dt class="font-semibold text-slate-500">Waktu Mulai</dt>
                        <dd class="mt-1 font-bold text-slate-900">{{ $session->start_time?->timezone('Asia/Jakarta')->format('d M Y H:i:s') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Waktu Selesai</dt>
                        <dd class="mt-1 font-bold text-slate-900">{{ $session->end_time?->timezone('Asia/Jakarta')->format('d M Y H:i:s') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Durasi Sesi</dt>
                        <dd class="mt-1 font-bold text-slate-900">{{ $sessionDurationMinutesText }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Waktu Rencana</dt>
                        <dd class="mt-1 font-bold text-slate-900">{{ $plannedMinutesText }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Waktu Operasi Bersih</dt>
                        <dd class="mt-1 font-bold text-slate-900">{{ $operatingMinutesText }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Total Downtime</dt>
                        <dd class="mt-1 font-bold text-slate-900">{{ $downtimeCount }} kejadian ({{ $totalDowntimeMinutesText }})</dd>
                    </div>
                </dl>
            </section>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="detail-downtime-title">
            <div class="flex flex-col gap-1 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <h2 id="detail-downtime-title" class="text-base font-bold text-slate-950">Rincian Kendala &amp; Downtime Sesi</h2>
                <span class="self-start rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 tabular-nums sm:self-auto">
                    Total: {{ $downtimeCount }} kejadian · {{ $totalDowntimeMinutesText }}
                </span>
            </div>

            @if($session->downtimeLogs->isEmpty())
                <div class="px-6 py-10 text-center text-sm font-medium text-slate-500">
                    Sesi ini selesai tanpa kendala yang tercatat.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Waktu Stop</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Waktu Jalan Kembali</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Kategori &amp; Perbaikan</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Pelaksana</th>
                                <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500 sm:px-6">Durasi &amp; Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($session->downtimeLogs as $log)
                                <tr class="transition-colors hover:bg-slate-50/80">
                                    <td class="whitespace-nowrap px-5 py-4 sm:px-6 tabular-nums">
                                        <div class="text-sm font-bold text-slate-900">{{ $log->stop_time->timezone('Asia/Jakarta')->format('d M Y') }}</div>
                                        <div class="mt-0.5 text-xs font-medium text-slate-500">{{ $log->stop_time->timezone('Asia/Jakarta')->format('H:i:s') }} WIB</div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 sm:px-6 tabular-nums">
                                        @if($log->go_time)
                                            <div class="text-sm font-bold text-slate-900">{{ $log->go_time->timezone('Asia/Jakarta')->format('d M Y') }}</div>
                                            <div class="mt-0.5 text-xs font-medium text-slate-500">{{ $log->go_time->timezone('Asia/Jakarta')->format('H:i:s') }} WIB</div>
                                        @else
                                            <span class="text-sm font-semibold text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 sm:px-6">
                                        <span class="inline-flex items-center rounded-md border border-red-200 bg-red-50 px-2 py-0.5 text-xs font-bold text-red-700">
                                            {{ $log->downtimeCategory?->category_name ?? '-' }}
                                        </span>
                                        <div class="mt-1.5 text-sm font-medium text-slate-800">{{ $log->repair_action ?? '-' }}</div>
                                        <div class="mt-1 text-xs font-medium text-slate-500">Komponen: {{ $log->replaced_component ?? 'Tidak ada' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-800 sm:px-6">
                                        {{ $log->personnel?->name ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right sm:px-6 tabular-nums">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>

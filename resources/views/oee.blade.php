<x-app-layout>
    @php
        $formatPercent = fn ($value) => $value === null
            ? '-'
            : rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',') . '%';
        $remainingGauge = 100 - $oeeSummary['gaugeOee'];
        $metrics = [
            ['label' => 'Availability', 'value' => $oeeSummary['averageAvailability'], 'description' => 'Rata-rata waktu mesin tersedia untuk produksi.'],
            ['label' => 'Quality', 'value' => $oeeSummary['averageQuality'], 'description' => 'Rata-rata output baik dibandingkan total output.'],
            ['label' => 'Performance', 'value' => $oeeSummary['averagePerformance'], 'description' => 'Rata-rata kecepatan aktual terhadap standar produksi.'],
        ];
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <header class="mb-6">
            <p class="text-sm font-semibold text-secondary">Analisis Kinerja Produksi</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Analisis OEE</h1>
            <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-600">Rata-rata seluruh sesi hasil filter. Pilihan mesin digunakan untuk mempersempit data.</p>
        </header>

        <x-report-filters
            :action="route('oee.index')"
            :reset-url="route('oee.index')"
            :areas="$areas"
            :machines="$machines"
            label="Filter Analisis OEE"
            submit-label="Terapkan"
            loading-text="Menghitung OEE..."
            class="mb-6"
        />

        <section class="overflow-hidden rounded-2xl border border-accent/70 bg-white shadow-sm" aria-labelledby="oee-average-title">
            <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 id="oee-average-title" class="text-lg font-bold text-slate-950">Rata-rata OEE</h2>
                    <p class="mt-0.5 text-sm font-medium text-slate-600">Rata-rata seluruh sesi hasil filter</p>
                </div>
                <span class="self-start rounded-lg bg-background/40 px-3 py-1.5 text-xs font-bold text-secondary tabular-nums sm:self-auto">
                    {{ $oeeSummary['sessionCount'] }} sesi selesai
                </span>
            </div>

            @if($oeeSummary['sessionCount'] === 0)
                <div class="px-5 py-14 text-center sm:px-6">
                    <svg class="mx-auto h-12 w-12 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V9m5 10V5m5 14v-7m5 7V3" />
                    </svg>
                    <h3 class="mt-4 text-base font-bold text-slate-950">Belum ada data OEE untuk filter ini.</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm font-medium leading-6 text-slate-600">Ubah rentang tanggal, area, atau mesin untuk melihat rata-rata sesi produksi yang telah selesai.</p>
                </div>
            @else
                <div class="grid gap-8 p-5 sm:p-6 lg:grid-cols-2 lg:items-center lg:p-8">
                    <div class="flex flex-col items-center justify-center rounded-2xl bg-background/25 px-5 py-8 sm:py-10">
                        <div class="relative h-56 w-56 sm:h-64 sm:w-64">
                            <svg class="h-full w-full -rotate-90" viewBox="0 0 120 120" role="img" aria-labelledby="oee-donut-title oee-donut-description">
                                <title id="oee-donut-title">Rata-rata OEE {{ $formatPercent($oeeSummary['averageOee']) }}</title>
                                <desc id="oee-donut-description">Perbandingan rata-rata OEE terhadap sisa menuju 100 persen.</desc>
                                <circle cx="60" cy="60" r="48" fill="none" stroke="currentColor" stroke-width="12" class="text-white" />
                                <circle
                                    cx="60"
                                    cy="60"
                                    r="48"
                                    pathLength="100"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="12"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ number_format($oeeSummary['gaugeOee'], 4, '.', '') }} {{ number_format($remainingGauge, 4, '.', '') }}"
                                    class="text-secondary"
                                />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span class="text-4xl font-bold tracking-tight text-primary tabular-nums sm:text-5xl">{{ $formatPercent($oeeSummary['averageOee']) }}</span>
                                <span class="mt-1 text-xs font-bold uppercase tracking-wider text-slate-600">OEE rata-rata</span>
                            </div>
                        </div>
                        @if($oeeSummary['averageOee'] > 100)
                            <p class="mt-5 max-w-sm text-center text-xs font-semibold leading-5 text-amber-700">Nilai rata-rata melebihi 100%. Indikator visual dibatasi sampai 100%.</p>
                        @endif
                    </div>

                    <div class="divide-y divide-slate-200 border-y border-slate-200">
                        @foreach($metrics as $metric)
                            <div class="flex items-start justify-between gap-5 py-5">
                                <div>
                                    <h3 class="text-sm font-bold text-primary">{{ $metric['label'] }}</h3>
                                    <p class="mt-1 text-xs font-medium leading-5 text-slate-600">{{ $metric['description'] }}</p>
                                </div>
                                <span class="shrink-0 text-xl font-bold text-secondary tabular-nums">{{ $formatPercent($metric['value']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>

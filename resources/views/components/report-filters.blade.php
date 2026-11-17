@props([
    'action',
    'resetUrl',
    'areas',
    'machines',
    'label' => 'Filter Laporan',
    'submitLabel' => 'Terapkan',
    'loadingText' => 'Memuat data...',
])

<section {{ $attributes->class('rounded-2xl border border-accent/70 bg-white p-5 shadow-sm sm:p-6')->merge(['aria-label' => $label]) }}>
    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ $action }}" method="GET" x-data="{ loading: false }" @submit="loading = true" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
        <div>
            <label for="start_date" class="block text-xs font-bold uppercase tracking-wider text-slate-600">Dari Tanggal</label>
            <input id="start_date" type="date" name="start_date" value="{{ request('start_date') }}" class="mt-1.5 block min-h-[44px] w-full rounded-xl border-accent text-sm font-medium text-primary focus:border-primary focus:ring-primary">
        </div>
        <div>
            <label for="end_date" class="block text-xs font-bold uppercase tracking-wider text-slate-600">Sampai Tanggal</label>
            <input id="end_date" type="date" name="end_date" value="{{ request('end_date') }}" class="mt-1.5 block min-h-[44px] w-full rounded-xl border-accent text-sm font-medium text-primary focus:border-primary focus:ring-primary">
        </div>
        <div>
            <label for="area_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600">Area Produksi</label>
            <select id="area_id" name="area_id" class="mt-1.5 block min-h-[44px] w-full rounded-xl border-accent text-sm font-medium text-primary focus:border-primary focus:ring-primary">
                <option value="">Semua Area</option>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->area_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="machine_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600">Mesin</label>
            <select id="machine_id" name="machine_id" class="mt-1.5 block min-h-[44px] w-full rounded-xl border-accent text-sm font-medium text-primary focus:border-primary focus:ring-primary">
                <option value="">Semua Mesin</option>
                @foreach($machines as $machine)
                    <option value="{{ $machine->id }}" {{ request('machine_id') == $machine->id ? 'selected' : '' }}>{{ $machine->machine_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
            <x-action-button type="submit" ::disabled="loading" class="min-h-[44px] flex-1 px-4 py-2.5 text-sm">
                <svg x-show="!loading" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17l-3.8-3.8m1.3-4.2a5.5 5.5 0 11-11 0 5.5 5.5 0 0111 0z" />
                </svg>
                <span x-show="!loading">{{ $submitLabel }}</span>
                <span x-cloak x-show="loading" role="status" aria-live="polite">{{ $loadingText }}</span>
            </x-action-button>
            <x-action-button href="{{ $resetUrl }}" variant="secondary" class="min-h-[44px] flex-1 px-4 py-2.5 text-sm">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 10a6 6 0 101.76-4.24L4 7.5M4 4v3.5h3.5" />
                </svg>
                <span>Reset</span>
            </x-action-button>
        </div>
    </form>
</section>

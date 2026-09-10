<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Riwayat & Rekapitulasi MTTR') }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div>
                <h3 class="text-2xl font-black text-gray-800 tracking-tight">Data Historis Kendala</h3>
                <p class="text-sm text-gray-500 font-medium mt-1">Arsip lengkap perbaikan untuk diintegrasikan dengan kalkulasi MTTR.</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('history.export') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-3 rounded-xl shadow-md transition transform active:scale-95 flex items-center space-x-2 border-b-4 border-emerald-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Download Excel</span>
                </a>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="bg-white shadow-sm sm:rounded-xl overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-black uppercase text-gray-600 tracking-wider">Tanggal</th>
                            <th class="px-6 py-4 text-left text-xs font-black uppercase text-gray-600 tracking-wider">Area & Mesin</th>
                            <th class="px-6 py-4 text-left text-xs font-black uppercase text-gray-600 tracking-wider">Kategori</th>
                            <th class="px-6 py-4 text-left text-xs font-black uppercase text-gray-600 tracking-wider">Aksi Perbaikan</th>
                            <th class="px-6 py-4 text-left text-xs font-black uppercase text-gray-600 tracking-wider">Pelaksana</th>
                            <th class="px-6 py-4 text-left text-xs font-black uppercase text-gray-600 tracking-wider">Durasi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100 text-sm">
                        @forelse($histories as $item)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 font-medium">{{ \Carbon\Carbon::parse($item->stop_time)->format('d M Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-gray-900 font-bold">{{ optional(optional($item->productionSession)->machine)->machine_name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ optional(optional($item->productionSession)->machine)->area->area_name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ optional($item->downtimeCategory)->category_name ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-indigo-700 font-extrabold">{{ $item->repair_action }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ optional($item->personnel)->name ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 font-bold rounded bg-red-50 text-red-700 border border-red-200">
                                    {{ round($item->duration_seconds / 60, 2) }} m
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400 font-medium">Data arsip belum tersedia.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                {{ $histories->links() }}
            </div>
        </div>

    </div>
</x-app-layout>
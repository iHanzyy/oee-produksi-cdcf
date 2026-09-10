<x-app-layout>
    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Section -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-3xl font-black text-slate-800 tracking-tight">Rekap & Histori Downtime</h2>
                <p class="text-slate-500 text-sm mt-1 font-medium">Filter dan pantau riwayat kendala mesin untuk pelaporan OEE.</p>
            </div>
            
            <!-- Tombol Download Excel dipindah ke atas agar lebih mudah diakses -->
            <div class="mt-4 md:mt-0">
                <a href="{{ route('history.export', request()->query()) }}" class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    UNDUH EXCEL
                </a>
            </div>
        </div>

        <!-- Panel Filter Pencarian -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
            
            <form action="{{ route('history.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-5 items-end">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition duration-150 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition duration-150 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Area Produksi</label>
                    <select name="area_id" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition duration-150 text-sm">
                        <option value="">-- Semua Area --</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}" {{ request('area_id') == $area->id ? 'selected' : '' }}>{{ $area->area_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Pilih Mesin</label>
                    <select name="machine_id" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition duration-150 text-sm">
                        <option value="">-- Semua Mesin --</option>
                        @foreach($machines as $machine)
                            <option value="{{ $machine->id }}" {{ request('machine_id') == $machine->id ? 'selected' : '' }}>{{ $machine->machine_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex space-x-3">
                    <button type="submit" class="w-full flex justify-center items-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg shadow transition duration-150">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        CARI
                    </button>
                    <a href="{{ route('history.index') }}" class="w-full flex justify-center items-center bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 rounded-lg transition duration-150 border border-slate-300">
                        RESET
                    </a>
                </div>
            </form>
        </div>

        <!-- Panel Tabel Data -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-widest">
                    Hasil Pencarian <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full ml-2 text-xs">{{ $logs->total() }} Data</span>
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Waktu Stop</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Area & Mesin</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Detail Perbaikan</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Pelaksana</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-wider">Total Durasi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @forelse($logs as $log)
                        <tr class="hover:bg-slate-50 transition duration-150 group">
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm font-bold text-slate-900">{{ \Carbon\Carbon::parse($log->stop_time)->format('d M Y') }}</div>
                                <div class="text-xs font-medium text-slate-500 mt-0.5">{{ \Carbon\Carbon::parse($log->stop_time)->format('H:i:s') }}</div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm font-bold text-slate-800">{{ optional(optional($log->productionSession)->machine)->machine_name ?? '-' }}</div>
                                <div class="text-xs font-medium text-slate-500 mt-0.5">{{ optional(optional($log->productionSession)->machine)->area->area_name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700 mb-2 border border-red-200">
                                    {{ optional($log->downtimeCategory)->category_name ?? '-' }}
                                </span>
                                <div class="text-sm font-medium text-slate-700 line-clamp-2">{{ $log->repair_action ?? '-' }}</div>
                                <div class="text-xs font-semibold text-blue-600 mt-1 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    {{ $log->replaced_component ?? 'Tidak ada' }}
                                </div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-700 flex items-center">
                                    <div class="w-7 h-7 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-bold text-xs mr-2">
                                        {{ substr(optional($log->personnel)->name ?? 'U', 0, 1) }}
                                    </div>
                                    {{ optional($log->personnel)->name ?? '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                @if($log->go_time)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ round($log->duration_seconds / 60, 1) }} Menit
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-red-600 font-bold text-sm animate-pulse">
                                        <span class="w-2 h-2 rounded-full bg-red-600 mr-2"></span> Berlangsung...
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p class="text-slate-500 font-medium text-lg">Tidak ada data histori yang ditemukan.</p>
                                <p class="text-slate-400 text-sm mt-1">Coba sesuaikan filter pencarian Anda di atas.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($logs->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
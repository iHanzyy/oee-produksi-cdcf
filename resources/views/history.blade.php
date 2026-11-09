<x-app-layout>
    <div
        x-data="historyMonitoring({
            initial: @js($activeSessions ?? []),
            url: @js(route('history.active_sessions')),
        })"
        class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
    >
        <!-- Header Section -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-3xl font-black text-slate-800 tracking-tight">Pemantauan & Histori Mesin Pabrik</h2>
                <p class="text-slate-500 text-sm mt-1 font-medium">Monitoring status mesin aktif secara live dan akses riwayat kendala untuk pelaporan OEE.</p>
            </div>
            
            <div class="mt-4 md:mt-0">
                <a href="{{ route('history.export', request()->query()) }}" class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    UNDUH EXCEL
                </a>
            </div>
        </div>

        <!-- Live Plant Monitoring Section (Mesin Sedang Berjalan) -->
        <section class="mb-10 space-y-4" aria-labelledby="live-monitoring-title">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white font-black text-lg shadow-sm">
                        🏭
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 id="live-monitoring-title" class="text-xl font-black text-slate-900 tracking-tight">Monitoring Mesin Aktif (Live)</h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600 animate-ping mr-1"></span> Live
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 font-medium">Status seluruh mesin pabrik yang sedang beroperasi saat ini (sinkron realtime tiap 2 detik).</p>
                    </div>
                </div>

                <!-- Status Counter Badges -->
                <div class="flex flex-wrap items-center gap-2 text-xs sm:text-sm font-bold">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800">
                        <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                        <span x-text="runningCount + ' Berjalan'"></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-red-50 border border-red-200 text-red-800">
                        <span class="h-2 w-2 rounded-full bg-red-600"></span>
                        <span x-text="downtimeCount + ' Kendala'"></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700">
                        <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                        <span x-text="sessions.length + ' Total Aktif'"></span>
                    </span>
                </div>
            </div>

            <!-- Empty State jika tidak ada mesin berjalan -->
            <div x-show="sessions.length === 0" x-cloak class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/70 p-8 text-center">
                <span class="inline-block text-3xl mb-2">⚪</span>
                <h4 class="text-base font-bold text-slate-800">Semua Mesin Standby</h4>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-md mx-auto">Saat ini belum ada sesi produksi yang aktif di pabrik. Semua mesin dalam status standby.</p>
            </div>

            <!-- Grid Kartu Mesin Aktif -->
            <div x-show="sessions.length > 0" x-cloak class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <template x-for="item in sessions" :key="item.id">
                    <div class="rounded-2xl border bg-white p-5 shadow-sm transition-all hover:shadow-md"
                        :class="item.status === 'running' ? 'border-emerald-300 ring-1 ring-emerald-500/10' : 'border-red-300 ring-1 ring-red-500/15'">
                        
                        <!-- Header Kartu: Status Pill & Area -->
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-wider"
                                :class="item.status === 'running' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'">
                                <span class="h-2 w-2 rounded-full" :class="item.status === 'running' ? 'bg-emerald-600 animate-ping' : 'bg-red-600 animate-pulse'"></span>
                                <span x-text="item.status === 'running' ? 'BERJALAN' : 'DOWNTIME'"></span>
                            </span>
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md" x-text="item.area"></span>
                        </div>

                        <!-- Nama Mesin -->
                        <h4 class="mt-3 text-xl font-black text-slate-900 tracking-tight" x-text="item.machine"></h4>

                        <!-- Detail Info -->
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div class="rounded-lg bg-slate-50 p-2 border border-slate-100">
                                <p class="text-[10px] font-bold uppercase text-slate-500">Operator</p>
                                <p class="mt-0.5 font-bold text-slate-900 truncate" x-text="item.operatorName"></p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-2 border border-slate-100">
                                <p class="text-[10px] font-bold uppercase text-slate-500">Produk</p>
                                <p class="mt-0.5 font-bold text-slate-900 truncate" x-text="item.product"></p>
                                <p class="mt-2 text-[10px] font-bold uppercase text-slate-500">Nomor Batch</p>
                                <p class="mt-0.5 font-bold text-slate-900 truncate" x-text="item.batchNumber || '-'"></p>
                            </div>
                        </div>

                        <!-- Timer Berjalan / Downtime -->
                        <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500">Waktu Berjalan:</span>
                            <span class="font-mono text-base font-extrabold text-slate-900" x-text="calcElapsed(item.startTime)"></span>
                        </div>

                        <div x-show="item.status === 'downtime' && item.downtime" class="mt-2 rounded-lg bg-red-50 p-2 border border-red-200 flex items-center justify-between text-xs">
                            <span class="font-bold text-red-800 flex items-center gap-1">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-600 animate-ping"></span>
                                Durasi Downtime:
                            </span>
                            <span class="font-mono font-black text-red-900" x-text="calcElapsed(item.downtime?.stopTime)"></span>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <!-- Divider Arsip Riwayat -->
        <div class="mb-5 flex items-center gap-3">
            <h3 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Arsip & Riwayat Kendala Mesin</h3>
            <div class="flex-1 border-t border-slate-200"></div>
        </div>

        <!-- Panel Filter Pencarian -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>

            @if($errors->any())
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif
            
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
                                <div class="text-sm font-bold text-slate-900">{{ $log->stop_time->timezone('Asia/Jakarta')->format('d M Y') }}</div>
                                <div class="text-xs font-medium text-slate-500 mt-0.5">{{ $log->stop_time->timezone('Asia/Jakarta')->format('H:i:s') }}</div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="text-sm font-bold text-slate-800">{{ optional(optional($log->productionSession)->machine)->machine_name ?? '-' }}</div>
                                <div class="text-xs font-medium text-slate-500 mt-0.5">{{ optional(optional($log->productionSession)->machine)->area->area_name ?? '-' }}</div>
                                <div class="text-xs font-semibold text-indigo-700 mt-1">Nomor Batch: {{ optional($log->productionSession)->batch_number ?? '-' }}</div>
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
                                @if($log->status_label === 'Selesai')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ round($log->duration_seconds / 60, 1) }} Menit · Selesai
                                    </span>
                                @elseif($log->status_label === 'Berlangsung')
                                    <span class="inline-flex items-center text-red-600 font-bold text-sm animate-pulse">
                                        <span class="w-2 h-2 rounded-full bg-red-600 mr-2"></span> Berlangsung
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-sm font-bold text-amber-700">
                                        Data tidak lengkap
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

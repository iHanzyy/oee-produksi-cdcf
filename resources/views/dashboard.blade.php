<x-app-layout>
    <div x-data="{ openResumeModal: false }" class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800 tracking-wide uppercase">Dasbor Kontrol Produksi</h2>
            <div class="text-gray-500 font-mono text-sm" id="realtime-clock"></div>
        </div>

        @if (!$activeSession)
            <!-- Panel Start Awal -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center max-w-2xl mx-auto mt-10">
                <h3 class="text-xl font-bold text-gray-700 mb-6">Mulai Sesi Produksi Baru</h3>
                <form action="{{ route('machine.start') }}" method="POST" class="space-y-5 text-left">
                    @csrf
                    <div>
                        <label class="block text-sm text-gray-600 mb-1 font-bold">Pilih Area Operasional</label>
                        <select name="area_id" required class="w-full border-gray-300 rounded-md focus:ring-blue-500">
                            <option value="">-- Pilih Area --</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->area_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1 font-bold">Nama Operator</label>
                        <input type="text" name="machine_name"required autocomplete="off"
                            class="w-full border-gray-300 rounded-md focus:ring-blue-500"
                            placeholder="Mulai ketik nama Operator...">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1 font-bold">Nama Produk</label>
                        <select name="area_id" required class="w-full border-gray-300 rounded-md focus:ring-blue-500">
                            <option value="">-- Pilih Produk --</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->area_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1 font-bold">Mesin / Line (Ketik untuk
                                mencari)</label>
                            <input type="text" name="machine_name" list="machine-options" required autocomplete="off"
                                class="w-full border-gray-300 rounded-md focus:ring-blue-500"
                                placeholder="Mulai ketik nama mesin...">
                            <datalist id="machine-options">
                                @foreach ($machines as $machine)
                                    <option value="{{ $machine->machine_name }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="pt-4">
                            <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-md shadow-md transition">START
                                PRODUKSI</button>
                        </div>
                </form>
            </div>
        @else
            <!-- Panel Status Mesin -->
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6 flex justify-between items-center border-l-8 {{ $activeSession->status === 'running' ? 'border-green-500' : 'border-red-500' }}">
                <div>
                    <p class="text-sm text-gray-500 uppercase tracking-widest font-bold">
                        {{ $activeSession->machine->area->area_name }}</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $activeSession->machine->machine_name }}</h3>
                </div>
                <div class="text-right flex items-center space-x-6">
                    <div class="text-center bg-gray-50 px-4 py-2 rounded-lg border border-gray-100">
                        <p class="text-xs text-gray-500 uppercase font-bold">Waktu Berjalan</p>
                        <div id="session-timer" class="text-2xl font-mono font-black text-gray-800"
                            data-start="{{ $activeSession->start_time }}">00:00:00</div>
                    </div>
                    @if ($activeSession->status === 'running')
                        <span
                            class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-green-100 text-green-800 animate-pulse border border-green-200">RUNNING
                            NORMAL</span>
                    @else
                        <span
                            class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-red-100 text-red-800 animate-pulse border border-red-200">MESIN
                            BERHENTI</span>
                    @endif
                </div>
            </div>

            <!-- Metrik OEE Visual -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div
                    class="bg-indigo-50 border border-indigo-100 p-4 rounded-xl flex flex-col justify-center items-center shadow-sm">
                    <span class="text-xs font-bold text-indigo-600 uppercase tracking-widest">OEE</span>
                    <p class="text-3xl font-black text-indigo-800 mt-1">76.5%</p>
                </div>
                <div
                    class="bg-blue-50 border border-blue-100 p-4 rounded-xl flex flex-col justify-center items-center shadow-sm">
                    <span class="text-xs font-bold text-blue-600 uppercase tracking-widest">Availability</span>
                    <p class="text-3xl font-black text-blue-800 mt-1">82.1%</p>
                </div>
                <div
                    class="bg-emerald-50 border border-emerald-100 p-4 rounded-xl flex flex-col justify-center items-center shadow-sm">
                    <span class="text-xs font-bold text-emerald-600 uppercase tracking-widest">Performance</span>
                    <p class="text-3xl font-black text-emerald-800 mt-1">94.0%</p>
                </div>
                <div
                    class="bg-purple-50 border border-purple-100 p-4 rounded-xl flex flex-col justify-center items-center shadow-sm">
                    <span class="text-xs font-bold text-purple-600 uppercase tracking-widest">Quality</span>
                    <p class="text-3xl font-black text-purple-800 mt-1">99.2%</p>
                </div>
            </div>

            <!-- Tombol Kontrol Langsung -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                @if ($activeSession->status === 'running')
                    <form action="{{ route('machine.stop') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="w-full bg-red-600 hover:bg-red-700 text-white py-6 rounded-xl text-xl font-black shadow-lg transition transform active:scale-95 border-b-4 border-red-800">
                            STOP MESIN
                        </button>
                    </form>
                @else
                    <button @click="openResumeModal = true"
                        class="w-full bg-green-600 hover:bg-green-700 text-white py-6 rounded-xl text-xl font-black shadow-lg transition transform active:scale-95 border-b-4 border-green-800">
                        START (ISI FORM KENDALA)
                    </button>
                @endif

                <form action="{{ route('machine.end') }}" method="POST"
                    onsubmit="return confirm('Akhiri sesi ini?');">
                    @csrf
                    <button type="submit"
                        class="w-full bg-gray-800 hover:bg-gray-900 text-white py-6 rounded-xl text-xl font-black shadow-lg border-b-4 border-gray-950">END
                        SESSION (SELESAI)</button>
                </form>
            </div>

            <!-- Tabel Histori Log Sesi -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-md font-bold text-gray-800">Catatan Downtime (Sesi Saat Ini)</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">
                                Jam Stop</th>
                            <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">
                                Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">
                                Perbaikan & Komponen</th>
                            <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">
                                Durasi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100 text-sm">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-bold">
                                    {{ \Carbon\Carbon::parse($log->stop_time)->format('H:i:s') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                    {{ optional($log->downtimeCategory)->category_name ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-indigo-700">{{ $log->repair_action ?? '-' }}</div>
                                    <div class="text-xs text-gray-500">Komponen: {{ $log->replaced_component ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($log->go_time)
                                        <span
                                            class="bg-gray-100 text-gray-800 px-2 py-1 rounded font-bold">{{ round($log->duration_seconds / 60, 1) }}
                                            mnt</span>
                                    @else
                                        <span class="text-red-600 font-bold animate-pulse">Sedang Perbaikan...</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400 font-medium">Belum ada
                                    catatan kendala.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Modal Form Resume & Pengisian Kendala -->
            @if ($activeSession->status === 'downtime' && $lastLog)
                <div x-show="openResumeModal" style="display: none;"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm px-4">
                    <div @click.away="openResumeModal = false"
                        class="bg-white w-full max-w-3xl rounded-xl shadow-2xl p-6 overflow-y-auto max-h-[90vh]">
                        <div class="border-b border-gray-200 pb-4 mb-5 flex justify-between items-center">
                            <h3 class="text-2xl font-black text-gray-800">Form Laporan Penyelesaian Kendala</h3>
                            <button @click="openResumeModal = false"
                                class="text-gray-400 hover:text-gray-600 font-bold text-2xl">&times;</button>
                        </div>

                        <!-- Indikator Waktu Real-time Modal -->
                        <div
                            class="bg-red-50 p-4 rounded-lg border border-red-200 mb-6 grid grid-cols-3 gap-4 text-center">
                            <div>
                                <p class="text-xs text-red-600 font-bold uppercase">Jam Berhenti</p>
                                <p class="text-xl font-black text-red-900">
                                    {{ \Carbon\Carbon::parse($lastLog->stop_time)->format('H:i:s') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-red-600 font-bold uppercase">Jam Sekarang</p>
                                <p class="text-xl font-black text-red-900" id="modal-current-time">--:--:--</p>
                            </div>
                            <div>
                                <p class="text-xs text-red-600 font-bold uppercase">Durasi Perbaikan</p>
                                <p class="text-xl font-mono font-black text-white bg-red-600 rounded px-2 shadow-inner inline-block"
                                    id="modal-downtime-duration" data-start="{{ $lastLog->stop_time }}">00:00:00</p>
                            </div>
                        </div>

                        <form action="{{ route('machine.resume') }}" method="POST" class="space-y-5">
                            @csrf
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Kategori Kendala</label>
                                    <select name="category_id" required
                                        class="w-full border-gray-300 rounded-md focus:ring-green-500">
                                        <option value="">-- Pilih Kategori --</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Dilaksanakan Oleh</label>
                                    <select name="personnel_id" required
                                        class="w-full border-gray-300 rounded-md focus:ring-green-500">
                                        <option value="">-- Pilih Pelaksana --</option>
                                        @foreach ($personnels as $person)
                                            <option value="{{ $person->id }}">{{ $person->role }}
                                                ({{ $person->name }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Komponen yang Diganti /
                                    Diperbaiki</label>
                                <input type="text" name="replaced_component" list="component-options" required
                                    autocomplete="off" class="w-full border-gray-300 rounded-md focus:ring-green-500"
                                    placeholder="Ketik atau pilih komponen...">
                                <datalist id="component-options">
                                    <option value="Tidak ada yang diganti"></option>
                                    <option value="Kontaktor / Relay"></option>
                                    <option value="Inverter / VSD"></option>
                                    <option value="Saklar / Push Button"></option>
                                    <option value="Sensor / Limit Switch"></option>
                                    <option value="Tooling / Punch & Die"></option>
                                    <option value="Filter HEPA"></option>
                                    <option value="Diode Bridge Rectifier"></option>
                                    <option value="Valve / WFI System"></option>
                                </datalist>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Keterangan / Aksi Perbaikan
                                    (Detail)</label>
                                <textarea name="repair_action" rows="2" required class="w-full border-gray-300 rounded-md focus:ring-green-500"
                                    placeholder="Jelaskan detail perbaikan..."></textarea>
                            </div>

                            <div class="pt-4 border-t border-gray-200 flex justify-end space-x-3">
                                <button type="button" @click="openResumeModal = false"
                                    class="px-6 py-3 font-bold text-gray-600 bg-gray-100 rounded-md hover:bg-gray-200">Batal
                                    / Tutup</button>
                                <button type="submit"
                                    class="px-8 py-3 font-black text-white bg-green-600 rounded-md hover:bg-green-700 shadow-md">SIMPAN
                                    & START MESIN</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <!-- Script JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Jam Utama
            setInterval(() => {
                document.getElementById('realtime-clock').innerText = new Date().toLocaleTimeString(
                    'id-ID');

                const modalClock = document.getElementById('modal-current-time');
                if (modalClock) modalClock.innerText = new Date().toLocaleTimeString('id-ID');
            }, 1000);

            // Timer Durasi Sesi Utama
            const sessionTimer = document.getElementById('session-timer');
            if (sessionTimer) {
                const startStr = sessionTimer.getAttribute('data-start');
                const startTime = new Date(startStr.replace(/-/g, "/"));
                setInterval(() => {
                    const diff = Math.floor((new Date() - startTime) / 1000);
                    if (diff > 0) {
                        const h = String(Math.floor(diff / 3600)).padStart(2, '0');
                        const m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
                        const s = String(diff % 60).padStart(2, '0');
                        sessionTimer.innerText = `${h}:${m}:${s}`;
                    }
                }, 1000);
            }

            // Timer Durasi Downtime di Modal
            const downtimeTimer = document.getElementById('modal-downtime-duration');
            if (downtimeTimer) {
                const startTimeStr = downtimeTimer.getAttribute('data-start');
                const startTime = new Date(startTimeStr.replace(/-/g, "/"));
                setInterval(() => {
                    const diff = Math.floor((new Date() - startTime) / 1000);
                    if (diff > 0) {
                        const h = String(Math.floor(diff / 3600)).padStart(2, '0');
                        const m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
                        const s = String(diff % 60).padStart(2, '0');
                        downtimeTimer.innerText = `${h}:${m}:${s}`;
                    }
                }, 1000);
            }
        });
    </script>
</x-app-layout>

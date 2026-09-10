<?php

namespace App\Http\Controllers;

use App\Models\DowntimeLog;
use App\Models\Area;
use App\Models\Machine;
use Illuminate\Http\Request;
use App\Exports\DowntimeHistoryExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        // Mulai query dasar dengan memanggil semua relasi tabel
        $query = DowntimeLog::with(['productionSession.machine.area', 'downtimeCategory', 'personnel']);

        // 1. Filter Rentang Tanggal (Bulan/Hari)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('stop_time', [$start, $end]);
        }

        // 2. Filter Area
        if ($request->filled('area_id')) {
            $query->whereHas('productionSession.machine', function($q) use ($request) {
                $q->where('area_id', $request->area_id);
            });
        }

        // 3. Filter Nama Mesin
        if ($request->filled('machine_id')) {
            $query->whereHas('productionSession', function($q) use ($request) {
                $q->where('machine_id', $request->machine_id);
            });
        }

        // withQueryString() memastikan filter tidak hilang saat pindah halaman (pagination)
        $logs = $query->latest()->paginate(15)->withQueryString(); 

        $areas = Area::all();
        $machines = Machine::all();

        return view('history', compact('logs', 'areas', 'machines'));
    }

    public function exportExcel(Request $request)
    {
        // Kirim data filter ke modul Export agar Excel yang diunduh sesuai dengan layar
        return Excel::download(new DowntimeHistoryExport($request->all()), 'Laporan_Downtime_OEE.xlsx');
    }
}
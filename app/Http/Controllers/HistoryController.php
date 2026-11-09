<?php

namespace App\Http\Controllers;

use App\Exports\DowntimeHistoryExport;
use App\Http\Requests\HistoryFilterRequest;
use App\Models\Area;
use App\Models\DowntimeLog;
use App\Models\Machine;
use App\Models\ProductionSession;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HistoryController extends Controller
{
    public function index(HistoryFilterRequest $request)
    {
        $filters = $request->filters();
        $logs = DowntimeLog::query()->forHistory($filters)->paginate(15)->withQueryString();
        $areas = Area::query()->orderBy('area_name')->get();
        $machines = Machine::query()
            ->when(isset($filters['area_id']), fn ($query) => $query->where('area_id', $filters['area_id']))
            ->orderBy('machine_name')
            ->get();

        $activeSessions = ProductionSession::with(['machine.area', 'product', 'downtimeLogs'])
            ->active()
            ->oldest('start_time')
            ->get()
            ->map(fn (ProductionSession $session) => $this->sessionPayload($session))
            ->values();

        return view('history', compact('logs', 'areas', 'machines', 'activeSessions'));
    }

    public function activeSessions(): JsonResponse
    {
        $activeSessions = ProductionSession::with(['machine.area', 'product', 'downtimeLogs'])
            ->active()
            ->oldest('start_time')
            ->get()
            ->map(fn (ProductionSession $session) => $this->sessionPayload($session))
            ->values();

        return response()->json([
            'sessions' => $activeSessions,
            'serverNow' => now('Asia/Jakarta')->toIso8601String(),
        ]);
    }

    private function sessionPayload(ProductionSession $session): array
    {
        $activeDowntime = $session->downtimeLogs->firstWhere('go_time', null);

        return [
            'id' => $session->id,
            'status' => $session->status,
            'operatorName' => $session->operator_name,
            'batchNumber' => $session->batch_number,
            'startTime' => $session->start_time?->copy()->timezone('Asia/Jakarta')->toIso8601String(),
            'area' => $session->machine->area->area_name,
            'product' => $session->product?->product_name ?? 'Produk',
            'machine' => $session->machine->machine_name,
            'downtime' => $activeDowntime ? [
                'id' => $activeDowntime->id,
                'stopTime' => Carbon::parse($activeDowntime->stop_time)->timezone('Asia/Jakarta')->toIso8601String(),
            ] : null,
        ];
    }

    public function exportExcel(HistoryFilterRequest $request): BinaryFileResponse
    {
        return Excel::download(new DowntimeHistoryExport($request->filters()), 'Laporan_Downtime_OEE.xlsx');
    }
}

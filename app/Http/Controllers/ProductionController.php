<?php

namespace App\Http\Controllers;

use App\Models\DowntimeLog;
use App\Models\ProductionSession;
use App\Models\DowntimeCategory;
use App\Models\Personnel;
use App\Models\Machine;
use App\Models\Area;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProductionController extends Controller
{
    public function index()
    {
        $activeSession = ProductionSession::with('machine.area', 'operator')->whereIn('status', ['running', 'downtime'])->latest()->first();
        
        $logs = DowntimeLog::with(['productionSession.machine.area', 'downtimeCategory', 'personnel'])
            ->latest()
            ->paginate(10);
            
        $lastLog = null;
        if ($activeSession && $activeSession->status === 'downtime') {
            $lastLog = DowntimeLog::where('production_session_id', $activeSession->id)
                ->whereNull('go_time')->latest()->first();
        }

        $categories = DowntimeCategory::all();
        $personnels = Personnel::all();
        $machines = Machine::all(); // Untuk opsi ketik
        $areas = Area::all(); // Untuk opsi area

        return view('dashboard', compact('logs', 'categories', 'personnels', 'machines', 'areas', 'activeSession', 'lastLog'));
    }

    public function startSession(Request $request)
    {
        // Cari mesin berdasarkan nama yang diketik, jika tidak ada buat baru sesuai Area
        $machine = Machine::firstOrCreate(
            ['machine_name' => $request->machine_name],
            ['area_id' => $request->area_id]
        );

        // Abaikan input operator dari form, otomatis tugaskan ke operator default (agar tidak error DB)
        $defaultOperator = Personnel::where('role', 'Operator Produksi')->first();

        ProductionSession::create([
            'machine_id' => $machine->id,
            'operator_id' => $defaultOperator->id ?? 1,
            'start_time' => Carbon::now(),
            'status' => 'running',
            'planned_time_minutes' => 480
        ]);
        return redirect()->back();
    }

    public function stopMachine(Request $request)
    {
        $activeSession = ProductionSession::where('status', 'running')->latest()->first();
        if ($activeSession) {
            $activeSession->update(['status' => 'downtime']);
            DowntimeLog::create([
                'production_session_id' => $activeSession->id,
                'stop_time' => Carbon::now()
            ]);
        }
        return redirect()->back();
    }

    public function resumeMachine(Request $request)
    {
        $activeSession = ProductionSession::where('status', 'downtime')->latest()->first();
        if ($activeSession) {
            $activeSession->update(['status' => 'running']);
            $lastLog = DowntimeLog::where('production_session_id', $activeSession->id)
                ->whereNull('go_time')->latest()->first();

            if ($lastLog) {
                $goTime = Carbon::now();
                $stopTime = Carbon::parse($lastLog->stop_time);
                $duration = $stopTime->diffInSeconds($goTime);
                
                $lastLog->update([
                    'go_time' => $goTime,
                    'duration_seconds' => $duration,
                    'downtime_category_id' => $request->category_id,
                    'personnel_id' => $request->personnel_id,
                    'replaced_component' => $request->replaced_component,
                    'repair_action' => $request->repair_action
                ]);
            }
        }
        return redirect()->back();
    }

    public function endSession(Request $request)
    {
        $activeSession = ProductionSession::whereIn('status', ['running', 'downtime'])->latest()->first();
        if ($activeSession) {
            $activeSession->update(['status' => 'completed', 'end_time' => Carbon::now()]);
        }
        return redirect()->back();
    }
}
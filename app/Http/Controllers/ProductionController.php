<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureDeviceToken;
use App\Http\Requests\CompleteProductionSessionRequest;
use App\Http\Requests\ResumeProductionSessionRequest;
use App\Http\Requests\StartProductionSessionRequest;
use App\Models\Area;
use App\Models\DowntimeCategory;
use App\Models\DowntimeLog;
use App\Models\Machine;
use App\Models\OeeStandard;
use App\Models\Personnel;
use App\Models\ProductionSession;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $controlledSession = ProductionSession::with(['machine.area', 'product'])
            ->where('controller_token_hash', $this->controllerHash($request))
            ->latest()
            ->first();
        $categories = DowntimeCategory::all();
        $personnels = Personnel::all();
        $areas = Area::with(['activeProducts', 'activeMachines'])->get();
        $dashboardState = [
            'data' => $controlledSession ? $this->sessionPayload($controlledSession) : null,
            'isController' => $controlledSession !== null,
            'serverNow' => now('Asia/Jakarta')->toIso8601String(),
        ];
        $areaOptions = $areas->map(fn (Area $area) => [
            'id' => $area->id,
            'name' => $area->area_name,
            'products' => $area->activeProducts->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->product_name,
            ])->values(),
            'machines' => $area->activeMachines->map(fn ($machine) => [
                'id' => $machine->id,
                'name' => $machine->machine_name,
            ])->values(),
        ])->values();

        return view('dashboard', compact('dashboardState', 'areaOptions', 'categories', 'personnels', 'areas'));
    }

    public function startSession(StartProductionSessionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $controllerHash = $this->controllerHash($request);

        $session = DB::transaction(function () use ($validated, $controllerHash): ProductionSession {
            abort_if(
                ProductionSession::query()->where('controller_token_hash', $controllerHash)->exists(),
                409,
                'Browser ini masih mengontrol sesi lain.',
            );

            $machine = Machine::query()->lockForUpdate()->findOrFail($validated['machine_id']);

            abort_unless(
                OeeStandard::query()
                    ->where('product_id', $validated['product_id'])
                    ->where('machine_id', $machine->id)
                    ->where('is_active', true)
                    ->where('theoretical_output_kg', '>', 0)
                    ->where('ideal_cycle_time_hours_per_kg', '>', 0)
                    ->exists(),
                422,
                'Standar OEE belum tersedia untuk produk dan mesin ini.',
            );

            abort_if(
                ProductionSession::active()->where('machine_id', $machine->id)->exists(),
                409,
                'Mesin sedang digunakan.',
            );

            return ProductionSession::create([
                'machine_id' => $machine->id,
                'product_id' => $validated['product_id'],
                'batch_number' => $validated['batch_number'],
                'operator_id' => null,
                'operator_name' => $validated['operator_name'],
                'controller_token_hash' => $controllerHash,
                'start_time' => now(),
                'status' => 'running',
            ]);
        });

        return response()->json([
            'data' => $this->sessionPayload($session),
            'isController' => true,
            'serverNow' => now('Asia/Jakarta')->toIso8601String(),
        ], 201);
    }

    public function takeover(Request $request, ProductionSession $productionSession): JsonResponse
    {
        $controllerHash = $this->controllerHash($request);

        $session = DB::transaction(function () use ($productionSession, $controllerHash): ProductionSession {
            $lockedSession = ProductionSession::query()->lockForUpdate()->findOrFail($productionSession->id);

            abort_unless(in_array($lockedSession->status, ['running', 'downtime'], true), 409, 'Sesi sudah tidak aktif.');
            abort_if(
                ProductionSession::query()
                    ->where('controller_token_hash', $controllerHash)
                    ->whereKeyNot($lockedSession->id)
                    ->exists(),
                409,
                'Browser ini masih mengontrol sesi lain.',
            );

            $lockedSession->update(['controller_token_hash' => $controllerHash]);

            return $lockedSession;
        });

        return response()->json([
            'data' => $this->sessionPayload($session),
            'isController' => true,
            'serverNow' => now('Asia/Jakarta')->toIso8601String(),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['nullable', 'integer'],
        ], [
            'session_id.integer' => 'ID sesi produksi tidak valid.',
        ]);
        $controllerHash = $this->controllerHash($request);

        $session = isset($validated['session_id'])
            ? ProductionSession::find($validated['session_id'])
            : ProductionSession::where('controller_token_hash', $controllerHash)->latest()->first();

        $isController = $session !== null
            && $session->controller_token_hash !== null
            && hash_equals($session->controller_token_hash, $controllerHash);

        // ponytail: non-controller browsers only see active sessions for takeover; completed sessions stay private to controller/SPV.
        if ($session !== null && ! $isController && ! in_array($session->status, ['running', 'downtime'], true)) {
            $session = null;
        }

        return response()->json([
            'data' => $session ? $this->sessionPayload($session) : null,
            'isController' => $isController,
            'serverNow' => now('Asia/Jakarta')->toIso8601String(),
        ]);
    }

    public function stopMachine(Request $request, ProductionSession $productionSession): JsonResponse
    {
        $session = DB::transaction(function () use ($request, $productionSession): ProductionSession {
            $lockedSession = ProductionSession::query()->lockForUpdate()->findOrFail($productionSession->id);
            $this->assertController($lockedSession, $request);
            abort_unless($lockedSession->status === 'running', 409, 'Mesin sedang tidak berjalan.');

            // Proteksi backend: cegah stop mesin dalam waktu 2 detik setelah resume untuk mencegah loop downtime
            if (! app()->runningUnitTests()) {
                $lastGoLog = DowntimeLog::query()
                    ->where('production_session_id', $lockedSession->id)
                    ->whereNotNull('go_time')
                    ->latest('id')
                    ->first();

                if ($lastGoLog && $lastGoLog->go_time) {
                    $secondsSinceResume = Carbon::parse($lastGoLog->go_time)->diffInSeconds(now());
                    abort_if(
                        $secondsSinceResume < 3,
                        429,
                        'Mesin baru saja dinyalakan. Tunggu beberapa detik sebelum menghentikan mesin lagi.'
                    );
                }

                if ($lockedSession->start_time) {
                    $secondsSinceStart = Carbon::parse($lockedSession->start_time)->diffInSeconds(now());
                    abort_if(
                        $secondsSinceStart < 2,
                        429,
                        'Mesin baru saja dimulai. Tunggu beberapa detik sebelum menghentikan mesin.'
                    );
                }
            }

            DowntimeLog::create([
                'production_session_id' => $lockedSession->id,
                'stop_time' => now(),
            ]);
            $lockedSession->update(['status' => 'downtime']);

            return $lockedSession;
        });

        return $this->sessionResponse($session);
    }

    public function resumeMachine(ResumeProductionSessionRequest $request, ProductionSession $productionSession): JsonResponse
    {
        $validated = $request->validated();
        $session = DB::transaction(function () use ($request, $productionSession, $validated): ProductionSession {
            $lockedSession = ProductionSession::query()->lockForUpdate()->findOrFail($productionSession->id);
            $this->assertController($lockedSession, $request);
            abort_unless($lockedSession->status === 'downtime', 409, 'Mesin sedang berjalan, tidak dalam kondisi downtime.');

            $log = DowntimeLog::query()
                ->where('production_session_id', $lockedSession->id)
                ->whereNull('go_time')
                ->lockForUpdate()
                ->first();
            abort_if($log === null, 409, 'Data kendala mesin tidak ditemukan. Muat ulang halaman lalu coba lagi.');

            $goTime = now();
            $log->update([
                'go_time' => $goTime,
                'duration_seconds' => (int) Carbon::parse($log->stop_time)->diffInSeconds($goTime),
                'downtime_category_id' => $validated['category_id'],
                'personnel_id' => $validated['personnel_id'],
                'replaced_component' => $validated['replaced_component'],
                'repair_action' => $validated['repair_action'],
            ]);
            $lockedSession->update(['status' => 'running']);

            return $lockedSession;
        });

        return $this->sessionResponse($session);
    }

    public function rejectLegacyCompletion(): JsonResponse
    {
        return response()->json([
            'message' => 'Halaman tablet masih memakai versi lama. Muat ulang halaman sebelum melanjutkan.',
        ], 409);
    }

    public function endSession(CompleteProductionSessionRequest $request, ProductionSession $productionSession): JsonResponse
    {
        abort_unless(
            $request->boolean('confirmed'),
            422,
            'Konfirmasi selesai produksi tidak diterima. Muat ulang halaman lalu coba lagi.'
        );

        $validated = $request->validated();

        $session = DB::transaction(function () use ($request, $productionSession, $validated): ProductionSession {
            $lockedSession = ProductionSession::query()->lockForUpdate()->findOrFail($productionSession->id);
            $this->assertController($lockedSession, $request);
            abort_if($lockedSession->status === 'downtime', 409, 'Selesaikan downtime terlebih dahulu.');
            abort_unless($lockedSession->status === 'running', 409, 'Sesi produksi sudah selesai.');

            $endTime = now();
            $lastResumeTime = DowntimeLog::query()
                ->where('production_session_id', $lockedSession->id)
                ->whereNotNull('go_time')
                ->latest('id')
                ->value('go_time');
            abort_if(
                $lastResumeTime && Carbon::parse($lastResumeTime)->diffInSeconds($endTime) < 3,
                429,
                'Mesin baru dijalankan kembali. Tunggu 3 detik sebelum menyelesaikan produksi.'
            );

            abort_if(
                $lockedSession->start_time?->diffInSeconds($endTime) < 3,
                429,
                'Sesi baru dimulai. Tunggu 3 detik sebelum menyelesaikan produksi.'
            );

            $standard = OeeStandard::query()
                ->where('product_id', $lockedSession->product_id)
                ->where('machine_id', $lockedSession->machine_id)
                ->where('is_active', true)
                ->where('theoretical_output_kg', '>', 0)
                ->where('ideal_cycle_time_hours_per_kg', '>', 0)
                ->first();
            abort_if($standard === null, 422, 'Standar OEE belum tersedia untuk produk dan mesin ini.');

            $closedDowntimeSeconds = (int) DowntimeLog::query()
                ->where('production_session_id', $lockedSession->id)
                ->whereNotNull('go_time')
                ->sum('duration_seconds');
            $operatingTimeSeconds = (int) $lockedSession->start_time->diffInSeconds($endTime) - $closedDowntimeSeconds;
            abort_if($operatingTimeSeconds <= 0, 422, 'Waktu operasi produksi harus lebih dari 0 detik.');

            // ponytail: inline OEE math because completion is the only calculation point; extract only if reused.
            $actualOutputKg = (float) $validated['actual_output_kg'];
            $theoreticalOutputKg = (float) $standard->theoretical_output_kg;
            $idealCycleHoursPerKg = (float) $standard->ideal_cycle_time_hours_per_kg;
            $operatingHours = $operatingTimeSeconds / 3600;
            $availabilityPercent = $operatingTimeSeconds / (($lockedSession->planned_time_minutes ?: 480) * 60) * 100;
            $qualityPercent = $actualOutputKg / $theoreticalOutputKg * 100;
            $performancePercent = $idealCycleHoursPerKg * $actualOutputKg / $operatingHours * 100;
            $oeePercent = $availabilityPercent * $qualityPercent * $performancePercent / 10000;

            $lockedSession->update([
                'status' => 'completed',
                'end_time' => $endTime,
                'actual_output_kg' => $validated['actual_output_kg'],
                'theoretical_output_kg' => $standard->theoretical_output_kg,
                'ideal_cycle_time_hours_per_kg' => $standard->ideal_cycle_time_hours_per_kg,
                'operating_time_seconds' => $operatingTimeSeconds,
                'availability_percent' => $availabilityPercent,
                'quality_percent' => $qualityPercent,
                'performance_percent' => $performancePercent,
                'oee_percent' => $oeePercent,
            ]);

            return $lockedSession;
        });

        return $this->sessionResponse($session);
    }

    public function acknowledge(Request $request, ProductionSession $productionSession): JsonResponse
    {
        DB::transaction(function () use ($request, $productionSession): void {
            $lockedSession = ProductionSession::query()->lockForUpdate()->findOrFail($productionSession->id);
            $this->assertController($lockedSession, $request);
            abort_unless($lockedSession->status === 'completed', 409, 'Sesi produksi belum selesai.');
            $lockedSession->update(['controller_token_hash' => null]);
        });

        return response()->json([
            'data' => null,
            'isController' => false,
            'serverNow' => now('Asia/Jakarta')->toIso8601String(),
        ]);
    }

    private function controllerHash(Request $request): string
    {
        return ProductionSession::hashControllerToken(
            (string) $request->attributes->get(EnsureDeviceToken::ATTRIBUTE)
        );
    }

    private function assertController(ProductionSession $session, Request $request): void
    {
        abort_unless(
            $session->controller_token_hash !== null
                && hash_equals($session->controller_token_hash, $this->controllerHash($request)),
            409,
            'Kontrol sesi sudah berpindah ke tablet lain. Tekan Lanjutkan Sesi untuk mengambil alih kembali.',
        );
    }

    private function sessionResponse(ProductionSession $session): JsonResponse
    {
        return response()->json([
            'data' => $this->sessionPayload($session->fresh(['machine.area', 'product', 'downtimeLogs'])),
            'isController' => true,
            'serverNow' => now('Asia/Jakarta')->toIso8601String(),
        ]);
    }

    private function sessionPayload(ProductionSession $session): array
    {
        $session->loadMissing(['machine.area', 'product', 'downtimeLogs']);
        $activeDowntime = $session->downtimeLogs->firstWhere('go_time', null);
        $closedDowntimes = $session->downtimeLogs->whereNotNull('go_time');
        $elapsedSeconds = $session->start_time
            ? (int) $session->start_time->diffInSeconds($session->end_time ?? now())
            : 0;
        $totalDowntimeSeconds = (int) $closedDowntimes->sum('duration_seconds');
        $activeDowntimeSeconds = $activeDowntime
            ? (int) Carbon::parse($activeDowntime->stop_time)->diffInSeconds(now())
            : 0;
        $totalDurationSeconds = max(0, $elapsedSeconds - $totalDowntimeSeconds - $activeDowntimeSeconds);

        return [
            'id' => $session->id,
            'status' => $session->status,
            'operatorName' => $session->operator_name,
            'batchNumber' => $session->batch_number,
            'startTime' => $session->start_time?->copy()->timezone('Asia/Jakarta')->toIso8601String(),
            'endTime' => $session->end_time?->copy()->timezone('Asia/Jakarta')->toIso8601String(),
            'area' => [
                'id' => $session->machine->area->id,
                'name' => $session->machine->area->area_name,
            ],
            'product' => [
                'id' => $session->product?->id,
                'name' => $session->product?->product_name ?? 'Data lama',
            ],
            'machine' => [
                'id' => $session->machine->id,
                'name' => $session->machine->machine_name,
            ],
            'downtime' => $activeDowntime ? [
                'id' => $activeDowntime->id,
                'stopTime' => Carbon::parse($activeDowntime->stop_time)->timezone('Asia/Jakarta')->toIso8601String(),
            ] : null,
            'summary' => [
                'totalDurationSeconds' => $totalDurationSeconds,
                'hadDowntime' => $session->downtimeLogs->isNotEmpty(),
                'downtimeCount' => $session->downtimeLogs->count(),
                'totalDowntimeSeconds' => $totalDowntimeSeconds,
                'hasIncompleteDowntime' => $session->status === 'completed' && $activeDowntime !== null,
            ],
            'oee' => [
                'actualOutputKg' => $session->actual_output_kg !== null ? (float) $session->actual_output_kg : null,
                'availabilityPercent' => $session->availability_percent !== null ? (float) $session->availability_percent : null,
                'qualityPercent' => $session->quality_percent !== null ? (float) $session->quality_percent : null,
                'performancePercent' => $session->performance_percent !== null ? (float) $session->performance_percent : null,
                'oeePercent' => $session->oee_percent !== null ? (float) $session->oee_percent : null,
                'warnings' => $session->oeeWarnings(),
            ],
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\DowntimeCategory;
use App\Models\DowntimeLog;
use App\Models\Machine;
use App\Models\OeeStandard;
use App\Models\Personnel;
use App\Models\Product;
use App\Models\ProductionSession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PresentationHistorySeeder extends Seeder
{
    public function run(): void
    {
        $operators = [
            'Budi Santoso', 'Siti Rahmawati', 'Agus Setiawan', 'Dewi Lestari',
            'Rizky Pratama', 'Nur Aisyah', 'Eko Saputra', 'Rina Marlina',
            'Dedi Kurniawan', 'Maya Sari', 'Andi Wijaya',
        ];
        $fallbacks = [
            'Mesin Tablet Non Hormon 1' => ['tablet', 120000, 480],
            'Mesin Injeksi 1' => ['vial', 12000, 480],
            'Mesin Implant 1' => ['implant', 5000, 480],
            'Mesin Packing Hormon 1' => ['box', 50000, 480],
        ];
        $batchPrefixes = [
            'Tablet Hormon' => 'TH',
            'Tablet Non Hormon' => 'TNH',
            'Injeksi' => 'INJ',
            'Implant' => 'IMP',
            'Packing Hormon' => 'PKH',
        ];
        $availability = [0.94, 1, 0.91, 1, 0.95, 1, 0.92, 1, 0.89, 1, 0.96];
        $quality = [0.994, 0.991, 0.987, 0.996, 0.992, 0.989, 0.995, 0.986, 0.993, 0.988, 0.997];
        $performance = [0.96, 0.94, 0.93, 0.97, 0.95, 0.92, 0.96, 0.91, 0.94, 0.93, 0.98];
        $downtimeCategories = ['PERBAIKAN MESIN', 'PERSIAPAN MESIN', 'MENUNGGU BAHAN BAKU/KEMAS'];

        DB::transaction(function () use ($operators, $fallbacks, $batchPrefixes, $availability, $quality, $performance, $downtimeCategories): void {
            ProductionSession::query()->where('status', 'completed')->delete();

            $maintenanceId = Personnel::query()->where('name', 'Tim Maintenance')->value('id');
            $categoryIds = DowntimeCategory::query()
                ->whereIn('category_name', $downtimeCategories)
                ->pluck('id', 'category_name');
            $baseEnd = now('Asia/Jakarta')->subDay()->setTime(16, 0);

            Machine::query()->active()->with('area')->orderBy('id')->get()->each(
                function (Machine $machine, int $index) use ($operators, $fallbacks, $batchPrefixes, $availability, $quality, $performance, $downtimeCategories, $maintenanceId, $categoryIds, $baseEnd): void {
                    $standard = OeeStandard::query()
                        ->where('machine_id', $machine->id)
                        ->where('is_active', true)
                        ->orderBy('id')
                        ->first();
                    $product = $standard?->product ?? Product::query()
                        ->where('area_id', $machine->area_id)
                        ->where('is_active', true)
                        ->orderBy('id')
                        ->firstOrFail();
                    [$outputUnit, $theoreticalOutput, $plannedMinutes] = $standard
                        ? [$standard->output_unit, (float) $standard->theoretical_output, $standard->planned_time_minutes]
                        : $fallbacks[$machine->machine_name];
                    $processType = $standard?->process_type ?? 'unit_cycle';
                    $idealCycle = $processType === 'unit_cycle' ? $plannedMinutes / $theoreticalOutput : null;
                    $availabilityPercent = $availability[$index] * 100;
                    $qualityPercent = $quality[$index] * 100;
                    $performancePercent = ($processType === 'yield_ratio' ? $quality[$index] : $performance[$index]) * 100;
                    $operatingSeconds = (int) round($plannedMinutes * 60 * $availability[$index]);
                    $totalOutput = $processType === 'yield_ratio'
                        ? $theoreticalOutput * $quality[$index]
                        : ($operatingSeconds / 60) * $performance[$index] / $idealCycle;
                    $rejectOutput = $totalOutput * (1 - $quality[$index]);
                    $endTime = $baseEnd->copy()->subDays($index)->subMinutes(($index % 3) * 15)->utc();
                    $startTime = $endTime->copy()->subMinutes($plannedMinutes);

                    $session = ProductionSession::create([
                        'machine_id' => $machine->id,
                        'product_id' => $product->id,
                        'operator_name' => $operators[$index],
                        'batch_number' => $batchPrefixes[$machine->area->area_name].'-'.$endTime->format('ymd').'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'planned_time_minutes' => $plannedMinutes,
                        'status' => 'completed',
                        'process_type' => $processType,
                        'output_unit' => $outputUnit,
                        'theoretical_output' => $theoreticalOutput,
                        'ideal_cycle_time_minutes_per_unit' => $idealCycle,
                        'total_output' => round($totalOutput, 3),
                        'reject_output' => round($rejectOutput, 3),
                        'operating_time_seconds' => $operatingSeconds,
                        'availability_percent' => $availabilityPercent,
                        'quality_percent' => $qualityPercent,
                        'performance_percent' => $performancePercent,
                        'oee_percent' => $availabilityPercent * $qualityPercent * $performancePercent / 10000,
                    ]);

                    if ($availability[$index] < 1) {
                        $durationSeconds = ($plannedMinutes * 60) - $operatingSeconds;
                        $category = $downtimeCategories[$index % count($downtimeCategories)];
                        $stopTime = $startTime->copy()->addMinutes((int) round($plannedMinutes * 0.55));

                        DowntimeLog::create([
                            'production_session_id' => $session->id,
                            'stop_time' => $stopTime,
                            'go_time' => $stopTime->copy()->addSeconds($durationSeconds),
                            'duration_seconds' => $durationSeconds,
                            'downtime_category_id' => $categoryIds[$category],
                            'personnel_id' => $maintenanceId,
                            'repair_action' => $category === 'MENUNGGU BAHAN BAKU/KEMAS' ? 'Koordinasi percepatan material' : 'Pemeriksaan dan penyetelan mesin',
                        ]);
                    }
                },
            );
        });
    }
}

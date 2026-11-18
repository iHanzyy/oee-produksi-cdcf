<?php

namespace Database\Seeders;

use App\Models\DowntimeCategory;
use App\Models\DowntimeLog;
use App\Models\OeeStandard;
use App\Models\Personnel;
use App\Models\ProductionSession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class PresentationHistorySeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new LogicException('Seeder data presentasi tidak boleh dijalankan di lingkungan produksi.');
        }

        $batchPrefixes = [
            'Tablet Hormon' => 'TH',
            'Tablet Non Hormon' => 'TNH',
            'Injeksi' => 'INJ',
            'Implant' => 'IMP',
            'Packing Hormon' => 'PKH',
        ];
        $quality = [0.994, 0.991, 0.987, 0.996, 0.992, 0.989, 0.995, 0.986, 0.993, 0.988, 0.997];
        $performance = [0.96, 0.94, 0.93, 0.97, 0.95, 0.92, 0.96, 0.91, 0.94, 0.93, 0.98];
        $downtimeCategories = ['PERBAIKAN MESIN', 'PERSIAPAN MESIN', 'MENUNGGU BAHAN BAKU/KEMAS'];

        DB::transaction(function () use ($batchPrefixes, $quality, $performance, $downtimeCategories): void {
            ProductionSession::query()->where('status', 'completed')->delete();

            $maintenanceId = Personnel::query()->where('name', 'Tim Maintenance')->value('id');
            $categoryIds = DowntimeCategory::query()
                ->whereIn('category_name', $downtimeCategories)
                ->pluck('id', 'category_name');
            $baseEnd = now('Asia/Jakarta')->subDay()->setTime(16, 0);
            $standards = OeeStandard::query()
                ->where('is_active', true)
                ->with(['product', 'machine.area'])
                ->orderBy('id')
                ->get();
            $entries = $standards->map(fn (OeeStandard $standard) => [$standard->machine, $standard, $standard->product]);

            $entries->values()->each(
                function (array $entry, int $index) use ($batchPrefixes, $quality, $performance, $downtimeCategories, $maintenanceId, $categoryIds, $baseEnd): void {
                    [$machine, $standard, $product] = $entry;
                    $outputUnit = $standard->output_unit;
                    $theoreticalOutput = (float) $standard->theoretical_output;
                    $plannedMinutes = $standard->planned_time_minutes;
                    $processType = $standard->process_type;
                    $idealCycle = (float) $standard->ideal_cycle_time_minutes_per_unit;
                    $operatingMinutes = self::demoProcessMinutes($product->product_name, $machine->machine_name);
                    $operatingSeconds = $operatingMinutes * 60;
                    $downtimeSeconds = 30 * 60;
                    $elapsedSeconds = $operatingSeconds + $downtimeSeconds;
                    $totalOutput = $processType === 'yield_ratio'
                        ? round($theoreticalOutput * 0.99, 3)
                        : round($theoreticalOutput * $performance[$index % count($performance)]);
                    $rejectOutput = $processType === 'unit_cycle'
                        ? round($totalOutput * (1 - $quality[$index % count($quality)]))
                        : round($totalOutput * (1 - $quality[$index % count($quality)]), 3);
                    $goodOutput = $totalOutput - $rejectOutput;
                    $availabilityPercent = $operatingMinutes / $plannedMinutes * 100;
                    $qualityPercent = $goodOutput / $totalOutput * 100;
                    $performancePercent = $processType === 'unit_cycle'
                        ? $idealCycle * $totalOutput / $operatingMinutes * 100
                        : $qualityPercent;
                    $endTime = $baseEnd->copy()->subDays($index)->subMinutes(($index % 3) * 15)->utc();
                    $startTime = $endTime->copy()->subSeconds($elapsedSeconds);

                    $session = ProductionSession::create([
                        'machine_id' => $machine->id,
                        'product_id' => $product->id,
                        'operator_name' => 'Tama',
                        'batch_number' => $batchPrefixes[$machine->area->area_name].'-'.$endTime->format('ymd').'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'planned_time_minutes' => $plannedMinutes,
                        'status' => 'completed',
                        'process_type' => $processType,
                        'output_unit' => $outputUnit,
                        'theoretical_output' => $theoreticalOutput,
                        'ideal_cycle_time_minutes_per_unit' => $idealCycle,
                        'total_output' => $totalOutput,
                        'reject_output' => $rejectOutput,
                        'operating_time_seconds' => $operatingSeconds,
                        'availability_percent' => $availabilityPercent,
                        'quality_percent' => $qualityPercent,
                        'performance_percent' => $performancePercent,
                        'oee_percent' => $availabilityPercent * $qualityPercent * $performancePercent / 10000,
                    ]);

                    $category = $downtimeCategories[$index % count($downtimeCategories)];
                    $stopTime = $startTime->copy()->addSeconds((int) round($operatingSeconds * 0.55));

                    DowntimeLog::create([
                        'production_session_id' => $session->id,
                        'stop_time' => $stopTime,
                        'go_time' => $stopTime->copy()->addSeconds($downtimeSeconds),
                        'duration_seconds' => $downtimeSeconds,
                        'downtime_category_id' => $categoryIds[$category],
                        'personnel_id' => $maintenanceId,
                        'repair_action' => $category === 'MENUNGGU BAHAN BAKU/KEMAS' ? 'Koordinasi percepatan material' : 'Pemeriksaan dan penyetelan mesin',
                    ]);
                },
            );
        });
    }

    private static function demoProcessMinutes(string $productName, string $machineName): int
    {
        return match ($machineName) {
            'Stripper Indomach' => in_array($productName, ['Alyrenol', 'Pregnolin', 'Progeston', 'Prestrenol'], true) ? 250 : 175,
            'Cetak Gylongli 1', 'Cetak Gylongli 2' => match ($productName) {
                'Alyrenol', 'Pregnolin', 'Progeston', 'Prestrenol' => 91,
                'Trinordiol Fase 1', 'Trinordiol Fase 2', 'Trinordiol Fase 3' => 480,
                'Pil KB Harmonis' => 680,
                default => 64,
            },
            'Blister DKPS 1', 'Blister DKPS 2' => $productName === 'Pil KB Harmonis' ? 798 : 1777,
            'Mixing' => match ($productName) {
                'Alyrenol', 'Pregnolin', 'Progeston', 'Prestrenol' => 181,
                'Bifertil', 'Provula' => 361,
                'Trinordiol Fase 1', 'Trinordiol Fase 2', 'Trinordiol Fase 3', 'Pil KB Harmonis' => 601,
                default => 121,
            },
            'Coating Yenchen' => match ($productName) {
                'Pil KB Harmonis' => 662,
                'Trinordiol Fase 1' => 1400,
                'Trinordiol Fase 2' => 1200,
                'Trinordiol Fase 3' => 1830,
            },
        };
    }
}

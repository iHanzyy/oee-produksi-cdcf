<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\OeeStandard;
use App\Models\Product;
use App\Models\ProductionSession;
use Database\Seeders\PresentationHistorySeeder;
use Database\Seeders\ProductionMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresentationHistorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_replaces_completed_history_for_every_standard_and_preserves_running_sessions(): void
    {
        $this->seed(ProductionMasterSeeder::class);

        $machine = Machine::query()->active()->firstOrFail();
        $product = Product::query()->where('area_id', $machine->area_id)->active()->firstOrFail();
        $running = ProductionSession::create([
            'machine_id' => $machine->id,
            'product_id' => $product->id,
            'operator_name' => 'Operator Aktif',
            'batch_number' => 'RUNNING-001',
            'start_time' => now()->subHour(),
            'status' => 'running',
        ]);
        ProductionSession::create([
            'machine_id' => $machine->id,
            'product_id' => $product->id,
            'operator_name' => 'Data Lama',
            'batch_number' => 'OLD-HISTORY',
            'start_time' => now()->subDay(),
            'end_time' => now()->subHours(20),
            'status' => 'completed',
        ]);

        $this->seed(PresentationHistorySeeder::class);
        $this->seed(PresentationHistorySeeder::class);

        $completed = ProductionSession::query()->where('status', 'completed')->get();

        $this->assertDatabaseHas('production_sessions', ['id' => $running->id, 'status' => 'running']);
        $this->assertDatabaseMissing('production_sessions', ['batch_number' => 'OLD-HISTORY']);
        $this->assertCount(OeeStandard::query()->where('is_active', true)->count(), $completed);
        $this->assertTrue($completed->every(fn (ProductionSession $session) => preg_match('/^(TH|TNH|INJ|IMP|PKH)-\d{6}-\d{2}$/', $session->batch_number) === 1));
        $this->assertSame(
            OeeStandard::query()->where('is_active', true)->distinct()->count('machine_id'),
            $completed->pluck('machine_id')->unique()->count(),
        );
        $this->assertSame(
            OeeStandard::query()->where('is_active', true)->count(),
            $completed->filter(fn (ProductionSession $session) => OeeStandard::query()
                ->where('product_id', $session->product_id)
                ->where('machine_id', $session->machine_id)
                ->where('is_active', true)
                ->exists())->count(),
        );
        $this->assertTrue($completed->every(fn (ProductionSession $session) => $session->operator_name === 'Tama'
            && $session->total_output > 0
            && $session->oee_percent > 0
        ));
        $this->assertSame($completed->count(), $completed->sum(fn (ProductionSession $session) => $session->downtimeLogs()->count()));
    }

    public function test_it_uses_the_ideal_cycle_from_the_oee_standard(): void
    {
        $this->seed(ProductionMasterSeeder::class);
        $this->seed(PresentationHistorySeeder::class);

        $session = ProductionSession::query()
            ->whereHas('machine', fn ($query) => $query->where('machine_name', 'Cetak Gylongli 1'))
            ->firstOrFail();
        $standard = OeeStandard::query()
            ->where('machine_id', $session->machine_id)
            ->where('product_id', $session->product_id)
            ->firstOrFail();

        $this->assertSame($standard->ideal_cycle_time_minutes_per_unit, $session->ideal_cycle_time_minutes_per_unit);
    }

    public function test_unit_cycle_output_does_not_exceed_theoretical_batch_size(): void
    {
        $this->seed(ProductionMasterSeeder::class);
        $this->seed(PresentationHistorySeeder::class);

        $sessions = ProductionSession::query()->where('process_type', 'unit_cycle')->get();

        $this->assertTrue($sessions->every(
            fn (ProductionSession $session) => $session->total_output <= $session->theoretical_output
                && floor((float) $session->total_output) === (float) $session->total_output
                && floor((float) $session->reject_output) === (float) $session->reject_output,
        ));
    }

    public function test_tablet_demo_duration_uses_ideal_realtime_instead_of_planned_time(): void
    {
        $this->seed(ProductionMasterSeeder::class);
        $this->seed(PresentationHistorySeeder::class);

        $session = ProductionSession::query()
            ->whereHas('machine', fn ($query) => $query->where('machine_name', 'Cetak Gylongli 1'))
            ->whereHas('product', fn ($query) => $query->where('product_name', 'Alyrenol'))
            ->with('downtimeLogs')
            ->firstOrFail();

        $this->assertSame(90.5, (float) $session->theoretical_output * (float) $session->ideal_cycle_time_minutes_per_unit);
        $this->assertSame(5460, $session->operating_time_seconds);
        $this->assertSame(1800, (int) $session->downtimeLogs->sum('duration_seconds'));
        $this->assertSame(7260, (int) $session->start_time->diffInSeconds($session->end_time));
        $this->assertSame('18.9583', $session->availability_percent);
    }

    public function test_demo_metrics_are_recalculated_from_excel_runtime_output_and_reject(): void
    {
        $this->seed(ProductionMasterSeeder::class);
        $this->seed(PresentationHistorySeeder::class);

        $sessions = ProductionSession::query()
            ->whereNotNull('process_type')
            ->get();

        $this->assertTrue($sessions->every(function (ProductionSession $session): bool {
            $runtimeMinutes = $session->operating_time_seconds / 60;
            $goodOutput = (float) $session->total_output - (float) $session->reject_output;
            $availability = $runtimeMinutes / $session->planned_time_minutes * 100;
            $quality = $goodOutput / (float) $session->total_output * 100;
            $performance = $session->process_type === 'unit_cycle'
                ? (float) $session->ideal_cycle_time_minutes_per_unit * (float) $session->total_output / $runtimeMinutes * 100
                : $quality;

            return abs((float) $session->availability_percent - $availability) < 0.0001
                && abs((float) $session->quality_percent - $quality) < 0.0001
                && abs((float) $session->performance_percent - $performance) < 0.0001;
        }));
    }

    public function test_every_standard_demo_uses_the_excel_process_duration(): void
    {
        $this->seed(ProductionMasterSeeder::class);
        $this->seed(PresentationHistorySeeder::class);

        $groups = [
            [['Stripper Indomach'], ['Alyrenol', 'Pregnolin', 'Progeston', 'Prestrenol'], 250],
            [['Stripper Indomach'], ['Bifertil', 'Provula', 'Novasterone', 'Norelute', 'Meprolute', 'Luterone', 'Norethisterone', 'Etiro-N', 'Andalut'], 175],
            [['Cetak Gylongli 1', 'Cetak Gylongli 2'], ['Alyrenol', 'Pregnolin', 'Progeston', 'Prestrenol'], 91],
            [['Cetak Gylongli 1', 'Cetak Gylongli 2'], ['Bifertil', 'Provula', 'Novasterone', 'Norelute', 'Meprolute', 'Luterone', 'Norethisterone', 'Etiro-N', 'Andalut'], 64],
            [['Cetak Gylongli 1', 'Cetak Gylongli 2'], ['Trinordiol Fase 1', 'Trinordiol Fase 2', 'Trinordiol Fase 3'], 480],
            [['Cetak Gylongli 1', 'Cetak Gylongli 2'], ['Pil KB Harmonis'], 680],
            [['Blister DKPS 1', 'Blister DKPS 2'], ['Pil KB Harmonis'], 798],
            [['Blister DKPS 1', 'Blister DKPS 2'], ['Trinordiol'], 1777],
            [['Mixing'], ['Alyrenol', 'Pregnolin', 'Progeston', 'Prestrenol'], 181],
            [['Mixing'], ['Bifertil', 'Provula'], 361],
            [['Mixing'], ['Novasterone', 'Norelute', 'Meprolute', 'Luterone', 'Norethisterone', 'Etiro-N', 'Andalut'], 121],
            [['Mixing'], ['Trinordiol Fase 1', 'Trinordiol Fase 2', 'Trinordiol Fase 3', 'Pil KB Harmonis'], 601],
            [['Coating Yenchen'], ['Pil KB Harmonis'], 662],
            [['Coating Yenchen'], ['Trinordiol Fase 1'], 1400],
            [['Coating Yenchen'], ['Trinordiol Fase 2'], 1200],
            [['Coating Yenchen'], ['Trinordiol Fase 3'], 1830],
        ];

        foreach ($groups as [$machines, $products, $minutes]) {
            foreach ($machines as $machine) {
                foreach ($products as $product) {
                    $session = ProductionSession::query()
                        ->whereHas('machine', fn ($query) => $query->where('machine_name', $machine))
                        ->whereHas('product', fn ($query) => $query->where('product_name', $product))
                        ->firstOrFail();

                    $this->assertSame($minutes * 60, $session->operating_time_seconds, "$product / $machine");
                }
            }
        }
    }
}

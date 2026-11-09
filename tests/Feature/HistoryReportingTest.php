<?php

namespace Tests\Feature;

use App\Exports\DowntimeHistoryExport;
use App\Models\Area;
use App\Models\DowntimeLog;
use App\Models\Machine;
use App\Models\Product;
use App\Models\ProductionSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Tests\TestCase;

class HistoryReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporting_routes_are_readable_by_spv_and_manager_but_forbidden_to_operator(): void
    {
        foreach (['spv', 'manager'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('history.index'))->assertOk();
            $this->actingAs($user)->get(route('history.export'))->assertDownload('Laporan_Downtime_OEE.xlsx');
        }

        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->get(route('history.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($operator)->get(route('history.export'))->assertRedirect(route('dashboard'));
    }

    public function test_date_filters_require_both_dates_in_the_correct_order(): void
    {
        $spv = User::factory()->create(['role' => 'spv']);

        $this->actingAs($spv)
            ->get(route('history.index', ['start_date' => '2026-09-24']))
            ->assertSessionHasErrors('end_date');

        $this->actingAs($spv)
            ->get(route('history.index', ['end_date' => '2026-09-24']))
            ->assertSessionHasErrors('start_date');

        $this->actingAs($spv)
            ->get(route('history.index', ['start_date' => '2026-09-25', 'end_date' => '2026-09-24']))
            ->assertSessionHasErrors('end_date');
    }

    public function test_machine_filter_must_belong_to_the_selected_area(): void
    {
        $spv = User::factory()->create(['role' => 'spv']);
        $tabletArea = Area::create(['area_name' => 'Tablet Hormon']);
        $packingArea = Area::create(['area_name' => 'Packing Hormon']);
        $packingMachine = Machine::create([
            'area_id' => $packingArea->id,
            'machine_name' => 'Mesin Packing Hormon 1',
        ]);

        $this->actingAs($spv)
            ->get(route('history.index', [
                'area_id' => $tabletArea->id,
                'machine_id' => $packingMachine->id,
            ]))
            ->assertSessionHasErrors('machine_id');
    }

    public function test_screen_and_export_share_filters_order_and_eager_loaded_relations(): void
    {
        $this->assertTrue(Schema::hasIndex('downtime_logs', ['stop_time']));

        $spv = User::factory()->create(['role' => 'spv']);
        $tabletArea = Area::create(['area_name' => 'Tablet Hormon']);
        $packingArea = Area::create(['area_name' => 'Packing Hormon']);
        $mixing = Machine::create(['area_id' => $tabletArea->id, 'machine_name' => 'Mixing']);
        $blister = Machine::create(['area_id' => $tabletArea->id, 'machine_name' => 'Blister DKPS 1']);
        $packing = Machine::create(['area_id' => $packingArea->id, 'machine_name' => 'Mesin Packing Hormon 1']);

        $older = $this->createDowntime($mixing, '2026-09-23 17:00:00');
        $newer = $this->createDowntime($mixing, '2026-09-24 16:59:59');
        $this->createDowntime($mixing, '2026-09-24 17:00:00');
        $this->createDowntime($packing, '2026-09-24 00:00:00');

        $filters = [
            'start_date' => '2026-09-24',
            'end_date' => '2026-09-24',
            'area_id' => $tabletArea->id,
            'machine_id' => $mixing->id,
        ];

        $response = $this->actingAs($spv)->get(route('history.index', $filters))->assertOk();

        $response->assertViewHas('logs', function (LengthAwarePaginator $logs) use ($newer, $older, $filters): bool {
            $this->assertSame([$newer->id, $older->id], $logs->pluck('id')->all());
            $this->assertStringContainsString('area_id='.$filters['area_id'], $logs->url(2));

            foreach ($logs as $log) {
                $this->assertTrue($log->relationLoaded('productionSession'));
                $this->assertTrue($log->productionSession->relationLoaded('machine'));
                $this->assertTrue($log->productionSession->machine->relationLoaded('area'));
                $this->assertTrue($log->relationLoaded('downtimeCategory'));
                $this->assertTrue($log->relationLoaded('personnel'));
            }

            return true;
        });

        $response->assertViewHas('machines', fn ($machines): bool => $machines->pluck('id')->all() === [$blister->id, $mixing->id]);

        $export = new DowntimeHistoryExport($filters);

        $this->assertInstanceOf(FromQuery::class, $export);
        $this->assertNotInstanceOf(FromCollection::class, $export);
        $this->assertSame([$newer->id, $older->id], $export->query()->pluck('downtime_logs.id')->all());
    }

    public function test_history_and_export_include_batch_number_with_legacy_fallback(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $machine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);
        $withBatch = $this->createDowntime(
            $machine,
            '2026-09-24 01:00:00',
            'completed',
            '2026-09-24 01:05:00',
            300,
            'BATCH-HISTORY-001',
        );
        $legacy = $this->createDowntime($machine, '2026-09-24 00:50:00');
        $this->createDowntime($machine, '2026-09-24 00:40:00', 'downtime', null, 0, 'BATCH-ACTIVE-001');

        $this->actingAs($manager)
            ->get(route('history.index'))
            ->assertOk()
            ->assertSee('Nomor Batch')
            ->assertSee('BATCH-HISTORY-001');

        $this->actingAs($manager)
            ->getJson(route('history.active_sessions'))
            ->assertOk()
            ->assertJsonPath('sessions.0.batchNumber', 'BATCH-ACTIVE-001');

        $export = new DowntimeHistoryExport([]);

        $this->assertSame('Nomor Batch', $export->headings()[3]);
        $this->assertSame('BATCH-HISTORY-001', $export->map($withBatch->fresh()->load('productionSession.machine.area', 'downtimeCategory', 'personnel'))[3]);
        $this->assertSame('-', $export->map($legacy->fresh()->load('productionSession.machine.area', 'downtimeCategory', 'personnel'))[3]);
    }

    public function test_history_distinguishes_active_incomplete_and_completed_logs_in_jakarta_time(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $machine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);

        $active = $this->createDowntime($machine, '2026-09-24 00:30:00', 'downtime');
        $incomplete = $this->createDowntime($machine, '2026-09-24 00:20:00');
        $completed = $this->createDowntime($machine, '2026-09-24 00:10:00', 'completed', '2026-09-24 00:15:00', 300);

        $this->actingAs($manager)
            ->get(route('history.index'))
            ->assertOk()
            ->assertSee('24 Sep 2026')
            ->assertSee('07:30:00')
            ->assertSee('Berlangsung')
            ->assertSee('Data tidak lengkap')
            ->assertSee('Selesai');

        $export = new DowntimeHistoryExport([]);

        $this->assertSame('Berlangsung', $active->fresh()->load('productionSession')->status_label);
        $this->assertSame('Data tidak lengkap', $incomplete->fresh()->load('productionSession')->status_label);
        $this->assertSame('Selesai', $completed->fresh()->load('productionSession')->status_label);
        $this->assertSame('24-09-2026 07:10:00', $export->map($completed->fresh()->load('productionSession.machine.area', 'downtimeCategory', 'personnel'))[0]);
    }

    public function test_completed_session_oee_history_lists_one_row_per_session_with_filters_and_independent_pagination(): void
    {
        $spv = User::factory()->create(['role' => 'spv']);
        $tabletArea = Area::create(['area_name' => 'Tablet Hormon']);
        $packingArea = Area::create(['area_name' => 'Packing Hormon']);
        $product = Product::create(['area_id' => $tabletArea->id, 'product_name' => 'Alyrenol']);
        $mixing = Machine::create(['area_id' => $tabletArea->id, 'machine_name' => 'Mixing']);
        $packingMachine = Machine::create(['area_id' => $packingArea->id, 'machine_name' => 'Mesin Packing Hormon 1']);

        $noDowntime = ProductionSession::create([
            'machine_id' => $mixing->id,
            'product_id' => $product->id,
            'operator_name' => 'Budi',
            'batch_number' => 'BATCH-OEE-NODT',
            'start_time' => '2026-09-24 01:00:00',
            'end_time' => '2026-09-24 09:00:00',
            'status' => 'completed',
            'actual_output_kg' => 35,
            'theoretical_output_kg' => 40,
            'ideal_cycle_time_hours_per_kg' => 0.2,
            'operating_time_seconds' => 28800,
            'availability_percent' => 100,
            'quality_percent' => 87.5,
            'performance_percent' => 87.5,
            'oee_percent' => 76.5625,
        ]);

        $multiDowntime = ProductionSession::create([
            'machine_id' => $mixing->id,
            'product_id' => $product->id,
            'operator_name' => 'Siti',
            'batch_number' => 'BATCH-OEE-WARN',
            'start_time' => '2026-09-24 02:00:00',
            'end_time' => '2026-09-24 10:00:00',
            'status' => 'completed',
            'actual_output_kg' => 50,
            'theoretical_output_kg' => 40,
            'ideal_cycle_time_hours_per_kg' => 0.2,
            'operating_time_seconds' => 25200,
            'availability_percent' => 87.5,
            'quality_percent' => 125,
            'performance_percent' => 142.8571,
            'oee_percent' => 156.25,
        ]);

        DowntimeLog::create([
            'production_session_id' => $multiDowntime->id,
            'stop_time' => '2026-09-24 03:00:00',
            'go_time' => '2026-09-24 03:30:00',
            'duration_seconds' => 1800,
        ]);
        DowntimeLog::create([
            'production_session_id' => $multiDowntime->id,
            'stop_time' => '2026-09-24 05:00:00',
            'go_time' => '2026-09-24 05:30:00',
            'duration_seconds' => 1800,
        ]);

        $legacyNull = ProductionSession::create([
            'machine_id' => $mixing->id,
            'product_id' => $product->id,
            'operator_name' => 'Rina',
            'batch_number' => 'BATCH-OEE-LEGACY',
            'start_time' => '2026-09-23 18:00:00',
            'end_time' => '2026-09-23 19:00:00',
            'status' => 'completed',
        ]);

        ProductionSession::create([
            'machine_id' => $mixing->id,
            'product_id' => $product->id,
            'operator_name' => 'Running',
            'batch_number' => 'BATCH-OEE-RUN',
            'start_time' => '2026-09-24 11:00:00',
            'status' => 'running',
        ]);

        ProductionSession::create([
            'machine_id' => $packingMachine->id,
            'product_id' => $product->id,
            'operator_name' => 'Other Area',
            'batch_number' => 'BATCH-OEE-PACK',
            'start_time' => '2026-09-24 01:00:00',
            'end_time' => '2026-09-24 09:00:00',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($spv)->get(route('history.index', [
            'start_date' => '2026-09-24',
            'end_date' => '2026-09-24',
            'area_id' => $tabletArea->id,
            'machine_id' => $mixing->id,
        ]))->assertOk();

        $response->assertViewHas('completedSessions', function (LengthAwarePaginator $sessions) use ($multiDowntime, $noDowntime, $legacyNull): bool {
            $this->assertSame([$multiDowntime->id, $noDowntime->id, $legacyNull->id], $sessions->pluck('id')->all());
            $this->assertSame('session_page', $sessions->getPageName());

            foreach ($sessions as $session) {
                $this->assertTrue($session->relationLoaded('machine'));
                $this->assertTrue($session->machine->relationLoaded('area'));
                $this->assertTrue($session->relationLoaded('product'));
            }

            return true;
        });

        $response->assertViewHas('logs', fn (LengthAwarePaginator $logs): bool => $logs->getPageName() === 'page' && $logs->total() === 2);

        $response
            ->assertSee('Riwayat Produksi/OEE')
            ->assertSee('BATCH-OEE-NODT')
            ->assertSee('BATCH-OEE-WARN')
            ->assertSee('BATCH-OEE-LEGACY')
            ->assertDontSee('BATCH-OEE-PACK')
            ->assertSee('35 kg')
            ->assertSee('76,56%')
            ->assertSee('125,00%')
            ->assertSee('156,25%')
            ->assertSee('Quality melebihi 100%. Performance melebihi 100%. OEE melebihi 100%.');
    }

    private function createDowntime(
        Machine $machine,
        string $stopTime,
        string $sessionStatus = 'completed',
        ?string $goTime = null,
        int $durationSeconds = 0,
        ?string $batchNumber = null,
    ): DowntimeLog {
        $session = ProductionSession::create([
            'machine_id' => $machine->id,
            'operator_name' => 'Budi',
            'batch_number' => $batchNumber,
            'start_time' => '2026-09-23 16:00:00',
            'end_time' => $sessionStatus === 'completed' ? '2026-09-24 17:00:00' : null,
            'status' => $sessionStatus,
        ]);

        return DowntimeLog::create([
            'production_session_id' => $session->id,
            'stop_time' => $stopTime,
            'go_time' => $goTime,
            'duration_seconds' => $durationSeconds,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\DowntimeCategory;
use App\Models\Machine;
use App\Models\OeeStandard;
use App\Models\Personnel;
use App\Models\Product;
use App\Models\ProductionSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OeeCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_completion_stores_snapshot_and_excludes_downtime_from_operation_time(): void
    {
        Carbon::setTestNow('2026-11-09 08:00:00');
        [$operator, $session, $standard, $token] = $this->startProductionSession();

        Carbon::setTestNow('2026-11-09 10:00:00');
        $this->postAsBrowser($operator, $token, route('machine.stop', $session))->assertOk();

        Carbon::setTestNow('2026-11-09 11:00:00');
        $this->postAsBrowser($operator, $token, route('machine.resume', $session), $this->downtimeDetails())
            ->assertOk();

        Carbon::setTestNow('2026-11-09 16:00:00');
        $this->postAsBrowser($operator, $token, route('machine.end', $session), [
            'confirmed' => true,
            'total_output' => 20000,
            'reject_output' => 1000,
        ])
            ->assertOk()
            ->assertJsonPath('data.oee.totalOutput', 20000)
            ->assertJsonPath('data.oee.rejectOutput', 1000)
            ->assertJsonPath('data.oee.goodOutput', 19000)
            ->assertJsonPath('data.oee.outputUnit', 'strip')
            ->assertJsonPath('data.oee.availabilityPercent', 87.5)
            ->assertJsonPath('data.oee.qualityPercent', 95)
            ->assertJsonPath('data.oee.performancePercent', 59.5238)
            ->assertJsonPath('data.oee.oeePercent', 49.4792)
            ->assertJsonPath('data.oee.warnings', []);

        $session->refresh();
        $this->assertSame(25200, $session->operating_time_seconds);
        $this->assertSame('20000.000', $session->total_output);
        $this->assertSame('1000.000', $session->reject_output);
        $this->assertSame('20000.000', $session->theoretical_output);
        $this->assertSame('0.0125000000', $session->ideal_cycle_time_minutes_per_unit);
        $this->assertSame('49.4792', $session->oee_percent);

        $standard->update([
            'theoretical_output' => 50000,
            'ideal_cycle_time_minutes_per_unit' => 0.02,
        ]);

        $this->assertSame('20000.000', $session->fresh()->theoretical_output);
        $this->assertSame('0.0125000000', $session->fresh()->ideal_cycle_time_minutes_per_unit);
    }

    public function test_completion_preserves_values_above_100_and_returns_warnings(): void
    {
        Carbon::setTestNow('2026-11-09 08:00:00');
        [$operator, $session, , $token] = $this->startProductionSession();
        Carbon::setTestNow('2026-11-09 16:00:00');

        $this->postAsBrowser($operator, $token, route('machine.end', $session), [
            'confirmed' => true,
            'total_output' => 50000,
            'reject_output' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('data.oee.qualityPercent', 100)
            ->assertJsonPath('data.oee.performancePercent', 130.2083)
            ->assertJsonPath('data.oee.oeePercent', 130.2083)
            ->assertJsonPath('data.oee.warnings', [
                'Performance melebihi 100%.',
                'OEE melebihi 100%.',
            ]);
    }

    public function test_yield_ratio_performance_matches_quality(): void
    {
        Carbon::setTestNow('2026-11-09 08:00:00');
        [$operator, $session, , $token] = $this->startProductionSession([
            'process_type' => 'yield_ratio',
            'output_unit' => 'kg',
            'theoretical_output' => 40,
            'ideal_cycle_time_minutes_per_unit' => null,
        ]);
        Carbon::setTestNow('2026-11-09 16:00:00');

        $this->postAsBrowser($operator, $token, route('machine.end', $session), [
            'confirmed' => true,
            'total_output' => 35,
            'reject_output' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.oee.goodOutput', 33)
            ->assertJsonPath('data.oee.availabilityPercent', 100)
            ->assertJsonPath('data.oee.qualityPercent', 94.2857)
            ->assertJsonPath('data.oee.performancePercent', 94.2857)
            ->assertJsonPath('data.oee.oeePercent', 88.898);
    }

    public function test_completion_accepts_zero_output(): void
    {
        Carbon::setTestNow('2026-11-09 08:00:00');
        [$operator, $session, , $token] = $this->startProductionSession();
        Carbon::setTestNow('2026-11-09 16:00:00');

        $this->postAsBrowser($operator, $token, route('machine.end', $session), [
            'confirmed' => true,
            'total_output' => 0,
            'reject_output' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('data.oee.availabilityPercent', 100)
            ->assertJsonPath('data.oee.qualityPercent', 0)
            ->assertJsonPath('data.oee.performancePercent', 0)
            ->assertJsonPath('data.oee.oeePercent', 0)
            ->assertJsonPath('data.oee.warnings', []);
    }

    public function test_completion_requires_non_negative_total_and_reject_not_above_total(): void
    {
        Carbon::setTestNow('2026-11-09 08:00:00');
        [$operator, $session, , $token] = $this->startProductionSession();
        Carbon::setTestNow('2026-11-09 16:00:00');

        $this->postAsBrowser($operator, $token, route('machine.end', $session), ['confirmed' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['total_output', 'reject_output']);
        $this->postAsBrowser($operator, $token, route('machine.end', $session), [
            'confirmed' => true,
            'total_output' => 10,
            'reject_output' => 11,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reject_output');

        $this->assertSame('running', $session->fresh()->status);
    }

    public function test_duplicate_completion_is_rejected_and_preserves_first_snapshot(): void
    {
        Carbon::setTestNow('2026-11-09 08:00:00');
        [$operator, $session, , $token] = $this->startProductionSession();
        Carbon::setTestNow('2026-11-09 16:00:00');

        $this->postAsBrowser($operator, $token, route('machine.end', $session), [
            'confirmed' => true,
            'total_output' => 20000,
            'reject_output' => 1000,
        ])->assertOk();

        $this->postAsBrowser($operator, $token, route('machine.end', $session), [
            'confirmed' => true,
            'total_output' => 10000,
            'reject_output' => 0,
        ])
            ->assertConflict()
            ->assertJsonPath('message', 'Sesi produksi sudah selesai.');

        $this->assertSame('20000.000', $session->fresh()->total_output);
    }

    public function test_completion_rolls_back_when_standard_becomes_inactive_before_finish(): void
    {
        Carbon::setTestNow('2026-11-09 08:00:00');
        [$operator, $session, $standard, $token] = $this->startProductionSession();
        $standard->update(['is_active' => false]);
        Carbon::setTestNow('2026-11-09 16:00:00');

        $this->postAsBrowser($operator, $token, route('machine.end', $session), [
            'confirmed' => true,
            'total_output' => 20000,
            'reject_output' => 1000,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Standar OEE belum tersedia untuk produk dan mesin ini.');

        $session->refresh();
        $this->assertSame('running', $session->status);
        $this->assertNull($session->end_time);
        $this->assertNull($session->total_output);
    }

    public function test_completion_rejects_downtime_that_exceeds_planned_time(): void
    {
        Carbon::setTestNow('2026-11-09 08:00:00');
        [$operator, $session, , $token] = $this->startProductionSession(['planned_time_minutes' => 60]);

        Carbon::setTestNow('2026-11-09 08:30:00');
        $this->postAsBrowser($operator, $token, route('machine.stop', $session))->assertOk();
        Carbon::setTestNow('2026-11-09 10:00:00');
        $this->postAsBrowser($operator, $token, route('machine.resume', $session), $this->downtimeDetails())->assertOk();
        Carbon::setTestNow('2026-11-09 10:00:03');

        $this->postAsBrowser($operator, $token, route('machine.end', $session), [
            'confirmed' => true,
            'total_output' => 100,
            'reject_output' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Waktu operasi produksi harus lebih dari 0 menit.');

        $this->assertSame('running', $session->fresh()->status);
    }

    private function startProductionSession(array $standardOverrides = []): array
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $product = Product::create(['area_id' => $area->id, 'product_name' => 'Alyrenol']);
        $machine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Stripper Indomach']);
        $standard = OeeStandard::create(array_merge([
            'product_id' => $product->id,
            'machine_id' => $machine->id,
            'process_type' => 'unit_cycle',
            'output_unit' => 'strip',
            'planned_time_minutes' => 480,
            'theoretical_output' => 20000,
            'ideal_cycle_time_minutes_per_unit' => 0.0125,
            'theoretical_output_kg' => 20000,
            'ideal_cycle_time_hours_per_kg' => 0.0125 / 60,
        ], $standardOverrides));
        $token = $this->actingAs($operator)
            ->withCookie('device_token', 'invalid')
            ->get(route('dashboard'))
            ->getCookie('device_token')
            ->getValue();
        $response = $this->postAsBrowser($operator, $token, route('machine.start'), [
            'area_id' => $area->id,
            'product_id' => $product->id,
            'machine_id' => $machine->id,
            'operator_name' => 'Budi',
            'batch_number' => 'BATCH-OEE-001',
        ])->assertCreated();

        return [$operator, ProductionSession::findOrFail($response->json('data.id')), $standard, $token];
    }

    private function downtimeDetails(): array
    {
        $category = DowntimeCategory::create(['category_name' => 'Breakdown Mesin']);
        $personnel = Personnel::create(['name' => 'Tim Maintenance', 'role' => 'Teknisi ME']);

        return [
            'category_id' => $category->id,
            'personnel_id' => $personnel->id,
            'replaced_component' => null,
            'repair_action' => 'Perbaikan mesin',
        ];
    }

    private function postAsBrowser(User $operator, string $token, string $url, array $data = [])
    {
        return $this->actingAs($operator)
            ->withCredentials()
            ->withCookie('device_token', $token)
            ->postJson($url, $data);
    }
}

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
            'actual_output_kg' => 35,
        ])
            ->assertOk()
            ->assertJsonPath('data.oee.actualOutputKg', 35)
            ->assertJsonPath('data.oee.availabilityPercent', 87.5)
            ->assertJsonPath('data.oee.qualityPercent', 87.5)
            ->assertJsonPath('data.oee.performancePercent', 100)
            ->assertJsonPath('data.oee.oeePercent', 76.5625)
            ->assertJsonPath('data.oee.warnings', []);

        $session->refresh();
        $this->assertSame(25200, $session->operating_time_seconds);
        $this->assertSame('35.000', $session->actual_output_kg);
        $this->assertSame('40.000', $session->theoretical_output_kg);
        $this->assertSame('0.2000000000', $session->ideal_cycle_time_hours_per_kg);
        $this->assertSame('76.5625', $session->oee_percent);

        $standard->update([
            'theoretical_output_kg' => 50,
            'ideal_cycle_time_hours_per_kg' => 0.1,
        ]);

        $this->assertSame('40.000', $session->fresh()->theoretical_output_kg);
        $this->assertSame('0.2000000000', $session->fresh()->ideal_cycle_time_hours_per_kg);
    }

    public function test_completion_preserves_values_above_100_and_returns_warnings(): void
    {
        Carbon::setTestNow('2026-11-09 08:00:00');
        [$operator, $session, , $token] = $this->startProductionSession();
        Carbon::setTestNow('2026-11-09 16:00:00');

        $this->postAsBrowser($operator, $token, route('machine.end', $session), [
            'confirmed' => true,
            'actual_output_kg' => 50,
        ])
            ->assertOk()
            ->assertJsonPath('data.oee.qualityPercent', 125)
            ->assertJsonPath('data.oee.performancePercent', 125)
            ->assertJsonPath('data.oee.oeePercent', 156.25)
            ->assertJsonPath('data.oee.warnings', [
                'Quality melebihi 100%.',
                'Performance melebihi 100%.',
                'OEE melebihi 100%.',
            ]);
    }

    public function test_completion_accepts_zero_output(): void
    {
        Carbon::setTestNow('2026-11-09 08:00:00');
        [$operator, $session, , $token] = $this->startProductionSession();
        Carbon::setTestNow('2026-11-09 16:00:00');

        $this->postAsBrowser($operator, $token, route('machine.end', $session), [
            'confirmed' => true,
            'actual_output_kg' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('data.oee.availabilityPercent', 100)
            ->assertJsonPath('data.oee.qualityPercent', 0)
            ->assertJsonPath('data.oee.performancePercent', 0)
            ->assertJsonPath('data.oee.oeePercent', 0)
            ->assertJsonPath('data.oee.warnings', []);
    }

    public function test_completion_requires_a_non_negative_actual_output(): void
    {
        Carbon::setTestNow('2026-11-09 08:00:00');
        [$operator, $session, , $token] = $this->startProductionSession();
        Carbon::setTestNow('2026-11-09 16:00:00');

        $this->postAsBrowser($operator, $token, route('machine.end', $session), ['confirmed' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('actual_output_kg');
        $this->postAsBrowser($operator, $token, route('machine.end', $session), [
            'confirmed' => true,
            'actual_output_kg' => -1,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('actual_output_kg');

        $this->assertSame('running', $session->fresh()->status);
    }

    public function test_duplicate_completion_is_rejected_and_preserves_first_snapshot(): void
    {
        Carbon::setTestNow('2026-11-09 08:00:00');
        [$operator, $session, , $token] = $this->startProductionSession();
        Carbon::setTestNow('2026-11-09 16:00:00');

        $this->postAsBrowser($operator, $token, route('machine.end', $session), [
            'confirmed' => true,
            'actual_output_kg' => 35,
        ])->assertOk();

        $this->postAsBrowser($operator, $token, route('machine.end', $session), [
            'confirmed' => true,
            'actual_output_kg' => 10,
        ])
            ->assertConflict()
            ->assertJsonPath('message', 'Sesi produksi sudah selesai.');

        $this->assertSame('35.000', $session->fresh()->actual_output_kg);
    }

    public function test_completion_rolls_back_when_standard_becomes_inactive_before_finish(): void
    {
        Carbon::setTestNow('2026-11-09 08:00:00');
        [$operator, $session, $standard, $token] = $this->startProductionSession();
        $standard->update(['is_active' => false]);
        Carbon::setTestNow('2026-11-09 16:00:00');

        $this->postAsBrowser($operator, $token, route('machine.end', $session), [
            'confirmed' => true,
            'actual_output_kg' => 35,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Standar OEE belum tersedia untuk produk dan mesin ini.');

        $session->refresh();
        $this->assertSame('running', $session->status);
        $this->assertNull($session->end_time);
        $this->assertNull($session->actual_output_kg);
    }

    private function startProductionSession(): array
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $product = Product::create(['area_id' => $area->id, 'product_name' => 'Alyrenol']);
        $machine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);
        $standard = OeeStandard::create([
            'product_id' => $product->id,
            'machine_id' => $machine->id,
            'theoretical_output_kg' => 40,
            'ideal_cycle_time_hours_per_kg' => 0.2,
        ]);
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

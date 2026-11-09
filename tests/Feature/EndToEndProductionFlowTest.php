<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\DowntimeCategory;
use App\Models\Machine;
use App\Models\OeeStandard;
use App\Models\Personnel;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndProductionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_flow_is_visible_to_spv_after_completion(): void
    {
        Carbon::setTestNow('2026-09-25 09:00:00');

        $operator = User::factory()->create(['role' => 'operator']);
        $spv = User::factory()->create(['role' => 'spv']);
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $product = Product::create(['area_id' => $area->id, 'product_name' => 'Novasteron']);
        $machine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);
        OeeStandard::create([
            'product_id' => $product->id,
            'machine_id' => $machine->id,
            'theoretical_output_kg' => 40,
            'ideal_cycle_time_hours_per_kg' => 0.075,
        ]);
        $category = DowntimeCategory::create(['category_name' => 'Kerusakan Mekanik']);
        $personnel = Personnel::create(['name' => 'Teknisi A', 'role' => 'Teknisi']);
        $token = $this->actingAs($operator)
            ->withCookie('device_token', 'invalid')
            ->get(route('dashboard'))
            ->getCookie('device_token')
            ->getValue();

        $started = $this->actingAs($operator)
            ->withCredentials()
            ->withCookie('device_token', $token)
            ->postJson(route('machine.start'), [
                'area_id' => $area->id,
                'product_id' => $product->id,
                'machine_id' => $machine->id,
                'operator_name' => 'Budi',
                'batch_number' => 'BATCH-E2E-001',
            ])
            ->assertCreated();

        $sessionId = $started->json('data.id');

        $this->actingAs($operator)->withCredentials()->withCookie('device_token', $token)
            ->postJson(route('machine.stop', $sessionId))
            ->assertOk()
            ->assertJsonPath('data.status', 'downtime');

        $this->actingAs($operator)->withCredentials()->withCookie('device_token', $token)
            ->postJson(route('machine.resume', $sessionId), [
                'category_id' => $category->id,
                'personnel_id' => $personnel->id,
                'replaced_component' => 'Bearing',
                'repair_action' => 'Mengganti bearing',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'running');

        Carbon::setTestNow('2026-09-25 09:00:03');

        $this->actingAs($operator)->withCredentials()->withCookie('device_token', $token)
            ->postJson(route('machine.end', $sessionId), ['confirmed' => true, 'actual_output_kg' => 35])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.summary.hadDowntime', true)
            ->assertJsonPath('data.summary.downtimeCount', 1);

        $this->actingAs($operator)->withCredentials()->withCookie('device_token', $token)
            ->postJson(route('production.acknowledge', $sessionId))
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->actingAs($spv)
            ->get(route('history.index', ['area_id' => $area->id, 'machine_id' => $machine->id]))
            ->assertOk()
            ->assertSee('Riwayat Produksi/OEE')
            ->assertSee('35 kg')
            ->assertSee('Mixing')
            ->assertSee('BATCH-E2E-001')
            ->assertSee('Mengganti bearing')
            ->assertSee('Selesai');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}

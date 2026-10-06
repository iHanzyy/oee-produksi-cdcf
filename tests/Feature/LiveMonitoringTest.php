<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Machine;
use App\Models\Product;
use App\Models\ProductionSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_spv_and_manager_can_see_live_active_sessions_and_poll_status(): void
    {
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $product = Product::create(['area_id' => $area->id, 'product_name' => 'Novasteron']);
        $machine1 = Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);
        $machine2 = Machine::create(['area_id' => $area->id, 'machine_name' => 'Cetak Gylongli 1']);

        ProductionSession::create([
            'machine_id' => $machine1->id,
            'product_id' => $product->id,
            'operator_name' => 'Budi Santoso',
            'start_time' => now()->subMinutes(30),
            'status' => 'running',
        ]);

        ProductionSession::create([
            'machine_id' => $machine2->id,
            'product_id' => $product->id,
            'operator_name' => 'Dewi Lestari',
            'start_time' => now()->subMinutes(10),
            'status' => 'downtime',
        ]);

        $spv = User::factory()->create(['role' => 'spv']);
        $manager = User::factory()->create(['role' => 'manager']);
        $operator = User::factory()->create(['role' => 'operator']);

        // SPV can see live machines on history page
        $this->actingAs($spv)
            ->get(route('history.index'))
            ->assertOk()
            ->assertSee('Monitoring Mesin Aktif (Live)')
            ->assertSee('Mixing')
            ->assertSee('Budi Santoso')
            ->assertSee('Cetak Gylongli 1')
            ->assertSee('Dewi Lestari');

        // SPV can poll active sessions JSON
        $this->actingAs($spv)
            ->getJson(route('history.active_sessions'))
            ->assertOk()
            ->assertJsonCount(2, 'sessions')
            ->assertJsonPath('sessions.0.machine', 'Mixing')
            ->assertJsonPath('sessions.0.status', 'running')
            ->assertJsonPath('sessions.1.machine', 'Cetak Gylongli 1')
            ->assertJsonPath('sessions.1.status', 'downtime');

        // Manager can also poll active sessions JSON
        $this->actingAs($manager)
            ->getJson(route('history.active_sessions'))
            ->assertOk()
            ->assertJsonCount(2, 'sessions');

        // Operator cannot access history active sessions
        $this->actingAs($operator)
            ->getJson(route('history.active_sessions'))
            ->assertForbidden();
    }

    public function test_operator_dashboard_displays_all_running_machines(): void
    {
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $product = Product::create(['area_id' => $area->id, 'product_name' => 'Novasteron']);
        $machine1 = Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);
        $machine2 = Machine::create(['area_id' => $area->id, 'machine_name' => 'Cetak Gylongli 1']);

        ProductionSession::create([
            'machine_id' => $machine1->id,
            'product_id' => $product->id,
            'operator_name' => 'Budi Santoso',
            'start_time' => now()->subMinutes(20),
            'status' => 'running',
        ]);

        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Monitoring Mesin Sedang Berjalan di Pabrik')
            ->assertSee('Mixing')
            ->assertSee('Budi Santoso');
    }
}
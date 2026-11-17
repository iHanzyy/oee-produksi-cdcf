<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\Product;
use App\Models\ProductionSession;
use Database\Seeders\PresentationHistorySeeder;
use Database\Seeders\ProductionMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresentationHistorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_replaces_completed_history_for_every_active_machine_and_preserves_running_sessions(): void
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
        $this->assertCount(Machine::query()->active()->count(), $completed);
        $this->assertTrue($completed->every(fn (ProductionSession $session) => preg_match('/^(TH|TNH|INJ|IMP|PKH)-\d{6}-\d{2}$/', $session->batch_number) === 1));
        $this->assertSame(Machine::query()->active()->count(), $completed->pluck('machine_id')->unique()->count());
        $this->assertTrue($completed->every(fn (ProductionSession $session) => $session->operator_name !== null
            && $session->total_output > 0
            && $session->oee_percent > 0
            && $session->oee_percent <= 100
        ));
        $this->assertSame(6, $completed->sum(fn (ProductionSession $session) => $session->downtimeLogs()->count()));
    }
}

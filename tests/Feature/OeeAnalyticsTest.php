<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Machine;
use App\Models\ProductionSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OeeAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporting_roles_can_open_oee_analytics_but_operator_cannot(): void
    {
        foreach (['spv', 'manager'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('oee.index'))
                ->assertOk()
                ->assertSee('Analisis OEE')
                ->assertSee('Rata-rata seluruh sesi hasil filter')
                ->assertSee('Belum ada data OEE untuk filter ini.');
        }

        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)
            ->get(route('oee.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_oee_analytics_averages_only_matching_completed_sessions_with_oee(): void
    {
        $spv = User::factory()->create(['role' => 'spv']);
        $tabletArea = Area::create(['area_name' => 'Tablet Hormon']);
        $packingArea = Area::create(['area_name' => 'Packing Hormon']);
        $mixing = Machine::create(['area_id' => $tabletArea->id, 'machine_name' => 'Mixing']);
        $blister = Machine::create(['area_id' => $tabletArea->id, 'machine_name' => 'Blister']);
        $packing = Machine::create(['area_id' => $packingArea->id, 'machine_name' => 'Packing']);

        $this->createSession($mixing, 'completed', '2026-09-24 04:00:00', 80, 90, 95, 85);
        $this->createSession($mixing, 'completed', '2026-09-24 05:00:00', 70, 100, 80, 90);
        $this->createSession($mixing, 'completed', '2026-09-24 06:00:00', null, 10, 10, 10);
        $this->createSession($mixing, 'running', null, 10, 10, 10, 10);
        $this->createSession($mixing, 'completed', '2026-09-22 05:00:00', 20, 20, 20, 20);
        $this->createSession($blister, 'completed', '2026-09-24 05:00:00', 50, 50, 50, 50);
        $this->createSession($packing, 'completed', '2026-09-24 05:00:00', 40, 40, 40, 40);

        $response = $this->actingAs($spv)->get(route('oee.index', [
            'start_date' => '2026-09-24',
            'end_date' => '2026-09-24',
            'area_id' => $tabletArea->id,
            'machine_id' => $mixing->id,
        ]));

        $response
            ->assertOk()
            ->assertViewHas('oeeSummary', fn (array $summary): bool => $summary === [
                'sessionCount' => 2,
                'averageOee' => 75.0,
                'averageAvailability' => 95.0,
                'averageQuality' => 87.5,
                'averagePerformance' => 87.5,
                'gaugeOee' => 75.0,
            ])
            ->assertSee('2 sesi selesai')
            ->assertSee('75%')
            ->assertSee('95%')
            ->assertSee('87,5%')
            ->assertSee('pathLength="100"', false);
    }

    public function test_reporting_navigation_has_separate_history_and_oee_sections(): void
    {
        $spv = User::factory()->create(['role' => 'spv']);

        $this->actingAs($spv)
            ->get(route('history.index'))
            ->assertOk()
            ->assertSee('Riwayat Produksi')
            ->assertSee(route('oee.index'), false)
            ->assertSee('Analisis OEE');

        $this->actingAs($spv)
            ->get(route('oee.index'))
            ->assertOk()
            ->assertSee(route('history.index'), false)
            ->assertSee('Riwayat Produksi');
    }

    private function createSession(
        Machine $machine,
        string $status,
        ?string $endTime,
        ?float $oee,
        float $availability,
        float $quality,
        float $performance,
    ): ProductionSession {
        return ProductionSession::create([
            'machine_id' => $machine->id,
            'operator_name' => 'Operator Test',
            'batch_number' => uniqid('BATCH-', true),
            'start_time' => '2026-09-24 01:00:00',
            'end_time' => $endTime,
            'status' => $status,
            'availability_percent' => $availability,
            'quality_percent' => $quality,
            'performance_percent' => $performance,
            'oee_percent' => $oee,
        ]);
    }
}

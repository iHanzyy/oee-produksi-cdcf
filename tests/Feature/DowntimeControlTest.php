<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\DowntimeCategory;
use App\Models\DowntimeLog;
use App\Models\Machine;
use App\Models\OeeStandard;
use App\Models\Personnel;
use App\Models\Product;
use App\Models\ProductionSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DowntimeControlTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_stop_is_owner_only_and_cannot_create_two_open_logs(): void
    {
        [$operator, $session, $ownerToken] = $this->runningSession();

        $this->postAsBrowser($operator, $ownerToken, route('machine.stop', $session))->assertOk()
            ->assertJsonPath('data.status', 'downtime');
        $this->postAsBrowser($operator, $ownerToken, route('machine.stop', $session))->assertConflict();

        $otherToken = $this->deviceToken($operator);
        $this->postAsBrowser($operator, $otherToken, route('machine.resume', $session), $this->downtimeDetails())
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'Kontrol sesi sudah berpindah ke tablet lain. Tekan Lanjutkan Sesi untuk mengambil alih kembali.'
            );

        $this->assertDatabaseCount('downtime_logs', 1);
        $this->assertDatabaseHas('downtime_logs', ['production_session_id' => $session->id, 'go_time' => null]);
    }

    public function test_resume_requires_details_and_uses_server_time_for_duration(): void
    {
        Carbon::setTestNow('2026-09-24 01:00:00');
        [$operator, $session, $token] = $this->runningSession();
        $this->postAsBrowser($operator, $token, route('machine.stop', $session))->assertOk();

        $this->postAsBrowser($operator, $token, route('machine.resume', $session))->assertUnprocessable();

        Carbon::setTestNow('2026-09-24 01:07:30');
        $this->postAsBrowser($operator, $token, route('machine.resume', $session), $this->downtimeDetails())
            ->assertOk()
            ->assertJsonPath('data.status', 'running')
            ->assertJsonPath('data.summary.totalDowntimeSeconds', 450);

        $log = DowntimeLog::firstOrFail();
        $this->assertSame(450, $log->duration_seconds);
        $this->assertNotNull($log->go_time);
    }

    public function test_running_and_downtime_durations_advance_independently(): void
    {
        Carbon::setTestNow('2026-09-24 01:00:00');
        [$operator, $session, $token] = $this->runningSession();

        Carbon::setTestNow('2026-09-24 01:10:00');
        $this->postAsBrowser($operator, $token, route('machine.stop', $session))
            ->assertOk()
            ->assertJsonPath('data.summary.accumulatedRunningSeconds', 600)
            ->assertJsonPath('data.summary.totalDurationSeconds', 600);

        Carbon::setTestNow('2026-09-24 01:20:00');
        $this->actingAs($operator)
            ->withCredentials()
            ->withCookie('device_token', $token)
            ->getJson(route('production.status', ['session_id' => $session->id]))
            ->assertOk()
            ->assertJsonPath('data.summary.accumulatedRunningSeconds', 600)
            ->assertJsonPath('data.summary.totalDurationSeconds', 600);

        $this->postAsBrowser($operator, $token, route('machine.resume', $session), $this->downtimeDetails())
            ->assertOk()
            ->assertJsonPath('data.summary.accumulatedRunningSeconds', 600)
            ->assertJsonPath('data.summary.runningSince', fn ($value) => $value !== null);

        Carbon::setTestNow('2026-09-24 01:25:00');
        $this->actingAs($operator)
            ->withCredentials()
            ->withCookie('device_token', $token)
            ->getJson(route('production.status', ['session_id' => $session->id]))
            ->assertOk()
            ->assertJsonPath('data.summary.accumulatedRunningSeconds', 600)
            ->assertJsonPath('data.summary.totalDurationSeconds', 900)
            ->assertJsonPath('data.summary.totalDowntimeSeconds', 600);
    }

    public function test_device_clock_test_mode_persists_the_jump_before_starting_downtime(): void
    {
        config(['app.device_clock_test_mode' => true]);
        Carbon::setTestNow('2026-09-24 01:00:00');

        try {
            [$operator, $session, $token] = $this->runningSession();

            $this->postAsBrowser($operator, $token, route('machine.stop', $session), [
                'client_time' => '2026-09-24T07:00:00Z',
            ])->assertOk()
                ->assertJsonPath('data.downtime.stopTime', '2026-09-24T14:00:00+07:00');

            $stopTime = DowntimeLog::where('production_session_id', $session->id)->sole()->stop_time;
            $this->assertSame(21600, (int) $session->start_time->diffInSeconds($stopTime));

            $this->postAsBrowser($operator, $token, route('machine.resume', $session), [
                ...$this->downtimeDetails(),
                'client_time' => '2026-09-24T07:30:00Z',
            ])->assertOk()
                ->assertJsonPath('data.summary.totalDowntimeSeconds', 1800);

            $this->assertSame(1800, DowntimeLog::where('production_session_id', $session->id)->sole()->duration_seconds);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_production_cannot_finish_during_downtime(): void
    {
        [$operator, $session, $token] = $this->runningSession();
        $this->postAsBrowser($operator, $token, route('machine.stop', $session))->assertOk();

        $this->postAsBrowser($operator, $token, route('machine.end', $session), ['confirmed' => true, 'total_output' => 35, 'reject_output' => 0])
            ->assertConflict()
            ->assertJsonPath('message', 'Selesaikan downtime terlebih dahulu.');

        $this->assertSame('downtime', $session->fresh()->status);
    }

    public function test_production_cannot_finish_immediately_after_resuming_from_downtime(): void
    {
        Carbon::setTestNow('2026-09-24 01:00:00');
        [$operator, $session, $token] = $this->runningSession();
        $this->postAsBrowser($operator, $token, route('machine.stop', $session))->assertOk();

        Carbon::setTestNow('2026-09-24 01:05:00');
        $this->postAsBrowser($operator, $token, route('machine.resume', $session), $this->downtimeDetails())
            ->assertOk();

        $this->postAsBrowser($operator, $token, route('machine.end', $session), ['confirmed' => true, 'total_output' => 35, 'reject_output' => 0])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Mesin baru dijalankan kembali. Tunggu 3 detik sebelum menyelesaikan produksi.');

        $this->assertSame('running', $session->fresh()->status);
        $this->assertNull($session->fresh()->end_time);

        Carbon::setTestNow('2026-09-24 01:05:03');
        $this->postAsBrowser($operator, $token, route('machine.end', $session), ['confirmed' => true, 'total_output' => 35, 'reject_output' => 0])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_completed_session_returns_downtime_summary_and_acknowledgement_returns_ready(): void
    {
        Carbon::setTestNow('2026-09-24 01:00:00');
        [$operator, $session, $token] = $this->runningSession();
        $this->postAsBrowser($operator, $token, route('machine.stop', $session))->assertOk();
        Carbon::setTestNow('2026-09-24 01:05:00');
        $this->postAsBrowser($operator, $token, route('machine.resume', $session), $this->downtimeDetails())->assertOk();
        Carbon::setTestNow('2026-09-24 02:00:00');

        $this->postAsBrowser($operator, $token, route('machine.end', $session), ['confirmed' => true, 'total_output' => 35, 'reject_output' => 0])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.summary.hadDowntime', true)
            ->assertJsonPath('data.summary.downtimeCount', 1)
            ->assertJsonPath('data.summary.totalDowntimeSeconds', 300)
            ->assertJsonPath('data.summary.totalDurationSeconds', 3300);

        $this->postAsBrowser($operator, $token, route('production.acknowledge', $session))
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('isController', false);

        $this->assertNull($session->fresh()->controller_token_hash);
    }

    private function runningSession(): array
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $product = Product::create(['area_id' => $area->id, 'product_name' => 'Novasterone']);
        $machine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);
        OeeStandard::create([
            'product_id' => $product->id,
            'machine_id' => $machine->id,
            'process_type' => 'yield_ratio',
            'output_unit' => 'kg',
            'planned_time_minutes' => 480,
            'theoretical_output' => 40,
            'theoretical_output_kg' => 40,
            'ideal_cycle_time_hours_per_kg' => 0.075,
        ]);
        $token = $this->deviceToken($operator);
        $response = $this->postAsBrowser($operator, $token, route('machine.start'), [
            'area_id' => $area->id,
            'product_id' => $product->id,
            'machine_id' => $machine->id,
            'operator_name' => 'Budi',
            'batch_number' => 'BATCH-DOWNTIME-001',
        ])->assertCreated();

        return [$operator, ProductionSession::findOrFail($response->json('data.id')), $token];
    }

    private function downtimeDetails(): array
    {
        $category = DowntimeCategory::firstOrCreate(['category_name' => 'Breakdown Mesin']);
        $personnel = Personnel::firstOrCreate(['name' => 'Tim Maintenance'], ['role' => 'Teknisi ME']);

        return [
            'category_id' => $category->id,
            'personnel_id' => $personnel->id,
            'replaced_component' => 'Bearing',
            'repair_action' => 'Mengganti bearing aus',
        ];
    }

    private function deviceToken(User $operator): string
    {
        return $this->actingAs($operator)
            ->withCookie('device_token', 'invalid')
            ->get(route('dashboard'))
            ->getCookie('device_token')
            ->getValue();
    }

    private function postAsBrowser(User $operator, string $token, string $url, array $data = [])
    {
        return $this->actingAs($operator)
            ->withCredentials()
            ->withCookie('device_token', $token)
            ->postJson($url, $data);
    }
}

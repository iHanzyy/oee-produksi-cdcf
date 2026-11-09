<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Machine;
use App\Models\OeeStandard;
use App\Models\Product;
use App\Models\ProductionSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSessionControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_requires_an_active_oee_standard_for_the_product_and_machine(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $product = Product::create(['area_id' => $area->id, 'product_name' => 'Novasteron']);
        $machine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);

        $this->postStartSession($operator, $this->deviceToken($operator), $area, $product, $machine)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Standar OEE belum tersedia untuk produk dan mesin ini.');

        $this->assertDatabaseCount('production_sessions', 0);

        OeeStandard::create([
            'product_id' => $product->id,
            'machine_id' => $machine->id,
            'theoretical_output_kg' => 40,
            'ideal_cycle_time_hours_per_kg' => 0.075,
            'is_active' => false,
        ]);

        $this->postStartSession($operator, $this->deviceToken($operator), $area, $product, $machine)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Standar OEE belum tersedia untuk produk dan mesin ini.');

        $this->assertDatabaseCount('production_sessions', 0);
    }

    public function test_start_requires_an_active_product_and_machine_from_the_selected_area(): void
    {
        [$operator, $firstArea, $firstProduct, $firstMachine] = $this->productionFixture();
        $secondArea = Area::create(['area_name' => 'Injeksi']);
        $secondProduct = Product::create(['area_id' => $secondArea->id, 'product_name' => 'Produk Injeksi 1']);
        $secondMachine = Machine::create(['area_id' => $secondArea->id, 'machine_name' => 'Mesin Injeksi 1']);
        $token = $this->deviceToken($operator);

        $this->actingAs($operator)
            ->withCredentials()
            ->withCookie('device_token', $token)
            ->postJson(route('machine.start'), [
                'area_id' => $firstArea->id,
                'product_id' => $secondProduct->id,
                'machine_id' => $secondMachine->id,
                'operator_name' => 'Budi',
                'batch_number' => 'BATCH-VALIDATION-001',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id', 'machine_id']);

        $this->assertDatabaseCount('production_sessions', 0);
        $this->assertTrue($firstProduct->refresh()->is_active && $firstMachine->refresh()->is_active);
    }

    public function test_start_requires_batch_number(): void
    {
        [$operator, $area, $product, $machine] = $this->productionFixture();
        $token = $this->deviceToken($operator);

        $this->actingAs($operator)
            ->withCredentials()
            ->withCookie('device_token', $token)
            ->postJson(route('machine.start'), [
                'area_id' => $area->id,
                'product_id' => $product->id,
                'machine_id' => $machine->id,
                'operator_name' => 'Budi',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['batch_number']);

        $this->assertDatabaseCount('production_sessions', 0);
    }

    public function test_start_trims_batch_number_and_returns_it_in_payload(): void
    {
        [$operator, $area, $product, $machine] = $this->productionFixture();
        $token = $this->deviceToken($operator);

        $response = $this->postStartSession(
            $operator,
            $token,
            $area,
            $product,
            $machine,
            '  BATCH-001  ',
        )
            ->assertCreated()
            ->assertJsonPath('data.batchNumber', 'BATCH-001');

        $this->assertDatabaseHas('production_sessions', [
            'id' => $response->json('data.id'),
            'batch_number' => 'BATCH-001',
        ]);
    }

    public function test_batch_number_can_be_reused_for_different_sessions(): void
    {
        [$operator, $area, $product, $firstMachine] = $this->productionFixture();
        $secondMachine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Coating Yenchen']);
        $this->createStandard($product, $secondMachine);

        $this->postStartSession($operator, $this->deviceToken($operator), $area, $product, $firstMachine, 'BATCH-SHARED')
            ->assertCreated();
        $this->postStartSession($operator, $this->deviceToken($operator), $area, $product, $secondMachine, 'BATCH-SHARED')
            ->assertCreated();

        $this->assertSame(2, ProductionSession::query()->where('batch_number', 'BATCH-SHARED')->count());
    }

    public function test_first_start_wins_when_the_same_machine_is_requested_twice(): void
    {
        [$operator, $area, $product, $machine] = $this->productionFixture();
        $firstToken = $this->deviceToken($operator);
        $secondToken = $this->deviceToken($operator);

        $this->postStartSession($operator, $firstToken, $area, $product, $machine)
            ->assertCreated()
            ->assertJsonPath('data.status', 'running');

        $this->postStartSession($operator, $secondToken, $area, $product, $machine)
            ->assertConflict()
            ->assertJsonPath('message', 'Mesin sedang digunakan.');

        $this->assertDatabaseCount('production_sessions', 1);
    }

    public function test_shared_operator_account_can_start_different_machines_from_different_browsers(): void
    {
        [$operator, $area, $product, $firstMachine] = $this->productionFixture();
        $secondMachine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Coating Yenchen']);
        $this->createStandard($product, $secondMachine);
        $firstToken = $this->deviceToken($operator);
        $secondToken = $this->deviceToken($operator);

        $this->postStartSession($operator, $firstToken, $area, $product, $firstMachine)->assertCreated();
        $this->postStartSession($operator, $secondToken, $area, $product, $secondMachine)->assertCreated();

        $this->assertDatabaseCount('production_sessions', 2);
    }

    public function test_one_browser_cannot_control_two_sessions(): void
    {
        [$operator, $area, $product, $firstMachine] = $this->productionFixture();
        $secondMachine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Coating Yenchen']);
        $token = $this->deviceToken($operator);

        $this->postStartSession($operator, $token, $area, $product, $firstMachine)
            ->assertCreated()
            ->assertCookieMissing('device_token');
        $this->assertSame(
            ProductionSession::hashControllerToken($token),
            ProductionSession::query()->firstOrFail()->controller_token_hash,
        );
        $this->postStartSession($operator, $token, $area, $product, $secondMachine)
            ->assertConflict()
            ->assertJsonPath('message', 'Browser ini masih mengontrol sesi lain.');
    }

    public function test_takeover_keeps_the_session_and_revokes_the_previous_browser(): void
    {
        [$operator, $area, $product, $machine] = $this->productionFixture();
        $oldToken = $this->deviceToken($operator);
        $newToken = $this->deviceToken($operator);
        $started = $this->postStartSession($operator, $oldToken, $area, $product, $machine)->assertCreated();
        $sessionId = $started->json('data.id');
        $startTime = $started->json('data.startTime');

        $this->actingAs($operator)
            ->withCookie('device_token', $newToken)
            ->postJson(route('production.takeover', $sessionId))
            ->assertOk()
            ->assertJsonPath('data.id', $sessionId)
            ->assertJsonPath('data.startTime', $startTime);

        $this->withCookie('device_token', $oldToken)
            ->getJson(route('production.status', ['session_id' => $sessionId]))
            ->assertOk()
            ->assertJsonPath('isController', false);

        $this->actingAs($operator)
            ->withCookie('device_token', $oldToken)
            ->postJson(route('machine.stop', $sessionId))
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'Kontrol sesi sudah berpindah ke tablet lain. Tekan Lanjutkan Sesi untuk mengambil alih kembali.'
            );

        $this->withCookie('device_token', $newToken)
            ->getJson(route('production.status', ['session_id' => $sessionId]))
            ->assertOk()
            ->assertJsonPath('isController', true)
            ->assertJsonPath('data.machine.name', 'Mixing');

        $session = ProductionSession::findOrFail($sessionId);
        $this->assertSame(ProductionSession::hashControllerToken($newToken), $session->controller_token_hash);
        $this->assertStringEndsWith('+07:00', $startTime);
    }

    public function test_session_cannot_complete_immediately_after_start(): void
    {
        Carbon::setTestNow('2026-09-25 09:00:00');

        try {
            [$operator, $area, $product, $machine] = $this->productionFixture();
            $token = $this->deviceToken($operator);
            $started = $this->postStartSession($operator, $token, $area, $product, $machine)->assertCreated();
            $session = ProductionSession::findOrFail($started->json('data.id'));

            $this->actingAs($operator)
                ->withCredentials()
                ->withCookie('device_token', $token)
                ->postJson(route('machine.end', $session), ['confirmed' => true, 'actual_output_kg' => 35])
                ->assertStatus(429)
                ->assertJsonPath('message', 'Sesi baru dimulai. Tunggu 3 detik sebelum menyelesaikan produksi.');

            $this->assertSame('running', $session->fresh()->status);
            $this->assertNull($session->fresh()->end_time);

            Carbon::setTestNow('2026-09-25 09:00:03');

            $this->actingAs($operator)
                ->withCredentials()
                ->withCookie('device_token', $token)
                ->postJson(route('machine.end', $session), ['confirmed' => true, 'actual_output_kg' => 35])
                ->assertOk()
                ->assertJsonPath('data.status', 'completed');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_completion_requires_current_endpoint_and_explicit_confirmation(): void
    {
        Carbon::setTestNow('2026-09-25 09:00:00');

        try {
            [$operator, $area, $product, $machine] = $this->productionFixture();
            $token = $this->deviceToken($operator);
            $started = $this->postStartSession($operator, $token, $area, $product, $machine)->assertCreated();
            $session = ProductionSession::findOrFail($started->json('data.id'));

            Carbon::setTestNow('2026-09-25 09:00:03');

            $this->actingAs($operator)
                ->withCredentials()
                ->withCookie('device_token', $token)
                ->postJson(url("/production-sessions/{$session->id}/complete"))
                ->assertConflict()
                ->assertJsonPath('message', 'Halaman tablet masih memakai versi lama. Muat ulang halaman sebelum melanjutkan.');

            $this->actingAs($operator)
                ->withCredentials()
                ->withCookie('device_token', $token)
                ->postJson(route('machine.end', $session), ['actual_output_kg' => 35])
                ->assertStatus(422)
                ->assertJsonPath('message', 'Konfirmasi selesai produksi tidak diterima. Muat ulang halaman lalu coba lagi.');

            $this->assertSame('running', $session->fresh()->status);

            $this->actingAs($operator)
                ->withCredentials()
                ->withCookie('device_token', $token)
                ->postJson(route('machine.end', $session), ['confirmed' => true, 'actual_output_kg' => 35])
                ->assertOk()
                ->assertJsonPath('data.status', 'completed');
        } finally {
            Carbon::setTestNow();
        }
    }

    private function productionFixture(): array
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $product = Product::create(['area_id' => $area->id, 'product_name' => 'Novasteron']);
        $machine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);
        $this->createStandard($product, $machine);

        return [$operator, $area, $product, $machine];
    }

    private function createStandard(Product $product, Machine $machine): void
    {
        OeeStandard::create([
            'product_id' => $product->id,
            'machine_id' => $machine->id,
            'theoretical_output_kg' => 40,
            'ideal_cycle_time_hours_per_kg' => 0.075,
        ]);
    }

    private function postStartSession(User $operator, string $token, Area $area, Product $product, Machine $machine, string $batchNumber = 'BATCH-CONTROL-001')
    {
        return $this->actingAs($operator)
            ->withCredentials()
            ->withCookie('device_token', $token)
            ->postJson(route('machine.start'), [
                'area_id' => $area->id,
                'product_id' => $product->id,
                'machine_id' => $machine->id,
                'operator_name' => 'Budi',
                'batch_number' => $batchNumber,
            ]);
    }

    private function deviceToken(User $operator): string
    {
        return $this->actingAs($operator)
            ->withCookie('device_token', 'invalid')
            ->get(route('dashboard'))
            ->getCookie('device_token')
            ->getValue();
    }
}

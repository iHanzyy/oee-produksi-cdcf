<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Machine;
use App\Models\Product;
use App\Models\ProductionSession;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_browser_receives_and_reuses_an_encrypted_device_cookie(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $firstResponse = $this->actingAs($operator)->get(route('dashboard'));
        $firstResponse->assertCookie('device_token');
        $token = $firstResponse->getCookie('device_token')->getValue();

        $this->assertSame(64, strlen($token));

        $this->withCookie('device_token', $token)->post(route('logout'))->assertCookieMissing('device_token');
        $this->post('/login', ['email' => $operator->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->get(route('dashboard'))->assertCookieMissing('device_token');
    }

    public function test_session_stores_product_operator_name_and_only_a_controller_hash(): void
    {
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $product = Product::create(['area_id' => $area->id, 'product_name' => 'Novasteron']);
        $machine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);
        $token = str_repeat('a', 64);

        $session = ProductionSession::create([
            'machine_id' => $machine->id,
            'product_id' => $product->id,
            'operator_id' => null,
            'operator_name' => 'Budi',
            'controller_token_hash' => ProductionSession::hashControllerToken($token),
            'start_time' => now(),
            'status' => 'running',
        ]);

        $this->assertSame($product->id, $session->product->id);
        $this->assertSame('Budi', $session->operator_name);
        $this->assertSame(hash('sha256', $token), $session->controller_token_hash);
        $this->assertDatabaseMissing('production_sessions', ['controller_token_hash' => $token]);
    }

    public function test_one_controller_hash_cannot_be_attached_to_two_sessions(): void
    {
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $product = Product::create(['area_id' => $area->id, 'product_name' => 'Novasteron']);
        $firstMachine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);
        $secondMachine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Coating Yenchen']);
        $attributes = [
            'product_id' => $product->id,
            'operator_id' => null,
            'operator_name' => 'Budi',
            'controller_token_hash' => hash('sha256', 'device-token'),
            'start_time' => now(),
            'status' => 'running',
        ];

        ProductionSession::create(['machine_id' => $firstMachine->id, ...$attributes]);

        $this->expectException(QueryException::class);

        ProductionSession::create(['machine_id' => $secondMachine->id, ...$attributes]);
    }
}

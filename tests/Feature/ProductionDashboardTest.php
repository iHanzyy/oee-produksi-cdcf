<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\DowntimeCategory;
use App\Models\DowntimeLog;
use App\Models\Machine;
use App\Models\Personnel;
use App\Models\Product;
use App\Models\ProductionSession;
use App\Models\User;
use Database\Seeders\ProductionMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_actions_are_not_stored_as_alpine_auto_evaluated_functions(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertStringNotContainsString('this.confirmAction = () =>', $script);
        $this->assertStringContainsString("this.confirmAction = 'stop';", $script);
        $this->assertStringContainsString("this.confirmAction = 'finish';", $script);
    }

    public function test_ready_screen_contains_searchable_master_inputs_without_oee_cards(): void
    {
        $this->seed(ProductionMasterSeeder::class);
        $operator = User::factory()->create(['role' => 'operator']);

        $response = $this->actingAs($operator)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Mulai Sesi Produksi')
            ->assertSee('Nama Operator')
            ->assertSee('product-options')
            ->assertSee('machine-options')
            ->assertSee('Novasteron')
            ->assertSee('Cetak Gylongli 1')
            ->assertSee('STOP MESIN')
            ->assertSee('Selesai Downtime')
            ->assertSee('Mulai Mesin')
            ->assertSee('Total Downtime')
            ->assertSee('OKE')
            ->assertDontSee('Dashboard OEE')
            ->assertDontSee('Availability')
            ->assertDontSee('Performance');
    }

    public function test_ready_screen_offers_active_sessions_for_takeover(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $product = Product::create(['area_id' => $area->id, 'product_name' => 'Novasteron']);
        $machine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);
        ProductionSession::create([
            'machine_id' => $machine->id,
            'product_id' => $product->id,
            'operator_name' => 'Siti',
            'controller_token_hash' => hash('sha256', 'other-browser'),
            'start_time' => now(),
            'status' => 'running',
        ]);

        $this->actingAs($operator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Lanjutkan Sesi')
            ->assertSee('Mixing')
            ->assertSee('Siti');
    }

    public function test_dashboard_displays_downtime_state_and_form_options_for_active_session(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $product = Product::create(['area_id' => $area->id, 'product_name' => 'Novasteron']);
        $machine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Stripper Indomach']);
        $category = DowntimeCategory::create(['category_name' => 'Troubleshooting Kelistrikan']);
        $personnel = Personnel::create(['name' => 'Teknisi Listrik Pabrik', 'role' => 'Teknisi']);

        $tokenResponse = $this->actingAs($operator)
            ->withCookie('device_token', 'invalid')
            ->get(route('dashboard'));
        $deviceToken = $tokenResponse->getCookie('device_token')->getValue();
        $controllerHash = ProductionSession::hashControllerToken($deviceToken);

        $session = ProductionSession::create([
            'machine_id' => $machine->id,
            'product_id' => $product->id,
            'operator_name' => 'Ahmad',
            'controller_token_hash' => $controllerHash,
            'start_time' => now()->subMinutes(15),
            'status' => 'downtime',
        ]);

        DowntimeLog::create([
            'production_session_id' => $session->id,
            'stop_time' => now()->subMinutes(5),
            'go_time' => null,
        ]);

        $response = $this->actingAs($operator)
            ->withCookie('device_token', $deviceToken)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('MESIN BERHENTI (DOWNTIME)')
            ->assertSee('Total Waktu Mesin Berhenti')
            ->assertSee('Total Waktu Mesin Berjalan')
            ->assertDontSee('Total Waktu Sesi')
            ->assertDontSee('Jam Stop Mesin')
            ->assertSee('Stripper Indomach')
            ->assertSee('Ahmad')
            ->assertSee('Troubleshooting Kelistrikan')
            ->assertSee('Teknisi Listrik Pabrik')
            ->assertSee('Selesai Downtime')
            ->assertSee('Mulai Mesin');
    }
}

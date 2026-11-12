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

    public function test_slow_actions_show_clear_accessible_loading_feedback(): void
    {
        $dashboard = file_get_contents(resource_path('views/dashboard.blade.php'));
        $login = file_get_contents(resource_path('views/auth/login.blade.php'));
        $history = file_get_contents(resource_path('views/history.blade.php'));
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString('x-show="busy"', $dashboard);
        $this->assertStringContainsString('role="status"', $dashboard);
        $this->assertStringContainsString('x-text="busyMessage"', $dashboard);
        $this->assertStringContainsString('Menyiapkan sesi produksi...', $script);
        $this->assertStringContainsString('Menyelesaikan sesi produksi...', $script);
        $this->assertStringContainsString('Sedang masuk...', $login);
        $this->assertStringContainsString('Memuat riwayat...', $history);
    }

    public function test_mobile_timer_resyncs_without_asking_for_a_page_reload(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertSame(1440, config('session.lifetime'));
        $this->assertStringContainsString('clockOffsetMs: config.deviceClockTestMode', $script);
        $this->assertStringContainsString('client_time: new Date().toISOString()', $script);
        $this->assertStringContainsString('!config.deviceClockTestMode && payload?.serverNow', $script);
        $this->assertStringContainsString("document.addEventListener('visibilitychange'", $script);
        $this->assertStringContainsString("window.addEventListener('pageshow'", $script);
        $this->assertStringContainsString('accumulatedRunningSeconds + activeRunningSeconds', $script);
        $this->assertStringNotContainsString('totalSeconds - previousDowntimeSeconds', $script);
        $this->assertStringNotContainsString('Halaman sudah terlalu lama dibuka', $script);
    }

    public function test_confirmation_actions_are_not_stored_as_alpine_auto_evaluated_functions(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertStringNotContainsString('this.confirmAction = () =>', $script);
        $this->assertStringContainsString("this.confirmAction = 'stop';", $script);
        $this->assertStringContainsString("this.confirmAction = 'finish';", $script);
    }

    public function test_running_machine_actions_use_semantic_colors_and_relevant_icons(): void
    {
        $view = file_get_contents(resource_path('views/dashboard.blade.php'));

        $this->assertMatchesRegularExpression(
            '/<button[^>]*@click="askStop"[^>]*class="[^"]*bg-red-700[^"]*"[^>]*>\s*<svg[^>]*aria-hidden="true"[^>]*>.*?<span>STOP MESIN<\/span>/s',
            $view,
        );
        $this->assertMatchesRegularExpression(
            '/<button[^>]*@click="askFinish"[^>]*class="[^"]*bg-emerald-700[^"]*"[^>]*>\s*<svg[^>]*aria-hidden="true"[^>]*>.*?<span>Selesai Produksi<\/span>/s',
            $view,
        );
    }

    public function test_dashboard_contains_searchable_master_inputs_without_fake_oee_metrics(): void
    {
        $this->seed(ProductionMasterSeeder::class);
        $operator = User::factory()->create(['role' => 'operator']);

        $response = $this->actingAs($operator)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('brand/logo-oee.png')
            ->assertDontSee('OEE PRODUKSI')
            ->assertSee('Mulai Sesi Produksi')
            ->assertSee('Nama Operator')
            ->assertSee('Nomor Batch')
            ->assertSee('id="batch_number"', false)
            ->assertSee('x-model.trim="batchNumber"', false)
            ->assertSee('x-text="session?.batchNumber || \'-\'"', false)
            ->assertSee('product-options')
            ->assertSee('machine-options')
            ->assertSee('Novasterone')
            ->assertSee('Cetak Gylongli 1')
            ->assertSee('STOP MESIN')
            ->assertSee('Selesai Downtime')
            ->assertSee('Mulai Mesin')
            ->assertSee('Total Downtime')
            ->assertSee('OKE')
            ->assertDontSee('OEE Mesin')
            ->assertDontSee('78.8%')
            ->assertDontSee('92.0%')
            ->assertDontSee('87.4%')
            ->assertDontSee('98.0%')
            ->assertDontSee('DATA DUMMY')
            ->assertDontSee('Belum terhubung ke data mesin atau input produksi.')
            ->assertSee('Total Output')
            ->assertSee('Reject')
            ->assertSee('Good Output')
            ->assertSee('id="total_output"', false)
            ->assertSee('id="reject_output"', false)
            ->assertSee(':type="outputUnitLabel === \'kg\' ? \'text\' : \'number\'"', false)
            ->assertSee('x-model="totalOutput"', false)
            ->assertSee('x-model="rejectOutput"', false)
            ->assertSee('outputUnitLabel', false)
            ->assertSee('goodOutput', false)
            ->assertSee('Availability')
            ->assertSee('Quality')
            ->assertSee('Performance')
            ->assertSee('OEE')
            ->assertSee('Peringatan')
            ->assertSee('formatOutput(session?.oee?.totalOutput)', false)
            ->assertSee('formatOutput(session?.oee?.rejectOutput)', false)
            ->assertSee('formatOutput(session?.oee?.goodOutput)', false)
            ->assertSee('formatPercent(session?.oee?.availabilityPercent)', false)
            ->assertSee('formatPercent(session?.oee?.qualityPercent)', false)
            ->assertSee('formatPercent(session?.oee?.performancePercent)', false)
            ->assertSee('formatPercent(session?.oee?.oeePercent)', false);

        $script = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString('total_output', $script);
        $this->assertStringContainsString('reject_output', $script);
        $this->assertStringContainsString('goodOutput', $script);
        $this->assertStringContainsString("replace(',', '.')", $script);
        $this->assertStringContainsString('canConfirm()', $script);
    }

    public function test_ready_screen_does_not_list_active_sessions_from_other_machines(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $product = Product::create(['area_id' => $area->id, 'product_name' => 'Novasterone']);
        $machine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);
        ProductionSession::create([
            'machine_id' => $machine->id,
            'product_id' => $product->id,
            'operator_name' => 'Siti',
            'batch_number' => 'BATCH-DASH-001',
            'controller_token_hash' => hash('sha256', 'other-browser'),
            'start_time' => now(),
            'status' => 'running',
        ]);

        $this->actingAs($operator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Siti')
            ->assertDontSee('BATCH-DASH-001');
    }

    public function test_dashboard_displays_downtime_state_and_form_options_for_active_session(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $product = Product::create(['area_id' => $area->id, 'product_name' => 'Novasterone']);
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
            'batch_number' => 'BATCH-DASH-002',
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
            ->assertSee('Durasi Sesi')
            ->assertDontSee('Total Waktu Mesin Berjalan')
            ->assertDontSee('Operating Time')
            ->assertDontSee('Jam Stop Mesin')
            ->assertSee('Stripper Indomach')
            ->assertSee('Ahmad')
            ->assertSee('BATCH-DASH-002')
            ->assertSee('Troubleshooting Kelistrikan')
            ->assertSee('Teknisi Listrik Pabrik')
            ->assertSee('Selesai Downtime')
            ->assertSee('Mulai Mesin');
    }
}

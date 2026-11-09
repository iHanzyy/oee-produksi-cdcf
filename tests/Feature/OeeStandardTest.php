<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Machine;
use App\Models\OeeStandard;
use App\Models\Product;
use Database\Seeders\ProductionMasterSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OeeStandardTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_supports_oee_standards_and_nullable_session_snapshots(): void
    {
        $this->assertTrue(Schema::hasColumns('oee_standards', [
            'product_id',
            'machine_id',
            'theoretical_output_kg',
            'ideal_cycle_time_hours_per_kg',
            'is_active',
        ]));
        $this->assertTrue(Schema::hasColumns('production_sessions', [
            'actual_output_kg',
            'theoretical_output_kg',
            'ideal_cycle_time_hours_per_kg',
            'operating_time_seconds',
            'availability_percent',
            'quality_percent',
            'performance_percent',
            'oee_percent',
        ]));
    }

    public function test_standard_is_unique_per_product_and_machine(): void
    {
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $product = Product::create(['area_id' => $area->id, 'product_name' => 'Alyrenol']);
        $machine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);

        OeeStandard::create([
            'product_id' => $product->id,
            'machine_id' => $machine->id,
            'theoretical_output_kg' => 40,
            'ideal_cycle_time_hours_per_kg' => 1 / 13.33,
        ]);

        $this->expectException(QueryException::class);

        OeeStandard::create([
            'product_id' => $product->id,
            'machine_id' => $machine->id,
            'theoretical_output_kg' => 40,
            'ideal_cycle_time_hours_per_kg' => 1 / 13.33,
        ]);
    }

    public function test_master_seeder_is_idempotent_and_maps_spv_standards_to_production_machines(): void
    {
        $this->seed(ProductionMasterSeeder::class);
        $this->seed(ProductionMasterSeeder::class);

        $standards = OeeStandard::query()
            ->with(['product', 'machine'])
            ->get();

        $this->assertCount(52, $standards);
        foreach (['Mixing', 'Stripper Indomach', 'Cetak Gylongli 1', 'Cetak Gylongli 2'] as $machineName) {
            $this->assertCount(13, $standards->where('machine.machine_name', $machineName));
        }

        $find = fn (string $product, string $machine): ?OeeStandard => $standards->first(
            fn (OeeStandard $standard): bool => $standard->product->product_name === $product
                && $standard->machine->machine_name === $machine
        );

        $this->assertSame('0.0750187547', $find('Alyrenol', 'Mixing')?->ideal_cycle_time_hours_per_kg);
        $this->assertSame('0.0792393027', $find('Andalut', 'Mixing')?->ideal_cycle_time_hours_per_kg);
        $this->assertSame('0.1553398058', $find('Andalut', 'Stripper Indomach')?->ideal_cycle_time_hours_per_kg);
        $this->assertSame('0.0418389910', $find('Andalut', 'Cetak Gylongli 1')?->ideal_cycle_time_hours_per_kg);
        $this->assertSame('0.0418389910', $find('Andalut', 'Cetak Gylongli 2')?->ideal_cycle_time_hours_per_kg);
        $this->assertSame('25.235', $find('Novasteron', 'Mixing')?->theoretical_output_kg);
        $this->assertTrue($standards->every(fn (OeeStandard $standard): bool => $standard->is_active));
        $this->assertFalse($standards->contains(
            fn (OeeStandard $standard): bool => str_starts_with($standard->machine->machine_name, 'Blister DKPS')
        ));
    }
}

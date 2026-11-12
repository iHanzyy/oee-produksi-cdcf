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
            'process_type',
            'output_unit',
            'planned_time_minutes',
            'theoretical_output',
            'ideal_cycle_time_minutes_per_unit',
            'theoretical_output_kg',
            'ideal_cycle_time_hours_per_kg',
            'is_active',
        ]));
        $this->assertTrue(Schema::hasColumns('production_sessions', [
            'process_type',
            'output_unit',
            'theoretical_output',
            'ideal_cycle_time_minutes_per_unit',
            'total_output',
            'reject_output',
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

        $this->assertCount(72, $standards);
        $this->assertCount(17, $standards->where('machine.machine_name', 'Mixing'));
        $this->assertCount(13, $standards->where('machine.machine_name', 'Stripper Indomach'));
        $this->assertCount(17, $standards->where('machine.machine_name', 'Cetak Gylongli 1'));
        $this->assertCount(17, $standards->where('machine.machine_name', 'Cetak Gylongli 2'));
        $this->assertCount(2, $standards->where('machine.machine_name', 'Blister DKPS 1'));
        $this->assertCount(2, $standards->where('machine.machine_name', 'Blister DKPS 2'));
        $this->assertCount(4, $standards->where('machine.machine_name', 'Coating Yenchen'));

        $find = fn (string $product, string $machine): ?OeeStandard => $standards->first(
            fn (OeeStandard $standard): bool => $standard->product->product_name === $product
                && $standard->machine->machine_name === $machine
        );

        $this->assertSame('yield_ratio', $find('Alyrenol', 'Mixing')?->process_type);
        $this->assertSame('kg', $find('Alyrenol', 'Mixing')?->output_unit);
        $this->assertSame(480, $find('Alyrenol', 'Mixing')?->planned_time_minutes);
        $this->assertSame('40.000', $find('Alyrenol', 'Mixing')?->theoretical_output);
        $this->assertNull($find('Alyrenol', 'Mixing')?->ideal_cycle_time_minutes_per_unit);
        $this->assertSame('unit_cycle', $find('Alyrenol', 'Stripper Indomach')?->process_type);
        $this->assertSame('strip', $find('Alyrenol', 'Stripper Indomach')?->output_unit);
        $this->assertSame(960, $find('Alyrenol', 'Stripper Indomach')?->planned_time_minutes);
        $this->assertSame('0.0125000000', $find('Alyrenol', 'Stripper Indomach')?->ideal_cycle_time_minutes_per_unit);
        $this->assertSame('0.0004525000', $find('Trinordiol Fase 1', 'Cetak Gylongli 1')?->ideal_cycle_time_minutes_per_unit);
        $this->assertSame('636000.000', $find('Trinordiol Fase 1', 'Cetak Gylongli 1')?->theoretical_output);
        $this->assertSame(1140, $find('Pil KB Harmonies', 'Cetak Gylongli 2')?->planned_time_minutes);
        $this->assertSame('0.0111000000', $find('Pil KB Harmonies', 'Blister DKPS 1')?->ideal_cycle_time_minutes_per_unit);
        $this->assertSame(3300, $find('Trinordiol', 'Blister DKPS 2')?->planned_time_minutes);
        $this->assertSame(1800, $find('Trinordiol Fase 3', 'Coating Yenchen')?->planned_time_minutes);
        $this->assertTrue($standards->every(fn (OeeStandard $standard): bool => $standard->is_active));
    }
}

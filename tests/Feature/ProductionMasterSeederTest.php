<?php

namespace Tests\Feature;

use Database\Seeders\ProductionMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionMasterSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_master_seeder_is_complete_and_idempotent(): void
    {
        $this->seed(ProductionMasterSeeder::class);
        $this->seed(ProductionMasterSeeder::class);

        $this->assertDatabaseCount('areas', 5);
        $this->assertDatabaseCount('products', 26);
        $this->assertDatabaseCount('machines', 11);
        $this->assertDatabaseCount('downtime_categories', 5);
        $this->assertDatabaseCount('personnels', 3);

        $tabletHormonId = (int) $this->getConnection()
            ->table('areas')
            ->where('area_name', 'Tablet Hormon')
            ->value('id');

        $this->assertSame(22, $this->getConnection()->table('products')->where('area_id', $tabletHormonId)->count());
        $this->assertSame(7, $this->getConnection()->table('machines')->where('area_id', $tabletHormonId)->where('is_active', true)->count());
        $this->assertDatabaseHas('products', ['area_id' => $tabletHormonId, 'product_name' => 'Novasteron']);
        $this->assertDatabaseHas('products', ['area_id' => $tabletHormonId, 'product_name' => 'Dienogest']);
        $this->assertDatabaseHas('machines', ['area_id' => $tabletHormonId, 'machine_name' => 'Cetak Gylongli 1']);
        $this->assertDatabaseHas('machines', ['area_id' => $tabletHormonId, 'machine_name' => 'Coating Yenchen']);
    }
}

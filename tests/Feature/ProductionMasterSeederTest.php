<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Product;
use Database\Seeders\ProductionMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionMasterSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_master_seeder_is_complete_and_idempotent(): void
    {
        $tabletHormon = Area::create(['area_name' => 'Tablet Hormon']);
        $novasteroneId = Product::create(['area_id' => $tabletHormon->id, 'product_name' => 'Novasteron'])->id;
        $norethisteroneId = Product::create(['area_id' => $tabletHormon->id, 'product_name' => 'Norethisterone Tab 5 mg'])->id;
        $pilKbHarmonisId = Product::create(['area_id' => $tabletHormon->id, 'product_name' => 'Pil KB Harmonies'])->id;

        $this->seed(ProductionMasterSeeder::class);
        $this->seed(ProductionMasterSeeder::class);

        $this->assertDatabaseCount('areas', 5);
        $this->assertDatabaseCount('products', 29);
        $this->assertDatabaseCount('machines', 11);
        $this->assertDatabaseCount('downtime_categories', 5);
        $this->assertDatabaseCount('personnels', 3);

        $tabletHormonId = (int) $this->getConnection()
            ->table('areas')
            ->where('area_name', 'Tablet Hormon')
            ->value('id');

        $this->assertSame(25, $this->getConnection()->table('products')->where('area_id', $tabletHormonId)->count());
        $this->assertSame(7, $this->getConnection()->table('machines')->where('area_id', $tabletHormonId)->where('is_active', true)->count());
        $this->assertDatabaseHas('products', ['id' => $novasteroneId, 'area_id' => $tabletHormonId, 'product_name' => 'Novasterone']);
        $this->assertDatabaseHas('products', ['id' => $norethisteroneId, 'area_id' => $tabletHormonId, 'product_name' => 'Norethisterone']);
        $this->assertDatabaseHas('products', ['id' => $pilKbHarmonisId, 'area_id' => $tabletHormonId, 'product_name' => 'Pil KB Harmonis']);
        $this->assertDatabaseMissing('products', ['product_name' => 'Novasteron']);
        $this->assertDatabaseMissing('products', ['product_name' => 'Norethisterone Tab 5 mg']);
        $this->assertDatabaseMissing('products', ['product_name' => 'Pil KB Harmonies']);
        $this->assertDatabaseHas('products', ['area_id' => $tabletHormonId, 'product_name' => 'Dienogest']);
        $this->assertDatabaseHas('products', ['area_id' => $tabletHormonId, 'product_name' => 'Trinordiol Fase 1']);
        $this->assertDatabaseHas('products', ['area_id' => $tabletHormonId, 'product_name' => 'Trinordiol Fase 2']);
        $this->assertDatabaseHas('products', ['area_id' => $tabletHormonId, 'product_name' => 'Trinordiol Fase 3']);
        $this->assertDatabaseHas('machines', ['area_id' => $tabletHormonId, 'machine_name' => 'Cetak Gylongli 1']);
        $this->assertDatabaseHas('machines', ['area_id' => $tabletHormonId, 'machine_name' => 'Coating Yenchen']);
    }
}

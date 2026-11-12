<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Machine;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_area_exposes_active_products_and_machines(): void
    {
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        $activeProduct = Product::create(['area_id' => $area->id, 'product_name' => 'Novasterone']);
        Product::create(['area_id' => $area->id, 'product_name' => 'Produk Lama', 'is_active' => false]);
        $activeMachine = Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);
        Machine::create(['area_id' => $area->id, 'machine_name' => 'Mesin Lama', 'is_active' => false]);

        $this->assertTrue($area->activeProducts->contains($activeProduct));
        $this->assertCount(1, $area->activeProducts);
        $this->assertTrue($area->activeMachines->contains($activeMachine));
        $this->assertCount(1, $area->activeMachines);
    }

    public function test_product_name_must_be_unique_within_an_area(): void
    {
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        Product::create(['area_id' => $area->id, 'product_name' => 'Novasterone']);

        $this->expectException(QueryException::class);

        Product::create(['area_id' => $area->id, 'product_name' => 'Novasterone']);
    }

    public function test_machine_name_must_be_unique_within_an_area(): void
    {
        $area = Area::create(['area_name' => 'Tablet Hormon']);
        Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);

        $this->expectException(QueryException::class);

        Machine::create(['area_id' => $area->id, 'machine_name' => 'Mixing']);
    }

    public function test_same_product_and_machine_names_are_allowed_in_different_areas(): void
    {
        $firstArea = Area::create(['area_name' => 'Tablet Hormon']);
        $secondArea = Area::create(['area_name' => 'Tablet Non Hormon']);

        Product::create(['area_id' => $firstArea->id, 'product_name' => 'Produk 1']);
        Product::create(['area_id' => $secondArea->id, 'product_name' => 'Produk 1']);
        Machine::create(['area_id' => $firstArea->id, 'machine_name' => 'Mesin 1']);
        Machine::create(['area_id' => $secondArea->id, 'machine_name' => 'Mesin 1']);

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('machines', 2);
    }
}

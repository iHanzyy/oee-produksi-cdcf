<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\DowntimeCategory;
use App\Models\Machine;
use App\Models\OeeStandard;
use App\Models\Personnel;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductionMasterSeeder extends Seeder
{
    public function run(): void
    {
        $masters = [
            'Tablet Hormon' => [
                'products' => [
                    'Novasterone', 'Meprolut', 'Norelut', 'Etiro-N', 'Norethisterone',
                    'Luteron', 'Andalut', 'Bifertil', 'Provula', 'Ovulas', 'Ovulet', 'Clomifil',
                    'Alyrenol', 'Progeston', 'Pregnolin', 'Obstanon', 'Prestrenol', 'Maxinor',
                    'Prinolas', 'Pil KB Harmonis', 'Trinordiol', 'Trinordiol Fase 1',
                    'Trinordiol Fase 2', 'Trinordiol Fase 3', 'Dienogest',
                ],
                'machines' => [
                    'Cetak Gylongli 1', 'Cetak Gylongli 2', 'Stripper Indomach', 'Blister DKPS 1',
                    'Blister DKPS 2', 'Mixing', 'Coating Yenchen',
                ],
            ],
            'Tablet Non Hormon' => [
                'products' => ['Produk Tablet Non Hormon 1'],
                'machines' => ['Mesin Tablet Non Hormon 1'],
            ],
            'Injeksi' => [
                'products' => ['Produk Injeksi 1'],
                'machines' => ['Mesin Injeksi 1'],
            ],
            'Implant' => [
                'products' => ['Produk Implant 1'],
                'machines' => ['Mesin Implant 1'],
            ],
            'Packing Hormon' => [
                'products' => ['Produk Packing Hormon 1'],
                'machines' => ['Mesin Packing Hormon 1'],
            ],
        ];

        foreach ($masters as $areaName => $master) {
            $area = Area::firstOrCreate(['area_name' => $areaName]);

            if ($areaName === 'Tablet Hormon') {
                Product::where('area_id', $area->id)->where('product_name', 'Novasteron')->update(['product_name' => 'Novasterone']);
                Product::where('area_id', $area->id)->where('product_name', 'Norethisterone Tab 5 mg')->update(['product_name' => 'Norethisterone']);
                Product::where('area_id', $area->id)->where('product_name', 'Pil KB Harmonies')->update(['product_name' => 'Pil KB Harmonis']);
            }

            Product::where('area_id', $area->id)->update(['is_active' => false]);
            Machine::where('area_id', $area->id)->update(['is_active' => false]);

            foreach ($master['products'] as $productName) {
                Product::updateOrCreate(
                    ['area_id' => $area->id, 'product_name' => $productName],
                    ['is_active' => true],
                );
            }

            foreach ($master['machines'] as $machineName) {
                Machine::updateOrCreate(
                    ['area_id' => $area->id, 'machine_name' => $machineName],
                    ['is_active' => true],
                );
            }
        }

        OeeStandard::query()->update(['is_active' => false]);

        $standardGroups = [
            ['products' => ['Alyrenol', 'Pregnolin', 'Progeston', 'Prestrenol'], 'machines' => ['Stripper Indomach'], 'process_type' => 'unit_cycle', 'output_unit' => 'strip', 'theoretical_output' => 20000, 'ideal_cycle_time_minutes_per_unit' => '0.0125', 'planned_time_minutes' => 960],
            ['products' => ['Bifertil', 'Provula', 'Novasterone', 'Norelut', 'Meprolut', 'Luteron', 'Norethisterone', 'Etiro-N', 'Andalut'], 'machines' => ['Stripper Indomach'], 'process_type' => 'unit_cycle', 'output_unit' => 'strip', 'theoretical_output' => 14000, 'ideal_cycle_time_minutes_per_unit' => '0.0125', 'planned_time_minutes' => 480],
            ['products' => ['Alyrenol', 'Pregnolin', 'Progeston', 'Prestrenol'], 'machines' => ['Cetak Gylongli 1', 'Cetak Gylongli 2'], 'process_type' => 'unit_cycle', 'output_unit' => 'tablet', 'theoretical_output' => 200000, 'ideal_cycle_time_minutes_per_unit' => '0.0004525', 'planned_time_minutes' => 480],
            ['products' => ['Bifertil', 'Provula', 'Novasterone', 'Norelut', 'Meprolut', 'Luteron', 'Norethisterone', 'Etiro-N', 'Andalut'], 'machines' => ['Cetak Gylongli 1', 'Cetak Gylongli 2'], 'process_type' => 'unit_cycle', 'output_unit' => 'tablet', 'theoretical_output' => 140000, 'ideal_cycle_time_minutes_per_unit' => '0.0004525', 'planned_time_minutes' => 480],
            ['products' => ['Trinordiol Fase 1'], 'machines' => ['Cetak Gylongli 1', 'Cetak Gylongli 2'], 'process_type' => 'unit_cycle', 'output_unit' => 'tablet', 'theoretical_output' => 636000, 'ideal_cycle_time_minutes_per_unit' => '0.0004525', 'planned_time_minutes' => 660],
            ['products' => ['Trinordiol Fase 2'], 'machines' => ['Cetak Gylongli 1', 'Cetak Gylongli 2'], 'process_type' => 'unit_cycle', 'output_unit' => 'tablet', 'theoretical_output' => 530000, 'ideal_cycle_time_minutes_per_unit' => '0.0004525', 'planned_time_minutes' => 660],
            ['products' => ['Trinordiol Fase 3'], 'machines' => ['Cetak Gylongli 1', 'Cetak Gylongli 2'], 'process_type' => 'unit_cycle', 'output_unit' => 'tablet', 'theoretical_output' => 1060000, 'ideal_cycle_time_minutes_per_unit' => '0.0004525', 'planned_time_minutes' => 660],
            ['products' => ['Pil KB Harmonis'], 'machines' => ['Cetak Gylongli 1', 'Cetak Gylongli 2'], 'process_type' => 'unit_cycle', 'output_unit' => 'tablet', 'theoretical_output' => 1500000, 'ideal_cycle_time_minutes_per_unit' => '0.0004525', 'planned_time_minutes' => 1140],
            ['products' => ['Pil KB Harmonis'], 'machines' => ['Blister DKPS 1', 'Blister DKPS 2'], 'process_type' => 'unit_cycle', 'output_unit' => 'blister', 'theoretical_output' => 71428, 'ideal_cycle_time_minutes_per_unit' => '0.0111', 'planned_time_minutes' => 1980],
            ['products' => ['Trinordiol'], 'machines' => ['Blister DKPS 1', 'Blister DKPS 2'], 'process_type' => 'unit_cycle', 'output_unit' => 'blister', 'theoretical_output' => 106000, 'ideal_cycle_time_minutes_per_unit' => '0.01667', 'planned_time_minutes' => 3300],
            ['products' => ['Alyrenol', 'Pregnolin', 'Progeston', 'Prestrenol'], 'machines' => ['Mixing'], 'process_type' => 'yield_ratio', 'output_unit' => 'kg', 'theoretical_output' => 40, 'ideal_cycle_time_minutes_per_unit' => null, 'planned_time_minutes' => 480],
            ['products' => ['Bifertil', 'Provula'], 'machines' => ['Mixing'], 'process_type' => 'yield_ratio', 'output_unit' => 'kg', 'theoretical_output' => 25.55, 'ideal_cycle_time_minutes_per_unit' => null, 'planned_time_minutes' => 480],
            ['products' => ['Novasterone', 'Norelut', 'Meprolut', 'Luteron', 'Norethisterone', 'Etiro-N', 'Andalut'], 'machines' => ['Mixing'], 'process_type' => 'yield_ratio', 'output_unit' => 'kg', 'theoretical_output' => 25.235, 'ideal_cycle_time_minutes_per_unit' => null, 'planned_time_minutes' => 480],
            ['products' => ['Trinordiol Fase 1'], 'machines' => ['Mixing'], 'process_type' => 'yield_ratio', 'output_unit' => 'kg', 'theoretical_output' => 34.98, 'ideal_cycle_time_minutes_per_unit' => null, 'planned_time_minutes' => 660],
            ['products' => ['Trinordiol Fase 2'], 'machines' => ['Mixing'], 'process_type' => 'yield_ratio', 'output_unit' => 'kg', 'theoretical_output' => 29.15, 'ideal_cycle_time_minutes_per_unit' => null, 'planned_time_minutes' => 660],
            ['products' => ['Trinordiol Fase 3'], 'machines' => ['Mixing'], 'process_type' => 'yield_ratio', 'output_unit' => 'kg', 'theoretical_output' => 58.3, 'ideal_cycle_time_minutes_per_unit' => null, 'planned_time_minutes' => 660],
            ['products' => ['Pil KB Harmonis'], 'machines' => ['Mixing'], 'process_type' => 'yield_ratio', 'output_unit' => 'kg', 'theoretical_output' => 90, 'ideal_cycle_time_minutes_per_unit' => null, 'planned_time_minutes' => 660],
            ['products' => ['Pil KB Harmonis'], 'machines' => ['Coating Yenchen'], 'process_type' => 'yield_ratio', 'output_unit' => 'kg', 'theoretical_output' => 90, 'ideal_cycle_time_minutes_per_unit' => null, 'planned_time_minutes' => 660],
            ['products' => ['Trinordiol Fase 1'], 'machines' => ['Coating Yenchen'], 'process_type' => 'yield_ratio', 'output_unit' => 'kg', 'theoretical_output' => 34.98, 'ideal_cycle_time_minutes_per_unit' => null, 'planned_time_minutes' => 1320],
            ['products' => ['Trinordiol Fase 2'], 'machines' => ['Coating Yenchen'], 'process_type' => 'yield_ratio', 'output_unit' => 'kg', 'theoretical_output' => 29.15, 'ideal_cycle_time_minutes_per_unit' => null, 'planned_time_minutes' => 1140],
            ['products' => ['Trinordiol Fase 3'], 'machines' => ['Coating Yenchen'], 'process_type' => 'yield_ratio', 'output_unit' => 'kg', 'theoretical_output' => 58.3, 'ideal_cycle_time_minutes_per_unit' => null, 'planned_time_minutes' => 1800],
        ];

        foreach ($standardGroups as $standardGroup) {
            foreach ($standardGroup['products'] as $productName) {
                $product = Product::query()->where('product_name', $productName)->firstOrFail();

                foreach ($standardGroup['machines'] as $machineName) {
                    $machine = Machine::query()->where('machine_name', $machineName)->firstOrFail();
                    $legacyIdealCycle = $standardGroup['ideal_cycle_time_minutes_per_unit'] !== null
                        ? (float) $standardGroup['ideal_cycle_time_minutes_per_unit'] / 60
                        : ($standardGroup['planned_time_minutes'] / 60) / $standardGroup['theoretical_output'];

                    OeeStandard::updateOrCreate(
                        ['product_id' => $product->id, 'machine_id' => $machine->id],
                        [
                            'process_type' => $standardGroup['process_type'],
                            'output_unit' => $standardGroup['output_unit'],
                            'planned_time_minutes' => $standardGroup['planned_time_minutes'],
                            'theoretical_output' => $standardGroup['theoretical_output'],
                            'ideal_cycle_time_minutes_per_unit' => $standardGroup['ideal_cycle_time_minutes_per_unit'],
                            'theoretical_output_kg' => $standardGroup['theoretical_output'],
                            'ideal_cycle_time_hours_per_kg' => $legacyIdealCycle,
                            'is_active' => true,
                        ],
                    );
                }
            }
        }

        foreach (['Breakdown Mesin', 'Setup / Changeover', 'Troubleshooting Kelistrikan', 'Preventive Maintenance', 'Material Delay'] as $categoryName) {
            DowntimeCategory::firstOrCreate(['category_name' => $categoryName]);
        }

        foreach ([
            ['name' => 'Operator Internal', 'role' => 'Operator Produksi'],
            ['name' => 'Tim Maintenance', 'role' => 'Teknisi ME'],
            ['name' => 'Pihak Ketiga', 'role' => 'Vendor'],
        ] as $personnel) {
            Personnel::updateOrCreate(['name' => $personnel['name']], ['role' => $personnel['role']]);
        }
    }
}

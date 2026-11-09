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
                    'Novasteron', 'Meprolut', 'Norelut', 'Etiro-N', 'Norethisterone Tab 5 mg',
                    'Luteron', 'Andalut', 'Bifertil', 'Provula', 'Ovulas', 'Ovulet', 'Clomifil',
                    'Alyrenol', 'Progeston', 'Pregnolin', 'Obstanon', 'Prestrenol', 'Maxinor',
                    'Prinolas', 'Pil KB Harmonies', 'Trinordiol', 'Dienogest',
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

        foreach ([
            [
                'products' => ['Alyrenol', 'Pregnolin', 'Progeston', 'Prestrenol'],
                'theoretical_output_kg' => 40,
                'cycles' => [
                    'Mixing' => '0.0750187547',
                    'Stripper Indomach' => '0.1292500000',
                    'Cetak Gylongli 1' => '0.0377073906',
                    'Cetak Gylongli 2' => '0.0377073906',
                ],
            ],
            [
                'products' => ['Bifertil', 'Provula'],
                'theoretical_output_kg' => 25.55,
                'cycles' => [
                    'Mixing' => '0.2347417840',
                    'Stripper Indomach' => '0.1534246575',
                    'Cetak Gylongli 1' => '0.0413231678',
                    'Cetak Gylongli 2' => '0.0413231678',
                ],
            ],
            [
                'products' => ['Novasteron', 'Norelut', 'Meprolut', 'Luteron', 'Norethisterone Tab 5 mg', 'Etiro-N', 'Andalut'],
                'theoretical_output_kg' => 25.235,
                'cycles' => [
                    'Mixing' => '0.0792393027',
                    'Stripper Indomach' => '0.1553398058',
                    'Cetak Gylongli 1' => '0.0418389910',
                    'Cetak Gylongli 2' => '0.0418389910',
                ],
            ],
        ] as $standardGroup) {
            foreach ($standardGroup['products'] as $productName) {
                $product = Product::query()->where('product_name', $productName)->firstOrFail();

                foreach ($standardGroup['cycles'] as $machineName => $idealCycleTime) {
                    $machine = Machine::query()->where('machine_name', $machineName)->firstOrFail();

                    OeeStandard::updateOrCreate(
                        ['product_id' => $product->id, 'machine_id' => $machine->id],
                        [
                            'theoretical_output_kg' => $standardGroup['theoretical_output_kg'],
                            'ideal_cycle_time_hours_per_kg' => $idealCycleTime,
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

<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\DowntimeCategory;
use App\Models\Machine;
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
                    'Cetak Gylongli 1', 'Cetak Gylonglu 2', 'Stripper Indomach', 'Blister DKPS 1',
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

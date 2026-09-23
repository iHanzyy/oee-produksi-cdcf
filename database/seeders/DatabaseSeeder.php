<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Area;
use App\Models\Machine;
use App\Models\DowntimeCategory;
use App\Models\Personnel;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['name' => 'Supervisor Pabrik', 'email' => 'supervisor', 'password' => bcrypt('1234'), 'role' => 'supervisor']);
        User::create(['name' => 'Operator Produksi', 'email' => 'operator', 'password' => bcrypt('12345678'), 'role' => 'operator']);

        $areas = ['Tablet Hormon', 'Tablet Non Hormon', 'Injeksi', 'Implant', 'Packing Hormon'];
        foreach ($areas as $area) {
            Area::create(['area_name' => $area]);
        }

        Machine::create(['area_id' => 1, 'machine_name' => 'Mesin Cetak Tablet A']);
        Machine::create(['area_id' => 2, 'machine_name' => 'Mesin Cetak Tablet B']);
        Machine::create(['area_id' => 3, 'machine_name' => 'Mesin Filling Injeksi']);
        Machine::create(['area_id' => 5, 'machine_name' => 'Blister Packing Line']);

        $categories = ['Breakdown Mesin', 'Setup / Changeover', 'Troubleshooting Kelistrikan', 'Preventive Maintenance', 'Material Delay'];
        foreach ($categories as $cat) {
            DowntimeCategory::create(['category_name' => $cat]);
        }

        Personnel::create(['name' => 'Operator Internal', 'role' => 'Operator Produksi']);
        Personnel::create(['name' => 'Tim Maintenance', 'role' => 'Teknisi ME']);
        Personnel::create(['name' => 'Pihak Ketiga', 'role' => 'Vendor']);
    }
}

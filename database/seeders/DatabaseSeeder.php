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
        // 1. Akun Login Permanen
        User::create(['name' => 'Supervisor Pabrik', 'email' => 'supervisor', 'password' => bcrypt('1234')]);
        User::create(['name' => 'Operator Produksi', 'email' => 'operator', 'password' => bcrypt('12345678')]);

        // 2. Data Area
        $areas = ['Tablet Hormon', 'Tablet Non Hormon', 'Injeksi', 'Implant', 'Packing Hormon'];
        foreach ($areas as $area) {
            Area::create(['area_name' => $area]);
        }

        // 3. Data Mesin
        Machine::create(['area_id' => 1, 'machine_name' => 'Mesin Cetak Tablet A']);
        Machine::create(['area_id' => 2, 'machine_name' => 'Mesin Cetak Tablet B']);
        Machine::create(['area_id' => 3, 'machine_name' => 'Mesin Filling Injeksi']);
        Machine::create(['area_id' => 5, 'machine_name' => 'Blister Packing Line']);

        // 4. Kategori Kendala
        $categories = ['Breakdown Mesin', 'Setup / Changeover', 'Troubleshooting Kelistrikan', 'Preventive Maintenance', 'Material Delay'];
        foreach ($categories as $cat) {
            DowntimeCategory::create(['category_name' => $cat]);
        }

        // 5. Opsi Pelaksana
        Personnel::create(['name' => 'Operator Internal', 'role' => 'Operator Produksi']);
        Personnel::create(['name' => 'Tim Maintenance', 'role' => 'Teknisi ME']);
        Personnel::create(['name' => 'Pihak Ketiga', 'role' => 'Vendor']);
    }
}
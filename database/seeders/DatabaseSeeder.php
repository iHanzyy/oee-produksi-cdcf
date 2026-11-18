<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductionMasterSeeder::class);

        if (! app()->isProduction()) {
            $this->call([
                UserSeeder::class,
                PresentationHistorySeeder::class,
            ]);
        }
    }
}

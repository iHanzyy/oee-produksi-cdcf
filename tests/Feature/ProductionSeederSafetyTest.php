<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PresentationHistorySeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ProductionSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_only_loads_master_data_in_production(): void
    {
        $this->app['env'] = 'production';

        $seeder = new DatabaseSeeder;
        $seeder->setContainer($this->app);
        $seeder->run();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('production_sessions', 0);
        $this->assertDatabaseCount('areas', 5);
    }

    public function test_default_user_seeder_refuses_production(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(LogicException::class);
        $this->seed(UserSeeder::class);
    }

    public function test_presentation_seeder_refuses_production(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(LogicException::class);
        $this->seed(PresentationHistorySeeder::class);
    }
}

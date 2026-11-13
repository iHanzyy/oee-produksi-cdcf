<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_seeder_creates_current_accounts_and_removes_legacy_spv_login(): void
    {
        User::factory()->create(['email' => 'spv', 'role' => 'spv']);

        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        foreach ([
            'operator' => ['operator', 'operator', 'Operator Produksi'],
            'supervisor' => ['supervisor', 'spv', 'Supervisor Produksi'],
            'manager' => ['manager', 'manager', 'Manager Produksi'],
            'AMD' => ['AMD', 'manager', 'Associate Managing Director'],
        ] as $username => [$password, $role, $name]) {
            $user = User::query()->where('email', $username)->firstOrFail();

            $this->assertSame($name, $user->name);
            $this->assertSame($role, $user->role);
            $this->assertTrue(Hash::check($password, $user->password));
        }

        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseMissing('users', ['email' => 'spv']);
    }
}

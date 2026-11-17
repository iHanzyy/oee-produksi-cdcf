<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_management_routes_are_not_available(): void
    {
        $user = User::factory()->create(['role' => 'operator']);

        $this->actingAs($user)->get('/profile')->assertNotFound();
        $this->actingAs($user)->patch('/profile')->assertNotFound();
        $this->actingAs($user)->delete('/profile')->assertNotFound();
    }

    public function test_navigation_only_shows_links_allowed_for_the_role(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $spv = User::factory()->create(['role' => 'spv']);

        $this->actingAs($operator)
            ->get(route('dashboard'))
            ->assertSee(route('dashboard'), false)
            ->assertDontSee(route('history.index'), false);

        $this->actingAs($spv)
            ->get(route('history.index'))
            ->assertSee(route('history.index'), false)
            ->assertDontSee(route('dashboard'), false);
    }

    public function test_non_operator_roles_share_the_palette_and_action_components(): void
    {
        foreach ([
            ['role' => 'spv', 'name' => 'Supervisor Produksi'],
            ['role' => 'manager', 'name' => 'Manager Produksi'],
            ['role' => 'manager', 'name' => 'Associate Managing Director'],
        ] as $account) {
            $user = User::factory()->create($account);

            $this->actingAs($user)
                ->get(route('history.index'))
                ->assertOk()
                ->assertSee('bg-secondary', false)
                ->assertSee('bg-background', false)
                ->assertSee('bg-primary', false)
                ->assertSee($account['name']);
        }
    }
}

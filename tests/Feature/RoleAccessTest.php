<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_only_access_production_control_routes(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->get(route('dashboard'))->assertOk();
        $this->actingAs($operator)->get(route('history.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($operator)->get(route('history.export'))->assertRedirect(route('dashboard'));
    }

    public function test_wrong_role_pages_redirect_to_the_allowed_home(): void
    {
        foreach (['spv', 'manager'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('history.index'))->assertOk();
            $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('history.index'));
            $this->actingAs($user)->post(route('machine.start'))->assertForbidden();
        }

        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->get(route('history.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($operator)->getJson(route('history.active_sessions'))->assertForbidden();
    }

    public function test_login_redirects_users_to_their_allowed_home(): void
    {
        foreach ([
            'operator' => route('dashboard', absolute: false),
            'spv' => route('history.index', absolute: false),
            'manager' => route('history.index', absolute: false),
        ] as $role => $destination) {
            $user = User::factory()->create([
                'email' => $role.'@example.com',
                'password' => 'password',
                'role' => $role,
            ]);

            $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect($destination);

            $this->post('/logout');
        }
    }

    public function test_login_ignores_a_forbidden_intended_page(): void
    {
        $spv = User::factory()->create([
            'email' => 'spv@example.com',
            'password' => 'password',
            'role' => 'spv',
        ]);

        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $spv->email,
            'password' => 'password',
        ])->assertRedirect(route('history.index'));
    }
}

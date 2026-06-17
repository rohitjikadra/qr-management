<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_admin_root(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_banned_admin_cannot_access_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'banned']);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertRedirect(route('account.suspended'));

        $this->assertGuest();
    }

    public function test_admin_root_redirects_to_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertRedirect('/admin/dashboard');
    }

    public function test_admin_dashboard_renders_inertia(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/dashboard')
                ->has('stats.total_users')
                ->has('stats.mrr')
                ->has('signupsChart.labels')
                ->has('qrTypeChart.labels')
            );
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_regular_user_login_redirects_to_user_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }
}

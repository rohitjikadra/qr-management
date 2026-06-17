<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_admin_team_page(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['role' => 'admin', 'email' => 'ops@example.com']);
        User::factory()->create(['role' => 'user', 'email' => 'customer@example.com']);

        $this->actingAs($superAdmin)
            ->get('/admin/team')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/team/index')
                ->has('members.data', 2)
                ->where('members.total', 2)
            );
    }

    public function test_admin_cannot_access_admin_team_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/team')->assertForbidden();
    }

    public function test_regular_user_cannot_access_admin_team_page(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/team')->assertForbidden();
    }

    public function test_admin_team_search_filters_results(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['role' => 'admin', 'name' => 'Find Me Admin']);
        User::factory()->create(['role' => 'admin', 'name' => 'Other Admin']);

        $this->actingAs($superAdmin)
            ->get('/admin/team?search=Find+Me')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.name', 'Find Me Admin')
            );
    }

    public function test_admin_team_role_filter_works(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['role' => 'admin']);

        $this->actingAs($superAdmin)
            ->get('/admin/team?role=super_admin')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('members.data', 1)
                ->where('members.data.0.role', 'super_admin')
            );
    }

    public function test_super_admin_can_view_add_admin_form(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->get('/admin/team/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/team/create'));
    }

    public function test_super_admin_can_create_admin_account(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->post('/admin/team', [
                'name' => 'New Admin',
                'email' => 'newadmin@example.com',
                'password' => 'password123',
                'status' => 'active',
                'email_verified' => true,
            ])
            ->assertRedirect(route('admin.team.index'));

        $admin = User::where('email', 'newadmin@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role->value);
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_admin_cannot_create_admin_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/team/create')
            ->assertForbidden();

        $this->actingAs($admin)
            ->post('/admin/team', [
                'name' => 'Blocked Admin',
                'email' => 'blocked@example.com',
                'password' => 'password123',
                'status' => 'active',
                'email_verified' => true,
            ])
            ->assertForbidden();

        $this->assertNull(User::where('email', 'blocked@example.com')->first());
    }

    public function test_super_admin_cannot_create_regular_user_via_team_form(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->post('/admin/team', [
                'name' => 'Not A Customer',
                'email' => 'notcustomer@example.com',
                'password' => 'password123',
                'status' => 'active',
                'email_verified' => true,
                'role' => 'user',
            ])
            ->assertRedirect(route('admin.team.index'));

        $this->assertSame('admin', User::where('email', 'notcustomer@example.com')->first()?->role->value);
    }
}

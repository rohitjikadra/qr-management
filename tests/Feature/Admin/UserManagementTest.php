<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_users(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_users_page_shows_only_customer_accounts(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'email' => 'super@example.com',
            'role' => 'super_admin',
        ]);

        User::factory()->create([
            'email' => 'user@user.com',
            'role' => 'user',
        ]);

        User::factory()->create([
            'email' => 'another-user@example.com',
            'role' => 'user',
        ]);

        $expectedTotal = User::query()->where('role', 'user')->count();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/users/index')
                ->where('totalUsers', $expectedTotal)
                ->where('users.total', $expectedTotal)
                ->has('users.data', 2)
            );
    }

    public function test_admin_users_list_total_matches_customer_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(10)->create(['role' => 'user']);
        User::factory()->create(['role' => 'admin']);

        $expectedTotal = User::query()->where('role', 'user')->count();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('users.total', $expectedTotal)
                ->where('totalUsers', $expectedTotal)
            );
    }

    public function test_admin_users_search_filters_results(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@example.com']);
        User::factory()->create(['email' => 'findme@example.com', 'name' => 'Find Me']);
        User::factory()->create(['email' => 'other@example.com', 'name' => 'Other Person']);

        $this->actingAs($admin)
            ->get('/admin/users?search=findme')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('users.total', 1)
                ->where('users.data.0.email', 'findme@example.com')
            );
    }

    public function test_admin_users_list_excludes_admin_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(3)->create(['role' => 'user']);
        User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('users.data', 3)
                ->where('users.total', 3)
            );
    }

    public function test_admin_can_view_user_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'name' => 'Detail User',
            'email' => 'detail@example.com',
            'billing_discount_percent' => 25,
        ]);

        $this->actingAs($admin)
            ->get("/admin/users/{$user->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/users/show')
                ->where('user.id', $user->id)
                ->where('user.email', 'detail@example.com')
                ->where('user.billing_discount_percent', 25)
            );
    }

    public function test_regular_user_cannot_view_admin_user_detail(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($user)
            ->get("/admin/users/{$target->id}")
            ->assertForbidden();
    }

    public function test_admin_can_view_user_edit_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => 'Edit Target']);

        $this->actingAs($admin)
            ->get("/admin/users/{$user->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/users/edit')
                ->where('user.name', 'Edit Target')
                ->where('canEditRole', false)
            );
    }

    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put("/admin/users/{$user->id}", [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'status' => 'active',
                'country' => 'IN',
                'email_verified' => true,
            ])
            ->assertRedirect(route('admin.users.show', $user));

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame('IN', $user->country);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_admin_can_mark_user_email_as_unverified(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->put("/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'status' => 'active',
                'email_verified' => false,
            ])
            ->assertRedirect(route('admin.users.show', $user));

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_admin_can_mark_unverified_user_email_as_verified(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->unverified()->create();

        $this->actingAs($admin)
            ->put("/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'status' => 'active',
                'email_verified' => true,
            ])
            ->assertRedirect(route('admin.users.show', $user));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_admin_cannot_change_user_role_without_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->put("/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'admin',
                'status' => 'active',
                'email_verified' => true,
            ])
            ->assertRedirect(route('admin.users.show', $user));

        $this->assertSame('user', $user->fresh()->role->value);
    }

    public function test_super_admin_can_change_user_role(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($superAdmin)
            ->put("/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'admin',
                'status' => 'active',
                'email_verified' => true,
            ])
            ->assertRedirect(route('admin.users.show', $user));

        $this->assertSame('admin', $user->fresh()->role->value);
    }

    public function test_users_create_route_is_not_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/users/create')->assertNotFound();
        $this->actingAs($admin)->post('/admin/users', [])->assertStatus(405);
    }

    public function test_admin_can_ban_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($admin)
            ->post("/admin/users/{$user->id}/ban")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('banned', $user->fresh()->status->value);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.banned',
            'entity_type' => $user->getMorphClass(),
            'entity_id' => $user->id,
        ]);
    }

    public function test_admin_can_unban_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['status' => 'banned']);

        $this->actingAs($admin)
            ->post("/admin/users/{$user->id}/unban")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('active', $user->fresh()->status->value);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.unbanned',
            'entity_id' => $user->id,
        ]);
    }

    public function test_admin_cannot_ban_themselves(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post("/admin/users/{$admin->id}/ban")
            ->assertForbidden();
    }

    public function test_admin_cannot_ban_other_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post("/admin/users/{$otherAdmin->id}/ban")
            ->assertForbidden();
    }

    public function test_super_admin_can_ban_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($superAdmin)
            ->post("/admin/users/{$admin->id}/ban")
            ->assertRedirect();

        $this->assertSame('banned', $admin->fresh()->status->value);
    }

    public function test_admin_can_set_billing_discount(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['billing_discount_percent' => null]);

        $this->actingAs($admin)
            ->post("/admin/users/{$user->id}/discount", [
                'billing_discount_percent' => 50,
                'billing_note' => 'Partner deal',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame(50, $user->billing_discount_percent);
        $this->assertSame('Partner deal', $user->billing_note);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.billing_discount_set',
            'entity_id' => $user->id,
        ]);
    }

    public function test_admin_can_clear_billing_discount(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['billing_discount_percent' => 25]);

        $this->actingAs($admin)
            ->post("/admin/users/{$user->id}/discount", [
                'billing_discount_percent' => null,
                'billing_note' => null,
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertNull($user->billing_discount_percent);
    }

    public function test_admin_can_grant_complimentary_subscription(): void
    {
        $this->seed(PlanSeeder::class);

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $plan = Plan::where('slug', 'pro_monthly')->firstOrFail();

        $this->actingAs($admin)
            ->post("/admin/users/{$user->id}/complimentary", [
                'plan_id' => $plan->id,
                'duration_days' => 30,
                'admin_note' => 'Beta tester reward',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $subscription = Subscription::where('user_id', $user->id)->first();
        $this->assertNotNull($subscription);
        $this->assertTrue($subscription->is_complimentary);
        $this->assertSame($plan->id, $subscription->plan_id);
        $this->assertSame('manual', $subscription->gateway);
        $this->assertSame('Beta tester reward', $subscription->admin_note);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'subscription.complimentary_granted',
            'entity_id' => $subscription->id,
        ]);
    }

    public function test_grant_complimentary_requires_admin_note(): void
    {
        $this->seed(PlanSeeder::class);

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $plan = Plan::where('slug', 'pro_monthly')->firstOrFail();

        $this->actingAs($admin)
            ->post("/admin/users/{$user->id}/complimentary", [
                'plan_id' => $plan->id,
                'duration_days' => 30,
                'admin_note' => '',
            ])
            ->assertSessionHasErrors('admin_note');
    }
}

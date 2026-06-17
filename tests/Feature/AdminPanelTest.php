<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_root_redirects_to_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertRedirect('/admin/dashboard');
    }

    public function test_admin_can_access_custom_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/dashboard'));
    }

    public function test_banned_admin_cannot_access_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'banned']);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertRedirect(route('account.suspended'));

        $this->assertGuest();
    }

    public function test_admin_can_view_plans_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/plans')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/plans/index')
                ->has('plans', 3)
            );
    }

    public function test_admin_can_view_subscriptions_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $plan = Plan::where('slug', 'pro_monthly')->firstOrFail();

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => 'razorpay',
            'gateway_subscription_id' => 'sub_test_123',
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/subscriptions')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/subscriptions/index')
                ->has('subscriptions.data', 1)
            );
    }
}

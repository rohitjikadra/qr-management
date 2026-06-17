<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\User;
use App\Services\AdminSubscriptionService;
use App\Services\PlanLimitService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    public function test_admin_can_grant_complimentary_pro_access(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        $user = User::factory()->create();
        $plan = Plan::where('slug', 'pro_monthly')->firstOrFail();

        $subscription = app(AdminSubscriptionService::class)->grantComplimentary(
            $user,
            $plan,
            now()->addDays(30),
            'QA tester account',
            $admin,
        );

        $this->assertTrue($subscription->is_complimentary);
        $this->assertSame('manual', $subscription->gateway);
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertSame('pro_monthly', app(PlanLimitService::class)->planFor($user->fresh())->slug);
    }

    public function test_admin_can_set_billing_discount_on_user(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        $user = User::factory()->create();
        $plan = Plan::where('slug', 'pro_monthly')->firstOrFail();

        app(AdminSubscriptionService::class)->setBillingDiscount($user, 50, 'Partner deal', $admin);

        $user->refresh();

        $this->assertSame(50, $user->billing_discount_percent);
        $this->assertSame(124.5, $user->discountedPriceFor($plan));
    }

    public function test_admin_can_extend_and_revoke_manual_subscription(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        $user = User::factory()->create();
        $plan = Plan::where('slug', 'pro_monthly')->firstOrFail();
        $service = app(AdminSubscriptionService::class);

        $subscription = $service->grantComplimentary(
            $user,
            $plan,
            now()->addDays(7),
            'Short test',
            $admin,
        );

        $service->extendManual($subscription, 7, 'Extended for demo', $admin);

        $this->assertTrue($subscription->fresh()->expires_at->isAfter(now()->addDays(13)));

        $service->revokeManual($subscription->fresh(), 'Test complete', $admin);

        $this->assertSame(SubscriptionStatus::Expired, $subscription->fresh()->status);
    }
}

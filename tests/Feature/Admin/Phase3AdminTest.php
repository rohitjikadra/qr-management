<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase3AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    public function test_admin_subscriptions_index_and_show(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $plan = Plan::where('slug', 'pro_monthly')->firstOrFail();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => 'manual',
            'gateway_subscription_id' => 'manual-test',
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_complimentary' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/subscriptions')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/subscriptions/index')
                ->has('subscriptions.data', 1)
            );

        $this->actingAs($admin)
            ->get("/admin/subscriptions/{$subscription->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/subscriptions/show')
                ->where('canExtend', true)
                ->where('canRevoke', true)
            );
    }

    public function test_admin_can_extend_manual_subscription(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $plan = Plan::where('slug', 'pro_monthly')->firstOrFail();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => 'manual',
            'gateway_subscription_id' => 'manual-extend',
            'status' => 'active',
            'expires_at' => now()->addDays(5),
            'is_complimentary' => true,
        ]);

        $this->actingAs($admin)
            ->post("/admin/subscriptions/{$subscription->id}/extend", [
                'days' => 30,
                'admin_note' => 'Partner extension',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($subscription->fresh()->expires_at->isAfter(now()->addDays(30)));
        $this->assertDatabaseHas('audit_logs', ['action' => 'subscription.manual_extended']);
    }

    public function test_admin_can_revoke_manual_subscription(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $plan = Plan::where('slug', 'pro_monthly')->firstOrFail();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => 'manual',
            'gateway_subscription_id' => 'manual-revoke',
            'status' => 'active',
            'expires_at' => now()->addDays(30),
            'is_complimentary' => true,
        ]);

        $this->actingAs($admin)
            ->post("/admin/subscriptions/{$subscription->id}/revoke", [
                'admin_note' => 'Abuse',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('expired', $subscription->fresh()->status->value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'subscription.manual_revoked']);
    }

    public function test_admin_payments_index_and_show(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $payment = Payment::create([
            'user_id' => $user->id,
            'gateway' => 'razorpay',
            'gateway_payment_id' => 'pay_admin_test',
            'amount' => 249,
            'currency' => 'INR',
            'status' => 'paid',
            'meta' => ['event' => 'payment.captured', 'id' => 'pay_admin_test'],
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/payments')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/payments/index')->has('payments.data', 1));

        $this->actingAs($admin)
            ->get("/admin/payments/{$payment->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/payments/show')
                ->where('payment.gateway_payment_id', 'pay_admin_test')
            );
    }

    public function test_admin_settings_crud(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::set('support_email', 'old@example.com');

        $this->actingAs($admin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/settings/index')
                ->has('settings', 1)
            );

        $this->actingAs($admin)
            ->put('/admin/settings', [
                'settings' => [
                    ['key' => 'support_email', 'value' => 'support@example.com'],
                    ['key' => 'feature_flag', 'value' => 'on'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('support@example.com', Setting::get('support_email'));
        $this->assertSame('on', Setting::get('feature_flag'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.updated']);
    }

    public function test_admin_branding_settings_update(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/settings/branding')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/settings/branding'));

        $this->actingAs($admin)
            ->post('/admin/settings/branding', [
                'project_name' => 'QR Manager',
                'seo_title' => 'Best QR App',
                'seo_description' => 'Create QR codes easily',
                'logo' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('QR Manager', Setting::get('project_name'));
        $this->assertNotNull(Setting::get('logo_path'));
        Storage::disk('public')->assertExists(Setting::get('logo_path'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.branding_updated']);
    }

    public function test_branding_project_name_is_shared_on_user_dashboard(): void
    {
        Storage::fake('public');
        Setting::set('project_name', 'My QR SaaS');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('name', 'My QR SaaS'));
    }

    public function test_branding_logo_url_is_shared_when_logo_uploaded(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('logo.png')->store('branding', 'public');
        Setting::set('logo_path', $path);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('branding.logo_url', Storage::disk('public')->url($path))
            );
    }

    public function test_admin_audit_logs_index_and_show(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($admin)->post("/admin/users/{$user->id}/ban");

        $log = AuditLog::where('action', 'user.banned')->first();
        $this->assertNotNull($log);

        $this->actingAs($admin)
            ->get('/admin/audit-logs')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/audit-logs/index')
                ->has('logs.data', 1)
            );

        $this->actingAs($admin)
            ->get("/admin/audit-logs/{$log->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/audit-logs/show')
                ->where('log.action', 'user.banned')
            );
    }

    public function test_admin_dashboard_includes_charts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/dashboard')
                ->has('signupsChart.labels', 30)
                ->has('signupsChart.values', 30)
                ->has('qrTypeChart')
            );
    }
}

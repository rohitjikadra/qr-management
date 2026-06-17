<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\QrCode;
use App\Models\QrReport;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    public function test_super_admin_can_impersonate_user(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($superAdmin)
            ->post("/admin/users/{$user->id}/impersonate")
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame($superAdmin->id, session('impersonator_id'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.impersonated']);
    }

    public function test_admin_cannot_impersonate_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->post("/admin/users/{$user->id}/impersonate")
            ->assertForbidden();
    }

    public function test_user_can_stop_impersonation(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($superAdmin)
            ->withSession(['impersonator_id' => $superAdmin->id])
            ->actingAs($user)
            ->post('/impersonation/stop')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($superAdmin);
        $this->assertNull(session('impersonator_id'));
    }

    public function test_admin_can_resend_verification_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->unverified()->create();

        $this->actingAs($admin)
            ->post("/admin/users/{$user->id}/resend-verification")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.verification_resent']);
    }

    public function test_super_admin_can_delete_user(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($superAdmin)
            ->delete("/admin/users/{$user->id}")
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted($user);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deleted']);
    }

    public function test_admin_cannot_delete_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->delete("/admin/users/{$user->id}")
            ->assertForbidden();
    }

    public function test_admin_can_mark_payment_refunded(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $payment = Payment::create([
            'user_id' => $user->id,
            'gateway' => 'razorpay',
            'gateway_payment_id' => 'pay_refund_test',
            'amount' => 249,
            'currency' => 'INR',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post("/admin/payments/{$payment->id}/refund")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('refunded', $payment->fresh()->status->value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.refunded']);
    }

    public function test_admin_can_ban_user_from_qr_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['status' => 'active']);
        $qr = $user->qrCodes()->create([
            'name' => 'Bad QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'is_dynamic' => false,
            'status' => 'active',
        ]);
        $report = QrReport::create([
            'qr_code_id' => $qr->id,
            'reason' => 'Spam',
            'reporter_ip_hash' => 'hash',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post("/admin/qr-reports/{$report->id}/ban-user")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('banned', $user->fresh()->status->value);
        $this->assertSame('actioned', $report->fresh()->status);
    }

    public function test_admin_users_csv_export(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/admin/users/export/csv');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('email', $response->streamedContent());
    }

    public function test_admin_payments_csv_export(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        Payment::create([
            'user_id' => $user->id,
            'gateway' => 'razorpay',
            'gateway_payment_id' => 'pay_csv',
            'amount' => 100,
            'currency' => 'INR',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/payments/export/csv');

        $response->assertOk();
        $this->assertStringContainsString('user_email', $response->streamedContent());
    }

    public function test_user_show_includes_360_tabs_data(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create(['role' => 'user']);
        $plan = Plan::where('slug', 'pro_monthly')->firstOrFail();

        $user->qrCodes()->create([
            'name' => 'Tab QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'is_dynamic' => false,
            'status' => 'active',
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => 'manual',
            'gateway_subscription_id' => 'manual-tab',
            'status' => 'active',
        ]);

        Payment::create([
            'user_id' => $user->id,
            'gateway' => 'razorpay',
            'gateway_payment_id' => 'pay_tab',
            'amount' => 249,
            'currency' => 'INR',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get("/admin/users/{$user->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('qrCodes', 1)
                ->has('subscriptions', 1)
                ->has('payments', 1)
                ->where('canImpersonate', true)
            );
    }
}

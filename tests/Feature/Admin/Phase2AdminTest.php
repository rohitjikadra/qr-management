<?php

namespace Tests\Feature\Admin;

use App\Models\BlockedDomain;
use App\Models\Plan;
use App\Models\QrCode;
use App\Models\QrReport;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    private function makeQr(User $user, array $attributes = []): QrCode
    {
        return $user->qrCodes()->create(array_merge([
            'name' => 'Test QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'is_dynamic' => false,
            'status' => 'active',
        ], $attributes));
    }

    public function test_guest_cannot_access_admin_qr_codes(): void
    {
        $this->get('/admin/qr-codes')->assertRedirect('/login');
    }

    public function test_admin_qr_codes_index_lists_all_qr_codes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $this->makeQr($user, ['name' => 'Listed QR']);

        $this->actingAs($admin)
            ->get('/admin/qr-codes')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/qr-codes/index')
                ->has('qrCodes.data', 1)
                ->where('qrCodes.data.0.name', 'Listed QR')
            );
    }

    public function test_admin_qr_codes_search_filters_by_owner_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['email' => 'findowner@example.com']);
        $other = User::factory()->create(['email' => 'other@example.com']);
        $this->makeQr($owner, ['name' => 'Match']);
        $this->makeQr($other, ['name' => 'No Match']);

        $this->actingAs($admin)
            ->get('/admin/qr-codes?search=findowner')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('qrCodes.total', 1)
                ->where('qrCodes.data.0.name', 'Match')
            );
    }

    public function test_admin_can_view_qr_code_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $qr = $this->makeQr($user, [
            'name' => 'Detail QR',
            'destination_url' => 'https://example.com/page',
        ]);

        $this->actingAs($admin)
            ->get("/admin/qr-codes/{$qr->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/qr-codes/show')
                ->where('qr.name', 'Detail QR')
                ->where('qr.destination_url', 'https://example.com/page')
                ->where('canPause', true)
            );
    }

    public function test_admin_can_pause_qr_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $qr = $this->makeQr($user);

        $this->actingAs($admin)
            ->post("/admin/qr-codes/{$qr->id}/pause")
            ->assertRedirect()
            ->assertSessionHas('success');

        $qr->refresh();
        $this->assertSame('paused', $qr->status->value);
        $this->assertTrue($qr->admin_locked);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'qr.admin_paused',
            'entity_id' => $qr->id,
        ]);
    }

    public function test_qr_reports_default_to_pending_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $qr = $this->makeQr($user);

        QrReport::create([
            'qr_code_id' => $qr->id,
            'reason' => 'Spam link',
            'reporter_ip_hash' => 'hash',
            'status' => 'pending',
        ]);

        QrReport::create([
            'qr_code_id' => $qr->id,
            'reason' => 'Old report',
            'reporter_ip_hash' => 'hash2',
            'status' => 'reviewed',
        ]);

        $this->actingAs($admin)
            ->get('/admin/qr-reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/qr-reports/index')
                ->where('filters.status', 'pending')
                ->where('reports.total', 1)
                ->where('pendingCount', 1)
            );
    }

    public function test_admin_can_dismiss_qr_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $qr = $this->makeQr($user);
        $report = QrReport::create([
            'qr_code_id' => $qr->id,
            'reason' => 'False positive',
            'reporter_ip_hash' => 'hash',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post("/admin/qr-reports/{$report->id}/dismiss")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('reviewed', $report->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'qr_report.dismissed',
            'entity_id' => $report->id,
        ]);
    }

    public function test_admin_can_pause_qr_from_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $qr = $this->makeQr($user);
        $report = QrReport::create([
            'qr_code_id' => $qr->id,
            'reason' => 'Malware',
            'reporter_ip_hash' => 'hash',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post("/admin/qr-reports/{$report->id}/pause-qr")
            ->assertRedirect()
            ->assertSessionHas('success');

        $qr->refresh();
        $report->refresh();
        $this->assertSame('paused', $qr->status->value);
        $this->assertTrue($qr->admin_locked);
        $this->assertSame('actioned', $report->status);
    }

    public function test_admin_blocked_domains_crud(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post('/admin/blocked-domains', [
                'domain' => 'https://Bad.COM/path',
                'reason' => 'Phishing',
            ])
            ->assertRedirect(route('admin.blocked-domains.index'));

        $domain = BlockedDomain::first();
        $this->assertSame('bad.com/path', $domain->domain);
        $this->assertDatabaseHas('audit_logs', ['action' => 'blocked_domain.created']);

        $this->actingAs($admin)
            ->put("/admin/blocked-domains/{$domain->id}", [
                'domain' => 'bad.com',
                'reason' => 'Updated reason',
            ])
            ->assertRedirect(route('admin.blocked-domains.index'));

        $this->assertSame('Updated reason', $domain->fresh()->reason);

        $this->actingAs($admin)
            ->delete("/admin/blocked-domains/{$domain->id}")
            ->assertRedirect(route('admin.blocked-domains.index'));

        $this->assertDatabaseMissing('blocked_domains', ['id' => $domain->id]);
    }

    public function test_admin_plans_index_and_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plan = Plan::where('slug', 'pro_monthly')->firstOrFail();
        $limits = $plan->limits;

        $this->actingAs($admin)
            ->get('/admin/plans')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/plans/index')
                ->has('plans', 3)
            );

        $this->actingAs($admin)
            ->get("/admin/plans/{$plan->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/plans/edit'));

        $this->actingAs($admin)
            ->put("/admin/plans/{$plan->id}", [
                'name' => 'Pro Monthly Updated',
                'price' => 299,
                'razorpay_plan_id' => 'plan_test',
                'limits' => json_encode($limits),
                'is_active' => true,
                'sort_order' => 2,
            ])
            ->assertRedirect(route('admin.plans.index'))
            ->assertSessionHas('success');

        $plan->refresh();
        $this->assertSame('Pro Monthly Updated', $plan->name);
        $this->assertSame('299.00', $plan->price);
        $this->assertDatabaseHas('audit_logs', ['action' => 'plan.updated']);
    }

    public function test_plan_update_rejects_invalid_limits_json(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plan = Plan::where('slug', 'free')->firstOrFail();

        $this->actingAs($admin)
            ->put("/admin/plans/{$plan->id}", [
                'name' => $plan->name,
                'price' => 0,
                'limits' => '{not json',
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->assertSessionHasErrors('limits');
    }
}

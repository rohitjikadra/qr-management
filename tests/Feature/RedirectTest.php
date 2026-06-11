<?php

namespace Tests\Feature;

use App\Jobs\RecordScanJob;
use App\Models\QrCode;
use App\Models\QrScanEvent;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    private function makeDynamicQr(array $attributes = []): QrCode
    {
        return User::factory()->create()->qrCodes()->create(array_merge([
            'name' => 'Campaign',
            'type' => 'url',
            'content' => ['url' => 'https://example.com/landing'],
            'is_dynamic' => true,
            'slug' => 'ABCD2345',
            'destination_url' => 'https://example.com/landing',
            'status' => 'active',
        ], $attributes));
    }

    public function test_active_qr_redirects_to_destination(): void
    {
        Queue::fake();
        $this->makeDynamicQr();

        $response = $this->get('/q/ABCD2345');

        $response->assertRedirect('https://example.com/landing');
        Queue::assertPushed(RecordScanJob::class, fn (RecordScanJob $job) => $job->slug === 'ABCD2345');
    }

    public function test_paused_qr_shows_branded_page(): void
    {
        Queue::fake();
        $this->makeDynamicQr(['status' => 'paused']);

        $response = $this->get('/q/ABCD2345');

        $response->assertOk();
        $response->assertSee('currently paused');
        Queue::assertNothingPushed();
    }

    public function test_unknown_slug_shows_not_found_page(): void
    {
        $response = $this->get('/q/ZZZZ9999');

        $response->assertNotFound();
        $response->assertSee("doesn't exist", false);
    }

    public function test_expired_qr_shows_expired_page(): void
    {
        Queue::fake();
        $this->makeDynamicQr(['expires_at' => now()->subDay()]);

        $response = $this->get('/q/ABCD2345');

        $response->assertOk();
        $response->assertSee('expired');
        Queue::assertNothingPushed();
    }

    public function test_deleted_qr_shows_not_found(): void
    {
        $qr = $this->makeDynamicQr();
        $qr->delete();

        $this->get('/q/ABCD2345')->assertNotFound();
    }

    public function test_redirect_reflects_updated_destination_immediately(): void
    {
        Queue::fake();
        $qr = $this->makeDynamicQr();

        $this->get('/q/ABCD2345')->assertRedirect('https://example.com/landing');

        $qr->update(['destination_url' => 'https://example.com/new']);

        $this->get('/q/ABCD2345')->assertRedirect('https://example.com/new');
    }

    public function test_record_scan_job_creates_event_and_increments_counter(): void
    {
        $qr = $this->makeDynamicQr();

        (new RecordScanJob(
            slug: 'ABCD2345',
            ip: '203.0.113.5',
            userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            referrer: null,
            scannedAt: now(),
        ))->handle(app(\App\Services\GeoLocator::class));

        $event = QrScanEvent::first();
        $this->assertNotNull($event);
        $this->assertSame('mobile', $event->device_type);
        $this->assertNotSame('203.0.113.5', $event->ip_hash);
        $this->assertSame(64, strlen($event->ip_hash));
        $this->assertSame(1, $qr->refresh()->scan_count);
        $this->assertNotNull($qr->last_scanned_at);
    }

    public function test_report_form_creates_report(): void
    {
        $qr = $this->makeDynamicQr();

        $this->post('/q/ABCD2345/report', ['reason' => 'This link goes to a phishing site'])
            ->assertSessionHas('reported');

        $this->assertSame(1, $qr->reports()->count());
    }

    public function test_three_reports_auto_pause_qr(): void
    {
        $qr = $this->makeDynamicQr();

        foreach (range(1, 3) as $i) {
            $qr->reports()->create([
                'reason' => "Report number {$i} about phishing",
                'reporter_ip_hash' => str_repeat((string) $i, 64),
            ]);
        }

        $this->post('/q/ABCD2345/report', ['reason' => 'Yet another phishing report here']);

        $qr->refresh();
        $this->assertSame('paused', $qr->status->value);
        $this->assertTrue($qr->admin_locked);
    }
}

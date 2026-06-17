<?php

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeManagementTest extends TestCase
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

    public function test_user_can_create_static_url_qr(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/qr', [
            'name' => 'My Website',
            'type' => 'url',
            'is_dynamic' => false,
            'content' => ['url' => 'https://example.com'],
        ]);

        $qr = QrCode::first();
        $response->assertRedirect("/qr/{$qr->id}");
        $this->assertNull($qr->slug);
        $this->assertFalse($qr->is_dynamic);
    }

    public function test_user_can_create_dynamic_qr_with_slug(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/qr', [
            'name' => 'Campaign',
            'type' => 'url',
            'is_dynamic' => true,
            'content' => ['url' => 'https://example.com/landing'],
        ]);

        $qr = QrCode::first();
        $this->assertTrue($qr->is_dynamic);
        $this->assertNotNull($qr->slug);
        $this->assertSame(8, strlen($qr->slug));
        $this->assertSame('https://example.com/landing', $qr->destination_url);
    }

    public function test_free_plan_blocks_third_dynamic_qr(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 2) as $i) {
            $this->actingAs($user)->post('/qr', [
                'name' => "Dynamic {$i}",
                'type' => 'url',
                'is_dynamic' => true,
                'content' => ['url' => 'https://example.com'],
            ])->assertSessionHasNoErrors();
        }

        $response = $this->actingAs($user)->post('/qr', [
            'name' => 'Dynamic 3',
            'type' => 'url',
            'is_dynamic' => true,
            'content' => ['url' => 'https://example.com'],
        ]);

        $response->assertSessionHasErrors('is_dynamic');
        $this->assertSame(2, QrCode::where('is_dynamic', true)->count());
    }

    public function test_unverified_user_cannot_create_any_qr(): void
    {
        $user = User::factory()->unverified()->create();

        $static = $this->actingAs($user)->post('/qr', [
            'name' => 'Static QR',
            'type' => 'url',
            'is_dynamic' => false,
            'content' => ['url' => 'https://example.com'],
        ]);

        $static->assertSessionHasErrors('email');
        $this->assertSame(0, QrCode::count());

        $dynamic = $this->actingAs($user)->post('/qr', [
            'name' => 'Dynamic',
            'type' => 'url',
            'is_dynamic' => true,
            'content' => ['url' => 'https://example.com'],
        ]);

        $dynamic->assertSessionHasErrors('email');
        $this->assertSame(0, QrCode::count());
    }

    public function test_unverified_user_sees_verification_notice_on_create_page(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/qr/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('qr/create')
                ->where('limits.email_verified', false)
            );
    }

    public function test_static_only_type_cannot_be_dynamic(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/qr', [
            'name' => 'WiFi QR',
            'type' => 'wifi',
            'is_dynamic' => true,
            'content' => ['ssid' => 'Home', 'security' => 'WPA', 'password' => 'secret123'],
        ]);

        $response->assertSessionHasErrors('is_dynamic');
    }

    public function test_user_cannot_view_others_qr(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $qr = $this->makeQr($owner);

        $this->actingAs($intruder)->get("/qr/{$qr->id}")->assertForbidden();
        $this->actingAs($intruder)->delete("/qr/{$qr->id}")->assertForbidden();
    }

    public function test_dynamic_qr_content_can_be_edited(): void
    {
        $user = User::factory()->create();
        $qr = $this->makeQr($user, [
            'is_dynamic' => true,
            'slug' => 'TESTSLUG',
            'destination_url' => 'https://example.com',
        ]);

        $this->actingAs($user)->put("/qr/{$qr->id}", [
            'name' => 'Updated Name',
            'content' => ['url' => 'https://new-destination.com'],
        ])->assertSessionHasNoErrors();

        $qr->refresh();
        $this->assertSame('Updated Name', $qr->name);
        $this->assertSame('https://new-destination.com', $qr->destination_url);
        $this->assertSame('TESTSLUG', $qr->slug);
    }

    public function test_static_qr_content_is_immutable(): void
    {
        $user = User::factory()->create();
        $qr = $this->makeQr($user);

        $this->actingAs($user)->put("/qr/{$qr->id}", [
            'name' => 'New Name',
            'content' => ['url' => 'https://hacked.com'],
        ]);

        $qr->refresh();
        $this->assertSame('New Name', $qr->name);
        $this->assertSame('https://example.com', $qr->content['url']);
    }

    public function test_toggle_status(): void
    {
        $user = User::factory()->create();
        $qr = $this->makeQr($user);

        $this->actingAs($user)->post("/qr/{$qr->id}/toggle-status");
        $this->assertSame('paused', $qr->refresh()->status->value);

        $this->actingAs($user)->post("/qr/{$qr->id}/toggle-status");
        $this->assertSame('active', $qr->refresh()->status->value);
    }

    public function test_delete_is_soft_delete(): void
    {
        $user = User::factory()->create();
        $qr = $this->makeQr($user);

        $this->actingAs($user)->delete("/qr/{$qr->id}");

        $this->assertSoftDeleted($qr);
    }

    public function test_png_download_works_for_free_user(): void
    {
        $user = User::factory()->create();
        $qr = $this->makeQr($user);

        $response = $this->actingAs($user)->get("/qr/{$qr->id}/download?format=png&size=512");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_svg_download_blocked_for_free_user(): void
    {
        $user = User::factory()->create();
        $qr = $this->makeQr($user);

        $this->actingAs($user)->get("/qr/{$qr->id}/download?format=svg&size=512")->assertForbidden();
    }

    public function test_shortened_urls_are_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/qr', [
            'name' => 'Sneaky',
            'type' => 'url',
            'is_dynamic' => false,
            'content' => ['url' => 'https://bit.ly/abc123'],
        ]);

        $response->assertSessionHasErrors('content.url');
        $this->assertSame(0, QrCode::count());
    }
}

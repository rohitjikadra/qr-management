<?php

namespace Tests\Feature;

use App\Enums\RenewalType;
use App\Enums\SubscriptionStatus;
use App\Jobs\SubscriptionLifecycleJob;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\RazorpayGateway;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
        config(['services.razorpay.webhook_secret' => self::WEBHOOK_SECRET]);
    }

    private function makeSubscription(User $user, string $status = 'pending', array $attributes = []): Subscription
    {
        return Subscription::create(array_merge([
            'user_id' => $user->id,
            'plan_id' => Plan::where('slug', 'pro_monthly')->first()->id,
            'gateway' => 'razorpay',
            'renewal_type' => RenewalType::Manual,
            'gateway_subscription_id' => 'order_test123',
            'status' => $status,
        ], $attributes));
    }

    private function makeAutopaySubscription(User $user, string $status = 'pending', array $attributes = []): Subscription
    {
        return $this->makeSubscription($user, $status, array_merge([
            'renewal_type' => RenewalType::Autopay,
            'gateway_subscription_id' => 'sub_test123',
        ], $attributes));
    }

    private function postWebhook(array $payload, ?string $eventId = null): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);

        return $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => hash_hmac('sha256', $body, self::WEBHOOK_SECRET),
            'HTTP_x-razorpay-event-id' => $eventId ?? 'evt_'.uniqid(),
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $response = $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => 'forged-signature',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['event' => 'subscription.activated']));

        $response->assertStatus(400);
    }

    public function test_webhook_is_idempotent_per_event_id(): void
    {
        $user = User::factory()->create();
        $this->makeAutopaySubscription($user);

        $payload = [
            'event' => 'subscription.activated',
            'payload' => ['subscription' => ['entity' => ['id' => 'sub_test123']]],
        ];

        $this->postWebhook($payload, 'evt_same')->assertOk();
        $this->postWebhook($payload, 'evt_same')->assertJson(['status' => 'duplicate']);
    }

    public function test_subscription_activated_webhook_activates_subscription(): void
    {
        $user = User::factory()->create();
        $subscription = $this->makeAutopaySubscription($user);

        $this->postWebhook([
            'event' => 'subscription.activated',
            'payload' => ['subscription' => ['entity' => ['id' => 'sub_test123']]],
        ])->assertOk();

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertNotNull($subscription->expires_at);
        $this->assertTrue($subscription->expires_at->isFuture());
    }

    public function test_subscription_charged_records_payment_with_invoice(): void
    {
        $user = User::factory()->create();
        $subscription = $this->makeAutopaySubscription($user, 'active', ['expires_at' => now()->addDay()]);

        $this->postWebhook([
            'event' => 'subscription.charged',
            'payload' => [
                'subscription' => ['entity' => ['id' => 'sub_test123']],
                'payment' => ['entity' => ['id' => 'pay_abc1', 'amount' => 24900, 'currency' => 'INR']],
            ],
        ])->assertOk();

        $payment = Payment::where('gateway_payment_id', 'pay_abc1')->first();
        $this->assertNotNull($payment);
        $this->assertSame('paid', $payment->status->value);
        $this->assertSame('249.00', (string) $payment->amount);
        $this->assertMatchesRegularExpression('/^INV-\d{6}-\d{4}$/', $payment->invoice_number);

        // Expiry extended roughly one month beyond the previous expiry.
        $this->assertTrue($subscription->refresh()->expires_at->greaterThan(now()->addDays(25)));
    }

    public function test_duplicate_charge_does_not_create_two_payments(): void
    {
        $user = User::factory()->create();
        $this->makeAutopaySubscription($user, 'active', ['expires_at' => now()->addDay()]);

        $payload = [
            'event' => 'subscription.charged',
            'payload' => [
                'subscription' => ['entity' => ['id' => 'sub_test123']],
                'payment' => ['entity' => ['id' => 'pay_same', 'amount' => 24900, 'currency' => 'INR']],
            ],
        ];

        $this->postWebhook($payload, 'evt_1');
        $this->postWebhook($payload, 'evt_2');

        $this->assertSame(1, Payment::where('gateway_payment_id', 'pay_same')->count());
    }

    public function test_payment_failed_webhook_records_failed_payment(): void
    {
        $user = User::factory()->create();
        $this->makeAutopaySubscription($user, 'active');

        $this->postWebhook([
            'event' => 'payment.failed',
            'payload' => [
                'payment' => ['entity' => ['id' => 'pay_fail', 'subscription_id' => 'sub_test123', 'amount' => 24900]],
            ],
        ])->assertOk();

        $this->assertSame('failed', Payment::where('gateway_payment_id', 'pay_fail')->first()->status->value);
    }

    public function test_lifecycle_moves_expired_subscription_to_grace(): void
    {
        $user = User::factory()->create();
        $subscription = $this->makeSubscription($user, 'active', ['expires_at' => now()->subDay()]);

        (new SubscriptionLifecycleJob)->handle(app(\App\Services\SubscriptionService::class));

        $this->assertSame(SubscriptionStatus::Grace, $subscription->refresh()->status);
    }

    public function test_lifecycle_freezes_after_grace_and_freezes_excess_qrs(): void
    {
        $user = User::factory()->create();
        $subscription = $this->makeSubscription($user, 'grace', ['expires_at' => now()->subDays(10)]);

        foreach (range(1, 4) as $i) {
            $user->qrCodes()->create([
                'name' => "Dynamic {$i}",
                'type' => 'url',
                'content' => ['url' => 'https://example.com'],
                'is_dynamic' => true,
                'slug' => "SLUG000{$i}",
                'destination_url' => 'https://example.com',
                'status' => 'active',
                'created_at' => now()->subDays(10 - $i),
            ]);
        }

        (new SubscriptionLifecycleJob)->handle(app(\App\Services\SubscriptionService::class));

        $this->assertSame(SubscriptionStatus::Frozen, $subscription->refresh()->status);

        // Oldest 2 stay editable (free limit), newest 2 frozen.
        $frozen = $user->qrCodes()->where('frozen', true)->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['Dynamic 3', 'Dynamic 4'], $frozen);

        // Frozen QRs still redirect.
        $this->get('/q/SLUG0003')->assertRedirect('https://example.com');
    }

    public function test_successful_charge_unfreezes_qrs(): void
    {
        $user = User::factory()->create();
        $subscription = $this->makeSubscription($user, 'frozen', ['expires_at' => now()->subDays(10)]);

        $qr = $user->qrCodes()->create([
            'name' => 'Frozen QR',
            'type' => 'url',
            'content' => ['url' => 'https://example.com'],
            'is_dynamic' => true,
            'slug' => 'FROZEN01',
            'destination_url' => 'https://example.com',
            'status' => 'active',
        ]);
        $qr->forceFill(['frozen' => true])->save();

        $this->postWebhook([
            'event' => 'payment.captured',
            'payload' => [
                'payment' => ['entity' => [
                    'id' => 'pay_renew',
                    'order_id' => 'order_test123',
                    'amount' => 24900,
                    'currency' => 'INR',
                    'notes' => ['subscription_id' => (string) $subscription->id],
                ]],
            ],
        ]);

        $this->assertFalse($qr->refresh()->frozen);
    }

    public function test_payment_captured_activates_pending_manual_subscription(): void
    {
        $user = User::factory()->create();
        $subscription = $this->makeSubscription($user, 'pending');

        $this->postWebhook([
            'event' => 'payment.captured',
            'payload' => [
                'payment' => ['entity' => [
                    'id' => 'pay_manual1',
                    'order_id' => 'order_test123',
                    'amount' => 24900,
                    'currency' => 'INR',
                ]],
            ],
        ])->assertOk();

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertNotNull($subscription->expires_at);
        $this->assertNotNull(Payment::where('gateway_payment_id', 'pay_manual1')->first());
    }

    public function test_subscribe_creates_pending_subscription_and_returns_order_checkout(): void
    {
        $user = User::factory()->create();

        $this->mock(RazorpayGateway::class, function (MockInterface $mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('createOrder')->once()->andReturn('order_new456');
        });

        $response = $this->actingAs($user)->post('/billing/subscribe', ['plan' => 'pro_monthly']);

        $response->assertSessionHas('checkout', fn ($checkout) => $checkout['checkout_type'] === 'order'
            && $checkout['order_id'] === 'order_new456');

        $subscription = Subscription::where('gateway_subscription_id', 'order_new456')->first();
        $this->assertNotNull($subscription);
        $this->assertSame(SubscriptionStatus::Pending, $subscription->status);
        $this->assertSame(RenewalType::Manual, $subscription->renewal_type);
    }

    public function test_cancel_marks_subscription_cancelled_for_autopay_only(): void
    {
        $user = User::factory()->create();
        $subscription = $this->makeAutopaySubscription($user, 'active', ['expires_at' => now()->addMonth()]);

        $this->mock(RazorpayGateway::class, function (MockInterface $mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('cancelSubscription')->once();
        });

        $this->actingAs($user)->post('/billing/cancel');

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::Cancelled, $subscription->status);
        $this->assertNotNull($subscription->cancelled_at);
    }

    public function test_cancel_rejected_for_manual_renewal(): void
    {
        $user = User::factory()->create();
        $this->makeSubscription($user, 'active', ['expires_at' => now()->addMonth()]);

        $this->actingAs($user)->post('/billing/cancel')->assertStatus(422);
    }

    public function test_pricing_page_renders_for_guests(): void
    {
        $this->get('/pricing')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('pricing')
                ->has('plans', 3)
                ->where('currentPlan', null)
                ->where('billing_discount_percent', null)
            );
    }

    public function test_pricing_page_shows_discount_for_user_with_billing_discount(): void
    {
        $user = User::factory()->create(['billing_discount_percent' => 50]);
        $proMonthly = Plan::where('slug', 'pro_monthly')->firstOrFail();

        $this->actingAs($user)
            ->get('/pricing')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('pricing')
                ->where('billing_discount_percent', 50)
                ->where('plans', function ($plans) use ($proMonthly, $user): bool {
                    $pro = collect($plans)->firstWhere('slug', 'pro_monthly');

                    return $pro['has_discount'] === true
                        && $pro['discounted_price'] === $user->discountedPriceFor($proMonthly)
                        && $pro['discounted_price'] < $pro['price'];
                })
            );
    }

    public function test_home_page_shows_discount_for_user_with_billing_discount(): void
    {
        $user = User::factory()->create(['billing_discount_percent' => 50]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('welcome')
                ->where('billing_discount_percent', 50)
                ->where('plans', function ($plans): bool {
                    $pro = collect($plans)->firstWhere('slug', 'pro_monthly');

                    return $pro['has_discount'] === true && $pro['discounted_price'] < $pro['price'];
                })
            );
    }

    public function test_billing_page_renders(): void
    {
        $this->actingAs(User::factory()->create())->get('/billing')->assertOk();
    }
}

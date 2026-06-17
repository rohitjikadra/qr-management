<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Razorpay\Api\Api;
use RuntimeException;

/**
 * Thin wrapper around the Razorpay SDK so the rest of the app
 * stays gateway-agnostic and tests can fake this class.
 */
class RazorpayGateway
{
    private ?Api $api = null;

    public function isConfigured(): bool
    {
        return (bool) (config('services.razorpay.key') && config('services.razorpay.secret'));
    }

    /**
     * Create a plan on Razorpay and return its id (plan_XXXX).
     */
    public function createPlan(Plan $plan): string
    {
        $razorpayPlan = $this->api()->plan->create([
            'period' => $plan->billing_cycle->value === 'yearly' ? 'yearly' : 'monthly',
            'interval' => 1,
            'item' => [
                'name' => config('app.name').' '.$plan->name,
                'amount' => (int) round($plan->price * 100),
                'currency' => $plan->currency,
            ],
        ]);

        return $razorpayPlan->id;
    }

    /**
     * Create a one-time Razorpay order for manual renewal checkout.
     */
    public function createOrder(Plan $plan, User $user, int $amountPaise, Subscription $subscription): string
    {
        $order = $this->api()->order->create([
            'receipt' => 'sub_'.$subscription->id.'_'.now()->timestamp,
            'amount' => $amountPaise,
            'currency' => $plan->currency,
            'notes' => [
                'user_id' => (string) $user->id,
                'plan_slug' => $plan->slug,
                'subscription_id' => (string) $subscription->id,
            ],
        ]);

        return $order->id;
    }

    /**
     * Create a Razorpay subscription and return its id (autopay mode).
     */
    public function createSubscription(Plan $plan, User $user): string
    {
        if (! $plan->razorpay_plan_id) {
            throw new RuntimeException("Plan [{$plan->slug}] has no razorpay_plan_id configured.");
        }

        $subscription = $this->api()->subscription->create([
            'plan_id' => $plan->razorpay_plan_id,
            'total_count' => $plan->billing_cycle->value === 'yearly' ? 10 : 120,
            'customer_notify' => 1,
            'notes' => [
                'user_id' => (string) $user->id,
                'plan_slug' => $plan->slug,
            ],
        ]);

        return $subscription->id;
    }

    public function cancelSubscription(string $gatewaySubscriptionId): void
    {
        // Cancel at cycle end so the user keeps access until expiry.
        $this->api()->subscription
            ->fetch($gatewaySubscriptionId)
            ->cancel(['cancel_at_cycle_end' => 1]);
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('services.razorpay.webhook_secret');

        if (! $secret) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }

    private function api(): Api
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Razorpay is not configured. Set RAZORPAY_KEY and RAZORPAY_SECRET.');
        }

        return $this->api ??= new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    }
}

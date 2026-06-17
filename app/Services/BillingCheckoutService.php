<?php

namespace App\Services;

use App\Enums\RenewalType;
use App\Enums\SubscriptionStatus;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Support\BillingSettings;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BillingCheckoutService
{
    public function __construct(
        private readonly RazorpayGateway $gateway,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function startCheckout(User $user, Plan $plan): array
    {
        if (! BillingSettings::paymentsEnabled()) {
            throw ValidationException::withMessages([
                'plan' => BillingSettings::disabledMessage(),
            ]);
        }

        if (! $this->gateway->isConfigured()) {
            throw ValidationException::withMessages([
                'plan' => 'Payments are not configured yet. Please try again later.',
            ]);
        }

        $active = $user->activeSubscription;

        if ($active && $active->status->grantsProAccess() && $active->gateway === 'manual') {
            throw ValidationException::withMessages([
                'plan' => 'You already have complimentary Pro access until '.$active->expires_at?->toFormattedDateString().'.',
            ]);
        }

        return BillingSettings::isAutopay()
            ? $this->startAutopayCheckout($user, $plan)
            : $this->startManualCheckout($user, $plan);
    }

    /**
     * @return array<string, mixed>
     */
    private function startManualCheckout(User $user, Plan $plan): array
    {
        $subscription = $this->resolveSubscriptionForManualCheckout($user, $plan);
        $amountPaise = (int) round($user->discountedPriceFor($plan) * 100);

        if ($amountPaise < 100) {
            throw new RuntimeException('Order amount must be at least ₹1.');
        }

        $orderId = $this->gateway->createOrder($plan, $user, $amountPaise, $subscription);

        $subscription->update([
            'gateway_subscription_id' => $orderId,
            'renewal_type' => RenewalType::Manual,
        ]);

        AuditLog::record('subscription.checkout_started', meta: [
            'plan' => $plan->slug,
            'renewal_type' => RenewalType::Manual->value,
            'order_id' => $orderId,
        ]);

        return [
            'checkout_type' => 'order',
            'order_id' => $orderId,
            'amount' => $amountPaise,
            'currency' => $plan->currency,
            'razorpay_key' => config('services.razorpay.key'),
            'name' => config('app.name'),
            'prefill' => ['name' => $user->name, 'email' => $user->email],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function startAutopayCheckout(User $user, Plan $plan): array
    {
        $gatewaySubscriptionId = $this->gateway->createSubscription($plan, $user);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => 'razorpay',
            'renewal_type' => RenewalType::Autopay,
            'gateway_subscription_id' => $gatewaySubscriptionId,
            'status' => SubscriptionStatus::Pending,
        ]);

        AuditLog::record('subscription.checkout_started', meta: [
            'plan' => $plan->slug,
            'renewal_type' => RenewalType::Autopay->value,
        ]);

        return [
            'checkout_type' => 'subscription',
            'gateway_subscription_id' => $gatewaySubscriptionId,
            'razorpay_key' => config('services.razorpay.key'),
            'name' => config('app.name'),
            'prefill' => ['name' => $user->name, 'email' => $user->email],
        ];
    }

    private function resolveSubscriptionForManualCheckout(User $user, Plan $plan): Subscription
    {
        $user->subscriptions()
            ->where('status', SubscriptionStatus::Pending)
            ->where('renewal_type', RenewalType::Manual)
            ->where('created_at', '<', now()->subDay())
            ->update(['status' => SubscriptionStatus::Expired]);

        $existing = $user->subscriptions()
            ->where('plan_id', $plan->id)
            ->where('gateway', 'razorpay')
            ->where('renewal_type', RenewalType::Manual)
            ->whereIn('status', [
                SubscriptionStatus::Pending,
                SubscriptionStatus::Active,
                SubscriptionStatus::Grace,
                SubscriptionStatus::Frozen,
                SubscriptionStatus::Cancelled,
                SubscriptionStatus::Expired,
            ])
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => 'razorpay',
            'renewal_type' => RenewalType::Manual,
            'status' => SubscriptionStatus::Pending,
        ]);
    }
}

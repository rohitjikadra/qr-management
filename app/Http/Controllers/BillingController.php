<?php

namespace App\Http\Controllers;

use App\Enums\RenewalType;
use App\Enums\SubscriptionStatus;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Services\BillingCheckoutService;
use App\Services\PlanLimitService;
use App\Services\RazorpayGateway;
use App\Services\SubscriptionService;
use App\Support\BillingSettings;
use App\Support\PlanPricing;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function pricing(Request $request, PlanLimitService $planLimits)
    {
        $user = $request->user()?->fresh();
        $plans = PlanPricing::cards($user);

        return Inertia::render('pricing', [
            'plans' => $plans,
            'currentPlan' => $user ? $planLimits->planFor($user)->slug : null,
            'billing_discount_percent' => $user?->billing_discount_percent,
            ...BillingSettings::forFrontend(),
        ]);
    }

    public function index(Request $request, PlanLimitService $planLimits)
    {
        $user = $request->user()->fresh();
        $subscription = $user->subscriptions()->with('plan')->latest()->first();
        $effectivePlan = $planLimits->planFor($user);

        $payments = $user->payments()->latest()->limit(20)->get()->map(fn ($p) => [
            'id' => $p->id,
            'invoice_number' => $p->invoice_number,
            'amount' => (float) $p->amount,
            'currency' => $p->currency,
            'status' => $p->status->value,
            'paid_at' => $p->paid_at?->toDayDateTimeString(),
        ]);

        $canRenew = $this->canRenew($user, $subscription, $effectivePlan->isFree());

        return Inertia::render('billing', [
            'plan' => [
                'name' => $effectivePlan->name,
                'slug' => $effectivePlan->slug,
                'is_free' => $effectivePlan->isFree(),
            ],
            'billing_discount_percent' => $user->billing_discount_percent,
            'billing_note' => $user->billing_note,
            'subscription' => $subscription ? [
                'status' => $subscription->status->value,
                'plan_name' => $subscription->plan->name,
                'plan_slug' => $subscription->plan->slug,
                'gateway' => $subscription->gateway,
                'renewal_type' => $subscription->renewal_type?->value ?? RenewalType::Manual->value,
                'is_complimentary' => $subscription->is_complimentary,
                'starts_at' => $subscription->starts_at?->toFormattedDateString(),
                'expires_at' => $subscription->expires_at?->toFormattedDateString(),
                'cancelled_at' => $subscription->cancelled_at?->toFormattedDateString(),
            ] : null,
            'payments' => $payments,
            'razorpayConfigured' => app(RazorpayGateway::class)->isConfigured(),
            'can_renew' => $canRenew,
            ...BillingSettings::forFrontend(),
        ]);
    }

    public function subscribe(Request $request, BillingCheckoutService $checkout)
    {
        $request->validate([
            'plan' => ['required', Rule::exists('plans', 'slug')->where('is_active', true)],
        ]);

        $plan = Plan::where('slug', $request->input('plan'))->firstOrFail();

        abort_if($plan->isFree(), 422, 'Cannot subscribe to the free plan.');

        $checkoutData = $checkout->startCheckout($request->user(), $plan);

        return back()->with('checkout', $checkoutData);
    }

    public function cancel(Request $request, RazorpayGateway $gateway, SubscriptionService $subscriptions)
    {
        $subscription = $request->user()->subscriptions()
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Grace])
            ->latest()
            ->firstOrFail();

        abort_if(
            $subscription->renewal_type !== RenewalType::Autopay,
            422,
            'Manual renewal plans do not use autopay cancellation. Your access ends on the expiry date.'
        );

        if ($subscription->gateway_subscription_id && $gateway->isConfigured()) {
            $gateway->cancelSubscription($subscription->gateway_subscription_id);
        }

        $subscriptions->markCancelled($subscription);

        AuditLog::record('subscription.cancelled', $subscription);

        return back()->with('success', 'Subscription cancelled. You keep Pro access until the end of the billing period.');
    }

    private function canRenew($user, $subscription, bool $onFreePlan): bool
    {
        if (! BillingSettings::paymentsEnabled() || ! app(RazorpayGateway::class)->isConfigured()) {
            return false;
        }

        if ($onFreePlan) {
            return true;
        }

        if (! $subscription || $subscription->gateway === 'manual') {
            return false;
        }

        if ($subscription->renewal_type === RenewalType::Autopay && $subscription->status->grantsProAccess()) {
            return false;
        }

        return in_array($subscription->status, [
            SubscriptionStatus::Active,
            SubscriptionStatus::Grace,
            SubscriptionStatus::Frozen,
            SubscriptionStatus::Cancelled,
            SubscriptionStatus::Expired,
        ], true);
    }
}

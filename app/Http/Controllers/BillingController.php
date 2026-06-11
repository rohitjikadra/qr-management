<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PlanLimitService;
use App\Services\RazorpayGateway;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function pricing(Request $request, PlanLimitService $planLimits)
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get()->map(fn (Plan $plan) => [
            'slug' => $plan->slug,
            'name' => $plan->name,
            'price' => (float) $plan->price,
            'currency' => $plan->currency,
            'billing_cycle' => $plan->billing_cycle->value,
            'limits' => $plan->limits,
        ]);

        return Inertia::render('pricing', [
            'plans' => $plans,
            'currentPlan' => $request->user() ? $planLimits->planFor($request->user())->slug : null,
        ]);
    }

    public function index(Request $request, PlanLimitService $planLimits)
    {
        $user = $request->user();
        $subscription = $user->subscriptions()->with('plan')->latest()->first();

        $payments = $user->payments()->latest()->limit(20)->get()->map(fn ($p) => [
            'id' => $p->id,
            'invoice_number' => $p->invoice_number,
            'amount' => (float) $p->amount,
            'currency' => $p->currency,
            'status' => $p->status->value,
            'paid_at' => $p->paid_at?->toDayDateTimeString(),
        ]);

        return Inertia::render('billing', [
            'plan' => [
                'name' => $planLimits->planFor($user)->name,
                'slug' => $planLimits->planFor($user)->slug,
                'is_free' => $planLimits->planFor($user)->isFree(),
            ],
            'subscription' => $subscription ? [
                'status' => $subscription->status->value,
                'plan_name' => $subscription->plan->name,
                'starts_at' => $subscription->starts_at?->toFormattedDateString(),
                'expires_at' => $subscription->expires_at?->toFormattedDateString(),
                'cancelled_at' => $subscription->cancelled_at?->toFormattedDateString(),
            ] : null,
            'payments' => $payments,
            'razorpayConfigured' => app(RazorpayGateway::class)->isConfigured(),
        ]);
    }

    public function subscribe(Request $request, RazorpayGateway $gateway)
    {
        $request->validate([
            'plan' => ['required', Rule::exists('plans', 'slug')->where('is_active', true)],
        ]);

        $user = $request->user();
        $plan = Plan::where('slug', $request->input('plan'))->firstOrFail();

        abort_if($plan->isFree(), 422, 'Cannot subscribe to the free plan.');

        if (! $gateway->isConfigured()) {
            return back()->withErrors(['plan' => 'Payments are not configured yet. Please try again later.']);
        }

        $gatewaySubscriptionId = $gateway->createSubscription($plan, $user);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => 'razorpay',
            'gateway_subscription_id' => $gatewaySubscriptionId,
            'status' => SubscriptionStatus::Pending,
        ]);

        AuditLog::record('subscription.checkout_started', meta: ['plan' => $plan->slug]);

        return back()->with('checkout', [
            'gateway_subscription_id' => $gatewaySubscriptionId,
            'razorpay_key' => config('services.razorpay.key'),
            'name' => config('app.name'),
            'prefill' => ['name' => $user->name, 'email' => $user->email],
        ]);
    }

    public function cancel(Request $request, RazorpayGateway $gateway, SubscriptionService $subscriptions)
    {
        $subscription = $request->user()->subscriptions()
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Grace])
            ->latest()
            ->firstOrFail();

        if ($subscription->gateway_subscription_id && $gateway->isConfigured()) {
            $gateway->cancelSubscription($subscription->gateway_subscription_id);
        }

        $subscriptions->markCancelled($subscription);

        AuditLog::record('subscription.cancelled', $subscription);

        return back()->with('success', 'Subscription cancelled. You keep Pro access until the end of the billing period.');
    }
}

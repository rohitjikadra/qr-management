<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\BillingActions;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExtendSubscriptionRequest;
use App\Http\Requests\Admin\RevokeSubscriptionRequest;
use App\Models\AuditLog;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Subscription::query()
            ->with(['user:id,name,email', 'plan:id,name,slug'])
            ->latest('id');

        if ($search = trim($request->string('search')->value())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->whereLike('gateway_subscription_id', "%{$search}%", caseSensitive: false)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->whereLike('email', "%{$search}%", caseSensitive: false)
                        ->orWhereLike('name', "%{$search}%", caseSensitive: false))
                    ->orWhereHas('plan', fn ($planQuery) => $planQuery
                        ->whereLike('name', "%{$search}%", caseSensitive: false));
            });
        }

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        if ($gateway = $request->string('gateway')->value()) {
            $query->where('gateway', $gateway);
        }

        $subscriptions = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Subscription $subscription): array => [
                'id' => $subscription->id,
                'user' => $subscription->user ? [
                    'id' => $subscription->user->id,
                    'name' => $subscription->user->name,
                    'email' => $subscription->user->email,
                ] : null,
                'plan_name' => $subscription->plan?->name,
                'status' => $subscription->status->value,
                'gateway' => $subscription->gateway,
                'is_complimentary' => $subscription->is_complimentary,
                'starts_at' => $subscription->starts_at?->toDateTimeString(),
                'expires_at' => $subscription->expires_at?->toDateTimeString(),
                'created_at' => $subscription->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('admin/subscriptions/index', [
            'subscriptions' => $subscriptions,
            'filters' => $request->only(['search', 'status', 'gateway']),
            'totalSubscriptions' => Subscription::count(),
            'statusOptions' => collect(SubscriptionStatus::cases())->map(fn (SubscriptionStatus $status) => [
                'value' => $status->value,
                'label' => str($status->value)->headline()->toString(),
            ])->values(),
        ]);
    }

    public function show(Subscription $subscription): Response
    {
        $subscription->load(['user:id,name,email', 'plan', 'grantedBy:id,name,email', 'payments' => fn ($q) => $q->latest()->limit(10)]);

        return Inertia::render('admin/subscriptions/show', [
            'subscription' => [
                'id' => $subscription->id,
                'status' => $subscription->status->value,
                'gateway' => $subscription->gateway,
                'gateway_subscription_id' => $subscription->gateway_subscription_id,
                'is_complimentary' => $subscription->is_complimentary,
                'admin_note' => $subscription->admin_note,
                'starts_at' => $subscription->starts_at?->toDateTimeString(),
                'expires_at' => $subscription->expires_at?->toDateTimeString(),
                'cancelled_at' => $subscription->cancelled_at?->toDateTimeString(),
                'created_at' => $subscription->created_at?->toDateTimeString(),
                'user' => $subscription->user ? [
                    'id' => $subscription->user->id,
                    'name' => $subscription->user->name,
                    'email' => $subscription->user->email,
                ] : null,
                'plan' => $subscription->plan ? [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'slug' => $subscription->plan->slug,
                ] : null,
                'granted_by' => $subscription->grantedBy ? [
                    'id' => $subscription->grantedBy->id,
                    'name' => $subscription->grantedBy->name,
                    'email' => $subscription->grantedBy->email,
                ] : null,
            ],
            'payments' => $subscription->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status->value,
                'paid_at' => $payment->paid_at?->toDateTimeString(),
            ])->values(),
            'canExtend' => $subscription->gateway === 'manual',
            'canRevoke' => $subscription->gateway === 'manual' && $subscription->status->grantsProAccess(),
        ]);
    }

    public function extend(
        ExtendSubscriptionRequest $request,
        Subscription $subscription,
        BillingActions $billing,
    ): RedirectResponse {
        abort_if($subscription->gateway !== 'manual', 403);

        $validated = $request->validated();
        $billing->extendManual(
            $subscription,
            (int) $validated['days'],
            $validated['admin_note'],
            $request->user(),
        );

        return back()->with(
            'success',
            'Subscription extended until '.$subscription->fresh()->expires_at?->toFormattedDateString().'.'
        );
    }

    public function revoke(
        RevokeSubscriptionRequest $request,
        Subscription $subscription,
        BillingActions $billing,
    ): RedirectResponse {
        abort_if($subscription->gateway !== 'manual', 403);
        abort_if(! $subscription->status->grantsProAccess(), 403);

        $billing->revokeManual($subscription, $request->validated('admin_note'), $request->user());

        return back()->with('success', 'Manual subscription access revoked.');
    }
}

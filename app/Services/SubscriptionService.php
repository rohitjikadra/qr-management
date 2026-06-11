<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;

class SubscriptionService
{
    public const GRACE_DAYS = 7;

    /**
     * Called when the gateway confirms activation (webhook only).
     */
    public function activate(Subscription $subscription): void
    {
        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'starts_at' => $subscription->starts_at ?? now(),
            'expires_at' => $this->nextExpiry($subscription->plan, now()),
        ]);

        $this->unfreezeQrs($subscription->user);
    }

    /**
     * Called on every successful charge — records the payment
     * and extends the subscription.
     */
    public function recordCharge(Subscription $subscription, array $paymentData): Payment
    {
        $payment = Payment::firstOrCreate(
            ['gateway_payment_id' => $paymentData['id']],
            [
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'gateway' => 'razorpay',
                'gateway_order_id' => $paymentData['order_id'] ?? null,
                'invoice_number' => $this->nextInvoiceNumber(),
                'amount' => ($paymentData['amount'] ?? 0) / 100,
                'currency' => $paymentData['currency'] ?? 'INR',
                'status' => 'paid',
                'meta' => $paymentData,
                'paid_at' => now(),
            ]
        );

        $base = $subscription->expires_at?->isFuture() ? $subscription->expires_at : now();

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'expires_at' => $this->nextExpiry($subscription->plan, $base),
        ]);

        $this->unfreezeQrs($subscription->user);

        return $payment;
    }

    public function markCancelled(Subscription $subscription): void
    {
        // Access continues until expires_at; lifecycle job handles the rest.
        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function markGrace(Subscription $subscription): void
    {
        $subscription->update(['status' => SubscriptionStatus::Grace]);
    }

    /**
     * Grace period over: lock pro features but NEVER break redirects.
     */
    public function freeze(Subscription $subscription): void
    {
        $subscription->update(['status' => SubscriptionStatus::Frozen]);

        $this->freezeExcessQrs($subscription->user);
    }

    /**
     * Freeze dynamic QRs beyond the free plan limit (newest first
     * are frozen, oldest stay editable). Redirects keep working.
     */
    public function freezeExcessQrs(User $user): void
    {
        $freeLimit = (int) (Plan::where('slug', 'free')->first()?->limit('dynamic_qr', 2) ?? 2);

        $keepIds = $user->qrCodes()
            ->where('is_dynamic', true)
            ->oldest()
            ->limit(max(0, $freeLimit))
            ->pluck('id');

        $user->qrCodes()
            ->where('is_dynamic', true)
            ->whereNotIn('id', $keepIds)
            ->get()
            ->each(fn ($qr) => $qr->forceFill(['frozen' => true])->save());
    }

    public function unfreezeQrs(User $user): void
    {
        $user->qrCodes()
            ->where('frozen', true)
            ->get()
            ->each(fn ($qr) => $qr->forceFill(['frozen' => false])->save());
    }

    private function nextExpiry(Plan $plan, Carbon|\Carbon\CarbonInterface $from): Carbon
    {
        $from = Carbon::parse($from);

        return $plan->billing_cycle->value === 'yearly'
            ? $from->copy()->addYear()
            : $from->copy()->addMonth();
    }

    private function nextInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ym').'-';
        $sequence = Payment::where('invoice_number', 'like', $prefix.'%')->count() + 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}

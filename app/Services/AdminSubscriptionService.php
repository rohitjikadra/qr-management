<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AdminSubscriptionService
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * Grant complimentary Pro access (testing, partners, goodwill) without Razorpay.
     */
    public function grantComplimentary(
        User $user,
        Plan $plan,
        Carbon $expiresAt,
        string $adminNote,
        User $grantedBy,
    ): Subscription {
        if ($plan->isFree()) {
            throw new InvalidArgumentException('Cannot grant complimentary access for the free plan.');
        }

        if ($expiresAt->isPast()) {
            throw new InvalidArgumentException('Expiry must be in the future.');
        }

        $this->endActiveManualSubscriptions($user);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => 'manual',
            'gateway_subscription_id' => 'manual-'.Str::uuid(),
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'expires_at' => $expiresAt,
            'is_complimentary' => true,
            'admin_note' => $adminNote,
            'granted_by' => $grantedBy->id,
        ]);

        $this->subscriptions->unfreezeQrs($user);

        AuditLog::record(
            'subscription.complimentary_granted',
            $subscription,
            [
                'target_user_id' => $user->id,
                'plan' => $plan->slug,
                'expires_at' => $expiresAt->toIso8601String(),
                'note' => $adminNote,
            ],
            $grantedBy->id,
        );

        return $subscription;
    }

    public function extendManual(Subscription $subscription, int $days, string $adminNote, User $admin): void
    {
        if ($subscription->gateway !== 'manual') {
            throw new InvalidArgumentException('Only manual subscriptions can be extended from admin.');
        }

        if ($days < 1) {
            throw new InvalidArgumentException('Extension must be at least one day.');
        }

        $base = $subscription->expires_at?->isFuture()
            ? $subscription->expires_at->copy()
            : now();

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'expires_at' => $base->addDays($days),
            'admin_note' => $this->appendNote($subscription->admin_note, "Extended {$days}d: {$adminNote}"),
        ]);

        $this->subscriptions->unfreezeQrs($subscription->user);

        AuditLog::record(
            'subscription.manual_extended',
            $subscription,
            ['days' => $days, 'note' => $adminNote],
            $admin->id,
        );
    }

    public function revokeManual(Subscription $subscription, string $adminNote, User $admin): void
    {
        if ($subscription->gateway !== 'manual') {
            throw new InvalidArgumentException('Only manual subscriptions can be revoked from admin.');
        }

        $subscription->update([
            'status' => SubscriptionStatus::Expired,
            'cancelled_at' => now(),
            'expires_at' => now(),
            'admin_note' => $this->appendNote($subscription->admin_note, "Revoked: {$adminNote}"),
        ]);

        $this->subscriptions->freezeExcessQrs($subscription->user);

        AuditLog::record(
            'subscription.manual_revoked',
            $subscription,
            ['note' => $adminNote],
            $admin->id,
        );
    }

    public function setBillingDiscount(User $user, ?int $percent, ?string $note, User $admin): void
    {
        if ($percent !== null && ($percent < 0 || $percent > 100)) {
            throw new InvalidArgumentException('Discount must be between 0 and 100.');
        }

        $user->update([
            'billing_discount_percent' => $percent,
            'billing_note' => $note,
        ]);

        AuditLog::record(
            'user.billing_discount_set',
            $user,
            ['percent' => $percent, 'note' => $note],
            $admin->id,
        );
    }

    private function endActiveManualSubscriptions(User $user): void
    {
        $user->subscriptions()
            ->where('gateway', 'manual')
            ->whereIn('status', [
                SubscriptionStatus::Active,
                SubscriptionStatus::Grace,
                SubscriptionStatus::Cancelled,
            ])
            ->each(function (Subscription $subscription): void {
                $subscription->update([
                    'status' => SubscriptionStatus::Expired,
                    'cancelled_at' => now(),
                ]);
            });
    }

    private function appendNote(?string $existing, string $line): string
    {
        $prefix = $existing ? rtrim($existing)."\n" : '';

        return $prefix.now()->toDateString().': '.$line;
    }
}

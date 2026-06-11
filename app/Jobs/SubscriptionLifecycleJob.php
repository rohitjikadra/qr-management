<?php

namespace App\Jobs;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Daily transitions: expired active/cancelled subscriptions enter a
 * 7-day grace period; once grace ends they are frozen. Redirects are
 * never broken — freezing only locks management features.
 */
class SubscriptionLifecycleJob implements ShouldQueue
{
    use Queueable;

    public function handle(SubscriptionService $subscriptions): void
    {
        Subscription::query()
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Cancelled])
            ->where('expires_at', '<', now())
            ->each(fn (Subscription $s) => $subscriptions->markGrace($s));

        Subscription::query()
            ->where('status', SubscriptionStatus::Grace)
            ->where('expires_at', '<', now()->subDays(SubscriptionService::GRACE_DAYS))
            ->each(fn (Subscription $s) => $subscriptions->freeze($s));
    }
}

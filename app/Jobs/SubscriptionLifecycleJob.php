<?php

namespace App\Jobs;

use App\Enums\SubscriptionStatus;
use App\Mail\SubscriptionGraceMail;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

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
            ->each(function (Subscription $s) use ($subscriptions) {
                $subscriptions->markGrace($s);
                $this->sendGraceEmail($s->fresh(), 1);
            });

        Subscription::query()
            ->where('status', SubscriptionStatus::Grace)
            ->each(function (Subscription $s) {
                $daysSinceExpiry = (int) $s->expires_at?->diffInDays(now());

                foreach ([3, 7] as $day) {
                    if ($daysSinceExpiry === $day) {
                        $this->sendGraceEmail($s, $day);
                    }
                }
            });

        Subscription::query()
            ->where('status', SubscriptionStatus::Grace)
            ->where('expires_at', '<', now()->subDays(SubscriptionService::GRACE_DAYS))
            ->each(fn (Subscription $s) => $subscriptions->freeze($s));
    }

    private function sendGraceEmail(Subscription $subscription, int $day): void
    {
        $cacheKey = "grace_email:{$subscription->id}:{$day}";

        if (! Cache::add($cacheKey, true, now()->addDays(30))) {
            return;
        }

        Mail::to($subscription->user)->send(
            new SubscriptionGraceMail($subscription->user, $subscription, $day)
        );
    }
}

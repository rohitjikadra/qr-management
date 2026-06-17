<?php

namespace App\Actions\Admin;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AdminSubscriptionService;

class BillingActions
{
    public function __construct(
        private readonly AdminSubscriptionService $subscriptions,
    ) {}

    public function setBillingDiscount(User $user, ?int $percent, ?string $note, User $admin): void
    {
        $this->subscriptions->setBillingDiscount($user, $percent, $note, $admin);
    }

    public function grantComplimentary(
        User $user,
        Plan $plan,
        int $durationDays,
        string $adminNote,
        User $admin,
    ): Subscription {
        return $this->subscriptions->grantComplimentary(
            $user,
            $plan,
            now()->addDays($durationDays),
            $adminNote,
            $admin,
        );
    }

    public function extendManual(Subscription $subscription, int $days, string $adminNote, User $admin): void
    {
        $this->subscriptions->extendManual($subscription, $days, $adminNote, $admin);
    }

    public function revokeManual(Subscription $subscription, string $adminNote, User $admin): void
    {
        $this->subscriptions->revokeManual($subscription, $adminNote, $admin);
    }
}

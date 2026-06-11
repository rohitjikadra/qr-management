<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PlanLimitService
{
    public const UNLIMITED = -1;

    /**
     * Effective plan for the user. Users without a pro-access
     * subscription fall back to the Free plan.
     */
    public function planFor(User $user): Plan
    {
        $subscription = $user->activeSubscription;

        if ($subscription && $subscription->status->grantsProAccess()) {
            return $subscription->plan;
        }

        return $this->freePlan();
    }

    public function limits(User $user): array
    {
        return $this->planFor($user)->limits;
    }

    public function limit(User $user, string $key, mixed $default = 0): mixed
    {
        return $this->limits($user)[$key] ?? $default;
    }

    /**
     * Boolean feature flags (svg_download, custom_logo, custom_colors...).
     */
    public function can(User $user, string $feature): bool
    {
        return (bool) $this->limit($user, $feature, false);
    }

    public function canCreateDynamicQr(User $user): bool
    {
        $limit = (int) $this->limit($user, 'dynamic_qr');

        if ($limit === self::UNLIMITED) {
            return true;
        }

        return $this->dynamicQrCount($user) < $limit;
    }

    public function canCreateStaticQr(User $user): bool
    {
        $limit = (int) $this->limit($user, 'static_qr', self::UNLIMITED);

        if ($limit === self::UNLIMITED) {
            return true;
        }

        return $user->qrCodes()->where('is_dynamic', false)->count() < $limit;
    }

    public function dynamicQrCount(User $user): int
    {
        return $user->qrCodes()->where('is_dynamic', true)->count();
    }

    public function dynamicQrLimit(User $user): int
    {
        return (int) $this->limit($user, 'dynamic_qr');
    }

    public function analyticsHistoryDays(User $user): int
    {
        return (int) $this->limit($user, 'analytics_history_days', 30);
    }

    public function scansPerMonth(User $user): int
    {
        return (int) $this->limit($user, 'scans_per_month', 0);
    }

    private function freePlan(): Plan
    {
        return Cache::remember(
            'plan:free',
            now()->addHour(),
            fn () => Plan::where('slug', 'free')->firstOrFail()
        );
    }
}

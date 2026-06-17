<?php

namespace App\Services;

use App\Enums\QrType;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\QrCode;
use App\Models\QrScanEvent;
use App\Models\Subscription;
use App\Models\User;

class AdminDashboardService
{
    /**
     * @return array<string, int|float>
     */
    public function stats(): array
    {
        $mrrMonthly = Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('plans.billing_cycle', 'monthly')
            ->sum('plans.price');

        $mrrYearly = Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('plans.billing_cycle', 'yearly')
            ->sum('plans.price') / 12;

        $revenueThisMonth = Payment::where('status', 'paid')
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount');

        $revenueLastMonth = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ])
            ->sum('amount');

        $growth = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : ($revenueThisMonth > 0 ? 100.0 : 0.0);

        return [
            'total_users' => User::count(),
            'new_users_30d' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'paid_users' => Subscription::where('status', SubscriptionStatus::Active)->count(),
            'mrr' => (float) ($mrrMonthly + $mrrYearly),
            'revenue_this_month' => (float) $revenueThisMonth,
            'revenue_growth_percent' => $growth,
            'total_qr_codes' => QrCode::count(),
            'active_qr_codes' => QrCode::where('status', 'active')->count(),
            'total_scans' => QrScanEvent::count(),
            'scans_this_month' => QrScanEvent::where('scanned_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    public function signupsChart(): array
    {
        $labels = [];
        $values = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M j');
            $values[] = User::whereDate('created_at', $date->toDateString())->count();
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    public function qrTypeDistribution(): array
    {
        $counts = QrCode::query()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->orderByDesc('total')
            ->pluck('total', 'type');

        $labels = [];
        $values = [];

        foreach ($counts as $type => $total) {
            $labels[] = QrType::tryFrom($type)?->label() ?? (string) $type;
            $values[] = (int) $total;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}

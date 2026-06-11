<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $freeLimits = [
            'dynamic_qr' => 2,
            'static_qr' => -1,
            'scans_per_month' => 100,
            'analytics_history_days' => 30,
            'custom_logo' => false,
            'custom_colors' => false,
            'svg_download' => false,
            'ads' => true,
        ];

        $proLimits = [
            'dynamic_qr' => -1,
            'static_qr' => -1,
            'scans_per_month' => -1,
            'analytics_history_days' => -1,
            'custom_logo' => true,
            'custom_colors' => true,
            'svg_download' => true,
            'ads' => false,
        ];

        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'price' => 0,
                'billing_cycle' => 'free',
                'limits' => $freeLimits,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro Monthly',
                'slug' => 'pro_monthly',
                'price' => 249,
                'billing_cycle' => 'monthly',
                'limits' => $proLimits,
                'sort_order' => 2,
            ],
            [
                'name' => 'Pro Yearly',
                'slug' => 'pro_yearly',
                'price' => 2499,
                'billing_cycle' => 'yearly',
                'limits' => $proLimits,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}

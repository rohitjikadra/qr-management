<?php

namespace App\Support;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Collection;

class PlanPricing
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function cards(?User $user = null): Collection
    {
        return Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan) => self::card($plan, $user));
    }

    /**
     * @return array<string, mixed>
     */
    public static function card(Plan $plan, ?User $user): array
    {
        $price = (float) $plan->price;
        $discounted = $user ? $user->discountedPriceFor($plan) : $price;

        return [
            'slug' => $plan->slug,
            'name' => $plan->name,
            'price' => $price,
            'discounted_price' => $discounted,
            'has_discount' => $user?->hasBillingDiscount() && $discounted < $price,
            'currency' => $plan->currency,
            'billing_cycle' => $plan->billing_cycle->value,
            'limits' => $plan->limits,
        ];
    }
}

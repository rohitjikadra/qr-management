<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Support\PlanPricing;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user()?->fresh();

        return Inertia::render('welcome', [
            'plans' => PlanPricing::cards($user),
            'billing_discount_percent' => $user?->billing_discount_percent,
        ]);
    }
}

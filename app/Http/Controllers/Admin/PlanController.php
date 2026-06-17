<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Models\AuditLog;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(): Response
    {
        $plans = Plan::query()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price' => (float) $plan->price,
                'currency' => $plan->currency,
                'billing_cycle' => $plan->billing_cycle->value,
                'is_active' => $plan->is_active,
                'sort_order' => $plan->sort_order,
                'subscriptions_count' => $plan->subscriptions()->count(),
            ]);

        return Inertia::render('admin/plans/index', [
            'plans' => $plans,
        ]);
    }

    public function edit(Plan $plan): Response
    {
        return Inertia::render('admin/plans/edit', [
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price' => (float) $plan->price,
                'currency' => $plan->currency,
                'billing_cycle' => $plan->billing_cycle->value,
                'razorpay_plan_id' => $plan->razorpay_plan_id,
                'limits' => json_encode($plan->limits ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'limits_original' => json_encode($plan->limits ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'is_active' => $plan->is_active,
                'sort_order' => $plan->sort_order,
            ],
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validated();
        $oldLimits = $plan->limits;

        $plan->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'razorpay_plan_id' => $validated['razorpay_plan_id'] ?? null,
            'limits' => json_decode($validated['limits'], true),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'],
        ]);

        AuditLog::record('plan.updated', $plan, [
            'limits_changed' => $oldLimits !== json_decode($validated['limits'], true),
            'updated_by' => $request->user()?->id,
        ], $request->user()?->id);

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }
}

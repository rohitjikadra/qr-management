<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Services\RazorpayGateway;
use Illuminate\Console\Command;

class SetupRazorpayPlans extends Command
{
    protected $signature = 'razorpay:setup-plans {--force : Recreate plans even if already linked}';

    protected $description = 'Create paid plans on Razorpay and store their plan IDs locally';

    public function handle(RazorpayGateway $gateway): int
    {
        if (! $gateway->isConfigured()) {
            $this->error('Razorpay is not configured. Set RAZORPAY_KEY and RAZORPAY_SECRET in .env first.');

            return self::FAILURE;
        }

        $plans = Plan::where('is_active', true)
            ->where('billing_cycle', '!=', 'free')
            ->get();

        foreach ($plans as $plan) {
            if ($plan->razorpay_plan_id && ! $this->option('force')) {
                $this->line("• {$plan->name}: already linked ({$plan->razorpay_plan_id}) — skipping.");

                continue;
            }

            try {
                $razorpayPlanId = $gateway->createPlan($plan);
                $plan->update(['razorpay_plan_id' => $razorpayPlanId]);
                $this->info("✓ {$plan->name} (₹{$plan->price}/{$plan->billing_cycle->value}) → {$razorpayPlanId}");
            } catch (\Throwable $e) {
                $this->error("✗ {$plan->name}: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('Done. Users can now subscribe from the /pricing page.');

        return self::SUCCESS;
    }
}

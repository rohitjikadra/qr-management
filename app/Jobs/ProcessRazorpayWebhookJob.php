<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ProcessRazorpayWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public array $payload,
    ) {}

    public function handle(SubscriptionService $subscriptions): void
    {
        $event = $this->payload['event'] ?? null;

        $subscriptionData = Arr::get($this->payload, 'payload.subscription.entity', []);
        $paymentData = Arr::get($this->payload, 'payload.payment.entity', []);

        $gatewaySubscriptionId = $subscriptionData['id']
            ?? $paymentData['subscription_id']
            ?? null;

        $subscription = $gatewaySubscriptionId
            ? Subscription::where('gateway_subscription_id', $gatewaySubscriptionId)->first()
            : null;

        if (! $subscription) {
            Log::info('Razorpay webhook for unknown subscription', ['event' => $event, 'id' => $gatewaySubscriptionId]);

            return;
        }

        match ($event) {
            'subscription.activated' => $this->onActivated($subscription, $subscriptions),
            'subscription.charged' => $this->onCharged($subscription, $paymentData, $subscriptions),
            'payment.failed' => $this->onPaymentFailed($subscription, $paymentData),
            'subscription.cancelled' => $subscriptions->markCancelled($subscription),
            'subscription.completed',
            'subscription.expired' => $subscriptions->markGrace($subscription),
            default => Log::info('Unhandled Razorpay event', ['event' => $event]),
        };
    }

    private function onActivated(Subscription $subscription, SubscriptionService $subscriptions): void
    {
        $subscriptions->activate($subscription);
        AuditLog::record('subscription.activated', $subscription, userId: $subscription->user_id);
    }

    private function onCharged(Subscription $subscription, array $paymentData, SubscriptionService $subscriptions): void
    {
        if ($paymentData === []) {
            return;
        }

        $subscriptions->recordCharge($subscription, $paymentData);
        AuditLog::record('payment.recorded', $subscription, ['payment_id' => $paymentData['id'] ?? null], $subscription->user_id);
    }

    private function onPaymentFailed(Subscription $subscription, array $paymentData): void
    {
        if (($paymentData['id'] ?? null) === null) {
            return;
        }

        Payment::firstOrCreate(
            ['gateway_payment_id' => $paymentData['id']],
            [
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'gateway' => 'razorpay',
                'amount' => ($paymentData['amount'] ?? 0) / 100,
                'currency' => $paymentData['currency'] ?? 'INR',
                'status' => 'failed',
                'meta' => $paymentData,
            ]
        );

        AuditLog::record('payment.failed', $subscription, userId: $subscription->user_id);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Payment::query()
            ->with('user:id,name,email')
            ->latest('id');

        if ($search = trim($request->string('search')->value())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->whereLike('invoice_number', "%{$search}%", caseSensitive: false)
                    ->orWhereLike('gateway_payment_id', "%{$search}%", caseSensitive: false)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->whereLike('email', "%{$search}%", caseSensitive: false));
            });
        }

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        $payments = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Payment $payment): array => [
                'id' => $payment->id,
                'user' => $payment->user ? [
                    'id' => $payment->user->id,
                    'name' => $payment->user->name,
                    'email' => $payment->user->email,
                ] : null,
                'invoice_number' => $payment->invoice_number,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status->value,
                'gateway' => $payment->gateway,
                'paid_at' => $payment->paid_at?->toDateTimeString(),
                'created_at' => $payment->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('admin/payments/index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'status']),
            'totalPayments' => Payment::count(),
            'statusOptions' => collect(PaymentStatus::cases())->map(fn (PaymentStatus $status) => [
                'value' => $status->value,
                'label' => str($status->value)->headline()->toString(),
            ])->values(),
        ]);
    }

    public function show(Payment $payment): Response
    {
        $payment->load(['user:id,name,email', 'subscription.plan']);

        return Inertia::render('admin/payments/show', [
            'payment' => [
                'id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status->value,
                'gateway' => $payment->gateway,
                'gateway_payment_id' => $payment->gateway_payment_id,
                'gateway_order_id' => $payment->gateway_order_id,
                'meta' => $payment->meta,
                'meta_json' => json_encode($payment->meta ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'paid_at' => $payment->paid_at?->toDateTimeString(),
                'created_at' => $payment->created_at?->toDateTimeString(),
                'user' => $payment->user ? [
                    'id' => $payment->user->id,
                    'name' => $payment->user->name,
                    'email' => $payment->user->email,
                ] : null,
                'subscription' => $payment->subscription ? [
                    'id' => $payment->subscription->id,
                    'plan_name' => $payment->subscription->plan?->name,
                    'status' => $payment->subscription->status->value,
                ] : null,
            ],
            'canMarkRefunded' => $payment->status === PaymentStatus::Paid,
        ]);
    }

    public function markRefunded(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->status === PaymentStatus::Paid, 403);

        $payment->update(['status' => PaymentStatus::Refunded]);

        AuditLog::record('payment.refunded', $payment, [
            'marked_by' => $request->user()?->id,
            'razorpay_api' => false,
            'note' => 'Manual admin placeholder — Razorpay refund API not called.',
        ], $request->user()?->id);

        return back()->with('success', 'Payment marked as refunded (local record only).');
    }

    public function export(): StreamedResponse
    {
        $filename = 'payments-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'id', 'user_email', 'invoice_number', 'amount', 'currency',
                'status', 'gateway', 'gateway_payment_id', 'paid_at', 'created_at',
            ]);

            Payment::query()
                ->with('user:id,email')
                ->orderBy('id')
                ->chunk(200, function ($payments) use ($handle): void {
                    foreach ($payments as $payment) {
                        fputcsv($handle, [
                            $payment->id,
                            $payment->user?->email,
                            $payment->invoice_number,
                            $payment->amount,
                            $payment->currency,
                            $payment->status->value,
                            $payment->gateway,
                            $payment->gateway_payment_id,
                            $payment->paid_at?->toDateTimeString(),
                            $payment->created_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}

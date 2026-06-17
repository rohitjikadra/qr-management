<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePaymentControlsRequest;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Support\BillingSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentControlsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/billing/payment-controls', [
            'payments_enabled' => BillingSettings::paymentsEnabled(),
            'payments_disabled_message' => Setting::get('payments_disabled_message', ''),
            'billing_mode' => BillingSettings::billingMode(),
        ]);
    }

    public function update(UpdatePaymentControlsRequest $request): RedirectResponse
    {
        $enabled = $request->boolean('payments_enabled');

        Setting::set('payments_enabled', $enabled ? '1' : '0');
        Setting::set('payments_disabled_message', $request->input('payments_disabled_message'));

        AuditLog::record(
            $enabled ? 'payments.enabled' : 'payments.disabled',
            meta: [
                'message' => $request->input('payments_disabled_message'),
            ],
            userId: $request->user()->id,
        );

        return back()->with('success', $enabled ? 'Payments are now open.' : 'Payments are now closed.');
    }
}

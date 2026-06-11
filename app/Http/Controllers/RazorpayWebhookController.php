<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessRazorpayWebhookJob;
use App\Services\RazorpayGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    public function __invoke(Request $request, RazorpayGateway $gateway)
    {
        $signature = $request->header('X-Razorpay-Signature', '');

        if (! $gateway->verifyWebhookSignature($request->getContent(), $signature)) {
            Log::warning('Razorpay webhook signature verification failed', ['ip' => $request->ip()]);

            return response()->json(['error' => 'invalid signature'], 400);
        }

        $eventId = $request->header('x-razorpay-event-id');

        // Razorpay retries webhooks — process each event exactly once.
        if ($eventId && ! Cache::add('razorpay:event:'.$eventId, true, now()->addDays(7))) {
            return response()->json(['status' => 'duplicate']);
        }

        ProcessRazorpayWebhookJob::dispatch($request->json()->all());

        return response()->json(['status' => 'queued']);
    }
}

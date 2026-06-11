<?php

namespace App\Http\Controllers;

use App\Jobs\RecordScanJob;
use App\Services\RedirectResolver;
use Illuminate\Http\Request;

/**
 * The hottest path in the application. No Inertia, no auth,
 * no database writes — Redis read, queue push, 302.
 */
class RedirectController extends Controller
{
    public function __invoke(Request $request, string $slug, RedirectResolver $resolver)
    {
        $data = $resolver->resolve($slug);

        if (! $data) {
            return response()->view('redirect.not-found', ['slug' => $slug], 404);
        }

        if ($data['status'] !== 'active') {
            return response()->view('redirect.paused', ['slug' => $slug]);
        }

        if ($data['expires_at'] !== null && now()->greaterThan($data['expires_at'])) {
            return response()->view('redirect.expired', ['slug' => $slug]);
        }

        RecordScanJob::dispatch(
            $slug,
            $request->ip(),
            $request->userAgent(),
            $request->header('referer'),
            now(),
        );

        return redirect()->away($data['url'], 302);
    }
}

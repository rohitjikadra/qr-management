<?php

namespace App\Services;

use App\Models\QrCode;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves a QR slug to its redirect target. This sits on the hottest
 * path of the application, so it reads from Redis and never writes
 * to the database.
 */
class RedirectResolver
{
    private const MISSING = '__missing__';

    /**
     * Negative results are cached briefly to protect the DB from
     * scans of deleted/unknown slugs.
     */
    private const MISSING_TTL_SECONDS = 600;

    /**
     * @return array{url: string|null, status: string, expires_at: string|null}|null
     */
    public function resolve(string $slug): ?array
    {
        $key = QrCode::CACHE_PREFIX.$slug;

        $cached = Cache::get($key);

        if ($cached === self::MISSING) {
            return null;
        }

        if (is_array($cached)) {
            return $cached;
        }

        $qr = QrCode::query()
            ->where('slug', $slug)
            ->where('is_dynamic', true)
            ->first(['id', 'destination_url', 'status', 'expires_at']);

        if (! $qr) {
            Cache::put($key, self::MISSING, self::MISSING_TTL_SECONDS);

            return null;
        }

        $data = [
            'url' => $qr->destination_url,
            'status' => $qr->status->value,
            'expires_at' => $qr->expires_at?->toIso8601String(),
        ];

        Cache::put($key, $data);

        return $data;
    }
}

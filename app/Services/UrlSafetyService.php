<?php

namespace App\Services;

use App\Models\BlockedDomain;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UrlSafetyService
{
    /**
     * URL shorteners are rejected because they are commonly used
     * to hide phishing destinations behind our redirect.
     */
    private const SHORTENERS = [
        'bit.ly', 'tinyurl.com', 't.co', 'goo.gl', 'is.gd', 'buff.ly',
        'ow.ly', 'rb.gy', 'cutt.ly', 'shorturl.at', 'tiny.cc', 'rebrand.ly',
        's.id', 'v.gd', 'qr.ae', 'lnkd.in', 'shorte.st', 'adf.ly',
    ];

    /**
     * Returns null when the URL is acceptable, otherwise a
     * user-facing rejection reason.
     */
    public function check(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '') {
            return 'Invalid URL.';
        }

        $host = preg_replace('/^www\./', '', $host);

        if (in_array($host, self::SHORTENERS, true)) {
            return 'Shortened URLs are not allowed. Please use the final destination URL.';
        }

        if (BlockedDomain::where('domain', $host)->exists()) {
            return 'This domain is not allowed.';
        }

        if ($this->flaggedBySafeBrowsing($url)) {
            return 'This URL has been flagged as unsafe.';
        }

        return null;
    }

    /**
     * Google Safe Browsing v4 lookup. Fails open (allows the URL)
     * when the API is not configured or unreachable.
     */
    private function flaggedBySafeBrowsing(string $url): bool
    {
        $apiKey = config('services.safe_browsing.key');

        if (! $apiKey) {
            return false;
        }

        try {
            $response = Http::timeout(5)->post(
                'https://safebrowsing.googleapis.com/v4/threatMatches:find?key='.$apiKey,
                [
                    'client' => [
                        'clientId' => config('app.name'),
                        'clientVersion' => '1.0',
                    ],
                    'threatInfo' => [
                        'threatTypes' => ['MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE'],
                        'platformTypes' => ['ANY_PLATFORM'],
                        'threatEntryTypes' => ['URL'],
                        'threatEntries' => [['url' => $url]],
                    ],
                ]
            );

            return $response->successful() && ! empty($response->json('matches'));
        } catch (\Throwable $e) {
            Log::warning('Safe Browsing check failed', ['url' => $url, 'error' => $e->getMessage()]);

            return false;
        }
    }
}

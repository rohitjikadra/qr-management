<?php

namespace App\Jobs;

use App\Models\QrCode;
use App\Models\QrScanEvent;
use App\Services\GeoLocator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

class RecordScanJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public string $slug,
        public string $ip,
        public ?string $userAgent,
        public ?string $referrer,
        public Carbon $scannedAt,
    ) {
        $this->onQueue('scans');
    }

    public function handle(GeoLocator $geo): void
    {
        $qr = QrCode::where('slug', $this->slug)->first();

        if (! $qr) {
            return;
        }

        $agent = new Agent;
        $agent->setUserAgent($this->userAgent ?? '');

        [$country, $city] = $geo->locate($this->ip);

        QrScanEvent::create([
            'qr_code_id' => $qr->id,
            'country' => $country,
            'city' => $city,
            'device_type' => $agent->isTablet() ? 'tablet' : ($agent->isMobile() ? 'mobile' : 'desktop'),
            'os' => $agent->platform() ?: null,
            'browser' => $agent->browser() ?: null,
            'referrer' => $this->referrer,
            'ip_hash' => hash('sha256', $this->ip.config('qr.scan_salt')),
            'scanned_at' => $this->scannedAt,
        ]);

        // Raw query: avoids firing model events (and the cache
        // invalidation they trigger) on every single scan.
        DB::table('qr_codes')->where('id', $qr->id)->update([
            'scan_count' => DB::raw('scan_count + 1'),
            'last_scanned_at' => $this->scannedAt,
        ]);
    }
}

<?php

namespace App\Services;

use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\Log;

class GeoLocator
{
    private ?Reader $reader = null;

    private bool $initialized = false;

    /**
     * @return array{0: string|null, 1: string|null} [country ISO code, city name]
     */
    public function locate(string $ip): array
    {
        $reader = $this->reader();

        if (! $reader) {
            return [null, null];
        }

        try {
            $record = $reader->city($ip);

            return [$record->country->isoCode, $record->city->name];
        } catch (\Throwable) {
            // Private/unknown IPs or lookup failures — geo data is optional.
            return [null, null];
        }
    }

    private function reader(): ?Reader
    {
        if (! $this->initialized) {
            $this->initialized = true;
            $path = config('qr.geoip_database');

            if (is_string($path) && is_file($path)) {
                try {
                    $this->reader = new Reader($path);
                } catch (\Throwable $e) {
                    Log::warning('GeoIP database could not be loaded', ['error' => $e->getMessage()]);
                }
            }
        }

        return $this->reader;
    }
}

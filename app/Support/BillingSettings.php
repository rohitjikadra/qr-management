<?php

namespace App\Support;

use App\Models\Setting;

class BillingSettings
{
    public const MODE_MANUAL = 'manual_renewal';

    public const MODE_AUTOPAY = 'autopay';

    public static function paymentsEnabled(): bool
    {
        return Setting::get('payments_enabled', '1') !== '0';
    }

    public static function disabledMessage(): string
    {
        $custom = Setting::get('payments_disabled_message');

        if (is_string($custom) && trim($custom) !== '') {
            return trim($custom);
        }

        return 'Payments are temporarily unavailable. Please try again later.';
    }

    public static function billingMode(): string
    {
        $mode = Setting::get('billing_mode', self::MODE_MANUAL);

        return $mode === self::MODE_AUTOPAY ? self::MODE_AUTOPAY : self::MODE_MANUAL;
    }

    public static function isManualRenewal(): bool
    {
        return self::billingMode() === self::MODE_MANUAL;
    }

    public static function isAutopay(): bool
    {
        return self::billingMode() === self::MODE_AUTOPAY;
    }

    /**
     * @return array{payments_enabled: bool, payments_disabled_message: string, billing_mode: string}
     */
    public static function forFrontend(): array
    {
        return [
            'payments_enabled' => self::paymentsEnabled(),
            'payments_disabled_message' => self::disabledMessage(),
            'billing_mode' => self::billingMode(),
        ];
    }
}

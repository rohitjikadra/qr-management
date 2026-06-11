<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Grace = 'grace';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Frozen = 'frozen';

    /**
     * Statuses where the user still has paid-plan benefits.
     */
    public function grantsProAccess(): bool
    {
        return in_array($this, [self::Active, self::Grace, self::Cancelled], true);
    }
}

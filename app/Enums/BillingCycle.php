<?php

namespace App\Enums;

enum BillingCycle: string
{
    case Free = 'free';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}

<?php

namespace App\Enums;

enum RenewalType: string
{
    case Manual = 'manual';
    case Autopay = 'autopay';
}

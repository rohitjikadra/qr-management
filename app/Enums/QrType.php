<?php

namespace App\Enums;

enum QrType: string
{
    case Url = 'url';
    case Whatsapp = 'whatsapp';
    case Email = 'email';
    case Phone = 'phone';
    case Wifi = 'wifi';
    case Vcard = 'vcard';
    case Text = 'text';

    /**
     * Types that can be created as dynamic (server-redirectable) QRs in V1.
     */
    public function supportsDynamic(): bool
    {
        return in_array($this, [self::Url, self::Whatsapp], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Url => 'Website URL',
            self::Whatsapp => 'WhatsApp',
            self::Email => 'Email',
            self::Phone => 'Phone Call',
            self::Wifi => 'WiFi',
            self::Vcard => 'Contact Card (vCard)',
            self::Text => 'Plain Text',
        };
    }
}

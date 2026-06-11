<?php

namespace App\Services;

use App\Models\QrCode;

class SlugGenerator
{
    /**
     * Alphanumeric set excluding visually confusing characters (0/O, 1/l/I).
     */
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';

    private const LENGTH = 8;

    public function generate(): string
    {
        do {
            $slug = $this->random();
        } while (QrCode::withTrashed()->where('slug', $slug)->exists());

        return $slug;
    }

    private function random(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $slug = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $slug .= self::ALPHABET[random_int(0, $max)];
        }

        return $slug;
    }
}

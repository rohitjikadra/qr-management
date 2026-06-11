<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use InvalidArgumentException;

class QrImageService
{
    public const SIZES = [256, 512, 1024];

    public const FORMATS = ['png', 'svg'];

    /**
     * Render a QR image binary on demand. Images are never persisted.
     */
    public function render(string $payload, string $format = 'png', int $size = 512): string
    {
        if (! in_array($format, self::FORMATS, true)) {
            throw new InvalidArgumentException("Unsupported QR format [{$format}].");
        }

        if (! in_array($size, self::SIZES, true)) {
            throw new InvalidArgumentException("Unsupported QR size [{$size}].");
        }

        $builder = new Builder(
            writer: $format === 'svg' ? new SvgWriter : new PngWriter,
            data: $payload,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: (int) ($size / 16),
        );

        return $builder->build()->getString();
    }

    public function dataUri(string $payload, int $size = 512): string
    {
        return 'data:image/png;base64,'.base64_encode($this->render($payload, 'png', $size));
    }

    public function mimeType(string $format): string
    {
        return $format === 'svg' ? 'image/svg+xml' : 'image/png';
    }
}

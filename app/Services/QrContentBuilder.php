<?php

namespace App\Services;

use App\Enums\QrType;
use App\Models\QrCode;

class QrContentBuilder
{
    /**
     * Build the final string encoded inside the QR image.
     *
     * Dynamic QRs encode the short redirect URL; static QRs encode
     * the actual content payload.
     */
    public function payloadFor(QrCode $qr): string
    {
        if ($qr->is_dynamic && $qr->slug) {
            return $qr->redirectUrl();
        }

        return $this->build($qr->type, $qr->content);
    }

    /**
     * Build the raw content payload for a QR type.
     */
    public function build(QrType $type, array $content): string
    {
        return match ($type) {
            QrType::Url => $content['url'],
            QrType::Whatsapp => $this->whatsapp($content),
            QrType::Email => $this->email($content),
            QrType::Phone => 'tel:'.$this->normalizePhone($content['phone']),
            QrType::Wifi => $this->wifi($content),
            QrType::Vcard => $this->vcard($content),
            QrType::Text => $content['text'],
        };
    }

    /**
     * The destination URL used by the redirect service (dynamic QRs only).
     */
    public function destinationUrl(QrType $type, array $content): ?string
    {
        return match ($type) {
            QrType::Url => $content['url'],
            QrType::Whatsapp => $this->whatsapp($content),
            default => null,
        };
    }

    private function whatsapp(array $content): string
    {
        $phone = preg_replace('/[^0-9]/', '', $content['phone']);
        $url = 'https://wa.me/'.$phone;

        if (! empty($content['message'])) {
            $url .= '?text='.rawurlencode($content['message']);
        }

        return $url;
    }

    private function email(array $content): string
    {
        $url = 'mailto:'.$content['to'];
        $params = array_filter([
            'subject' => $content['subject'] ?? null,
            'body' => $content['body'] ?? null,
        ]);

        if ($params !== []) {
            $url .= '?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }

    private function wifi(array $content): string
    {
        $security = $content['security'] ?? 'WPA';
        $parts = 'WIFI:T:'.($security === 'None' ? 'nopass' : $security).';';
        $parts .= 'S:'.$this->escapeWifi($content['ssid']).';';

        if ($security !== 'None' && ! empty($content['password'])) {
            $parts .= 'P:'.$this->escapeWifi($content['password']).';';
        }

        if (! empty($content['hidden'])) {
            $parts .= 'H:true;';
        }

        return $parts.';';
    }

    private function escapeWifi(string $value): string
    {
        return addcslashes($value, '\\;,":');
    }

    private function vcard(array $content): string
    {
        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            sprintf('N:%s;%s;;;', $this->escapeVcard($content['last_name'] ?? ''), $this->escapeVcard($content['first_name'])),
            sprintf('FN:%s', $this->escapeVcard(trim(($content['first_name'] ?? '').' '.($content['last_name'] ?? '')))),
        ];

        if (! empty($content['organization'])) {
            $lines[] = 'ORG:'.$this->escapeVcard($content['organization']);
        }
        if (! empty($content['job_title'])) {
            $lines[] = 'TITLE:'.$this->escapeVcard($content['job_title']);
        }
        if (! empty($content['phone'])) {
            $lines[] = 'TEL;TYPE=CELL:'.$this->escapeVcard($content['phone']);
        }
        if (! empty($content['email'])) {
            $lines[] = 'EMAIL:'.$this->escapeVcard($content['email']);
        }
        if (! empty($content['website'])) {
            $lines[] = 'URL:'.$this->escapeVcard($content['website']);
        }

        $address = array_filter([
            $content['street'] ?? null,
            $content['city'] ?? null,
            $content['state'] ?? null,
            $content['zip'] ?? null,
            $content['country'] ?? null,
        ]);

        if ($address !== []) {
            $lines[] = sprintf(
                'ADR;TYPE=WORK:;;%s;%s;%s;%s;%s',
                $this->escapeVcard($content['street'] ?? ''),
                $this->escapeVcard($content['city'] ?? ''),
                $this->escapeVcard($content['state'] ?? ''),
                $this->escapeVcard($content['zip'] ?? ''),
                $this->escapeVcard($content['country'] ?? ''),
            );
        }

        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines);
    }

    private function escapeVcard(string $value): string
    {
        return addcslashes($value, "\\;,");
    }
}

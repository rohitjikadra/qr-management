<?php

namespace Tests\Unit;

use App\Enums\QrType;
use App\Services\QrContentBuilder;
use PHPUnit\Framework\TestCase;

class QrContentBuilderTest extends TestCase
{
    private QrContentBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new QrContentBuilder;
    }

    public function test_url_payload(): void
    {
        $this->assertSame(
            'https://example.com',
            $this->builder->build(QrType::Url, ['url' => 'https://example.com'])
        );
    }

    public function test_whatsapp_payload_with_message(): void
    {
        $payload = $this->builder->build(QrType::Whatsapp, [
            'phone' => '+91 98765-43210',
            'message' => 'Hello there',
        ]);

        $this->assertSame('https://wa.me/919876543210?text=Hello%20there', $payload);
    }

    public function test_whatsapp_payload_without_message(): void
    {
        $payload = $this->builder->build(QrType::Whatsapp, ['phone' => '919876543210']);

        $this->assertSame('https://wa.me/919876543210', $payload);
    }

    public function test_email_payload(): void
    {
        $payload = $this->builder->build(QrType::Email, [
            'to' => 'hi@example.com',
            'subject' => 'Hello World',
        ]);

        $this->assertSame('mailto:hi@example.com?subject=Hello%20World', $payload);
    }

    public function test_phone_payload(): void
    {
        $this->assertSame(
            'tel:+919876543210',
            $this->builder->build(QrType::Phone, ['phone' => '+91 98765 43210'])
        );
    }

    public function test_wifi_payload_escapes_special_characters(): void
    {
        $payload = $this->builder->build(QrType::Wifi, [
            'ssid' => 'My;Net',
            'security' => 'WPA',
            'password' => 'pass:word',
        ]);

        $this->assertSame('WIFI:T:WPA;S:My\;Net;P:pass\:word;;', $payload);
    }

    public function test_open_wifi_has_no_password(): void
    {
        $payload = $this->builder->build(QrType::Wifi, [
            'ssid' => 'CafeFree',
            'security' => 'None',
        ]);

        $this->assertSame('WIFI:T:nopass;S:CafeFree;;', $payload);
    }

    public function test_vcard_payload(): void
    {
        $payload = $this->builder->build(QrType::Vcard, [
            'first_name' => 'Rohit',
            'last_name' => 'Sharma',
            'organization' => 'Acme',
            'phone' => '+919876543210',
        ]);

        $this->assertStringContainsString('BEGIN:VCARD', $payload);
        $this->assertStringContainsString('N:Sharma;Rohit;;;', $payload);
        $this->assertStringContainsString('FN:Rohit Sharma', $payload);
        $this->assertStringContainsString('ORG:Acme', $payload);
        $this->assertStringContainsString('TEL;TYPE=CELL:+919876543210', $payload);
        $this->assertStringContainsString('END:VCARD', $payload);
    }

    public function test_text_payload(): void
    {
        $this->assertSame(
            'Hello world',
            $this->builder->build(QrType::Text, ['text' => 'Hello world'])
        );
    }
}

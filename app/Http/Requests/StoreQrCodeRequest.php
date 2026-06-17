<?php

namespace App\Http\Requests;

use App\Enums\QrType;
use App\Services\PlanLimitService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQrCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(QrType::class)],
            'is_dynamic' => ['required', 'boolean'],
            'content' => ['required', 'array'],
            ...$this->contentRules(),
        ];
    }

    protected function contentRules(): array
    {
        $type = QrType::tryFrom((string) $this->input('type'));

        return match ($type) {
            QrType::Url => [
                'content.url' => ['required', 'url:http,https', 'max:2048'],
            ],
            QrType::Whatsapp => [
                'content.phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
                'content.message' => ['nullable', 'string', 'max:500'],
            ],
            QrType::Email => [
                'content.to' => ['required', 'email'],
                'content.subject' => ['nullable', 'string', 'max:200'],
                'content.body' => ['nullable', 'string', 'max:1000'],
            ],
            QrType::Phone => [
                'content.phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
            ],
            QrType::Wifi => [
                'content.ssid' => ['required', 'string', 'max:32'],
                'content.security' => ['required', Rule::in(['WPA', 'WEP', 'None'])],
                'content.password' => ['required_unless:content.security,None', 'nullable', 'string', 'max:63'],
                'content.hidden' => ['nullable', 'boolean'],
            ],
            QrType::Vcard => [
                'content.first_name' => ['required', 'string', 'max:50'],
                'content.last_name' => ['nullable', 'string', 'max:50'],
                'content.organization' => ['nullable', 'string', 'max:100'],
                'content.job_title' => ['nullable', 'string', 'max:100'],
                'content.phone' => ['nullable', 'string', 'max:20'],
                'content.email' => ['nullable', 'email'],
                'content.website' => ['nullable', 'url:http,https', 'max:2048'],
                'content.street' => ['nullable', 'string', 'max:100'],
                'content.city' => ['nullable', 'string', 'max:50'],
                'content.state' => ['nullable', 'string', 'max:50'],
                'content.zip' => ['nullable', 'string', 'max:20'],
                'content.country' => ['nullable', 'string', 'max:50'],
            ],
            QrType::Text => [
                'content.text' => ['required', 'string', 'max:1000'],
            ],
            default => [],
        };
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (! $this->user()->hasVerifiedEmail()) {
                    $validator->errors()->add(
                        'email',
                        'Please verify your email before creating QR codes.',
                    );

                    return;
                }

                if (! $this->boolean('is_dynamic')) {
                    return;
                }

                $type = QrType::from((string) $this->input('type'));

                if (! $type->supportsDynamic()) {
                    $validator->errors()->add('is_dynamic', 'This QR type does not support dynamic mode.');

                    return;
                }

                if (! app(PlanLimitService::class)->canCreateDynamicQr($this->user())) {
                    $validator->errors()->add('is_dynamic', 'You have reached your dynamic QR limit. Upgrade to Pro for unlimited dynamic QRs.');
                }
            },
        ];
    }
}

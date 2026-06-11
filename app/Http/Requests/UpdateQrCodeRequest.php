<?php

namespace App\Http\Requests;

class UpdateQrCodeRequest extends StoreQrCodeRequest
{
    public function rules(): array
    {
        $qr = $this->route('qr_code');

        $rules = [
            'name' => ['required', 'string', 'max:100'],
        ];

        // Static QR content is baked into the printed image and cannot change.
        if ($qr->is_dynamic) {
            $rules['content'] = ['required', 'array'];
            $rules = [...$rules, ...$this->contentRules()];
        }

        return $rules;
    }

    public function after(): array
    {
        return [];
    }

    protected function contentRules(): array
    {
        $this->merge(['type' => $this->route('qr_code')->type->value]);

        return parent::contentRules();
    }
}

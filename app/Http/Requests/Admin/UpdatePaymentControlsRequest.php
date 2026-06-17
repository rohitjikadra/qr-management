<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentControlsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->value === 'super_admin';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payments_enabled' => ['required', 'boolean'],
            'payments_disabled_message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

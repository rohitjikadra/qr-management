<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetBillingDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'billing_discount_percent' => ['nullable', 'integer', Rule::in([10, 25, 50, 75])],
            'billing_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('billing_discount_percent') && $this->input('billing_discount_percent') === '') {
            $this->merge(['billing_discount_percent' => null]);
        }
    }
}

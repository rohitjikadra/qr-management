<?php

namespace App\Http\Requests\Admin;

use App\Models\BlockedDomain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlockedDomainRequest extends FormRequest
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
            'domain' => ['required', 'string', 'max:255', Rule::unique(BlockedDomain::class, 'domain')],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('domain')) {
            $domain = strtolower(trim((string) $this->input('domain')));
            $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
            $domain = rtrim($domain, '/');

            $this->merge(['domain' => $domain]);
        }
    }
}

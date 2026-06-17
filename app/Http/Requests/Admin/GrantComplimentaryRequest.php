<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GrantComplimentaryRequest extends FormRequest
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
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'duration_days' => ['required', 'integer', Rule::in([7, 14, 30, 90, 365])],
            'admin_note' => ['required', 'string', 'max:2000'],
        ];
    }
}

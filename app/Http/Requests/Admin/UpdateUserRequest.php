<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        /** @var User $user */
        $user = $this->route('user');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'country' => ['nullable', 'string', 'max:100'],
            'billing_discount_percent' => ['nullable', 'integer', Rule::in([10, 25, 50, 75])],
            'billing_note' => ['nullable', 'string', 'max:2000'],
            'email_verified' => ['required', 'boolean'],
        ];

        if ($this->user()?->role === UserRole::SuperAdmin) {
            $rules['role'] = ['required', Rule::enum(UserRole::class)];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('billing_discount_percent') && $this->input('billing_discount_percent') === '') {
            $this->merge(['billing_discount_percent' => null]);
        }
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePlanRequest extends FormRequest
{
    private const LIMIT_KEYS = [
        'dynamic_qr',
        'static_qr',
        'scans_per_month',
        'analytics_history_days',
        'custom_logo',
        'custom_colors',
        'svg_download',
        'ads',
    ];

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
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'razorpay_plan_id' => ['nullable', 'string', 'max:255'],
            'limits' => ['required', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $decoded = json_decode((string) $this->input('limits'), true);

            if (! is_array($decoded)) {
                $validator->errors()->add('limits', 'Limits must be valid JSON.');

                return;
            }

            foreach (self::LIMIT_KEYS as $key) {
                if (! array_key_exists($key, $decoded)) {
                    $validator->errors()->add('limits', "Missing limit key: {$key}");
                }
            }

            foreach (['custom_logo', 'custom_colors', 'svg_download', 'ads'] as $boolKey) {
                if (array_key_exists($boolKey, $decoded) && ! is_bool($decoded[$boolKey])) {
                    $validator->errors()->add('limits', "{$boolKey} must be a boolean.");
                }
            }

            foreach (['dynamic_qr', 'static_qr', 'scans_per_month', 'analytics_history_days'] as $intKey) {
                if (array_key_exists($intKey, $decoded) && ! is_int($decoded[$intKey])) {
                    $validator->errors()->add('limits', "{$intKey} must be an integer (-1 for unlimited).");
                }
            }
        });
    }
}

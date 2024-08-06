<?php

namespace App\Http\Requests;

use App\Repositories\TierRepository;
use Illuminate\Foundation\Http\FormRequest;

class TierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'min_price' => 'required|integer',
            'max_price' => 'required',
            'cost_per_lead' => 'required|integer',
            'can_handle_ecommerce' => 'boolean',
            'can_handle_null_value' => 'boolean',
            'is_tpl_renewals' => 'boolean',
            'is_active' => 'boolean',
            'tier_user' => 'required|array',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'min_price' => (float) $this->min_price,
            'max_price' => (float) $this->max_price,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('min_price') && $this->filled('max_price')) {
                if ($this->min_price >= $this->max_price) {
                    $validator->errors()->add('min_price', 'Min price should be less than Max price.');
                    $validator->errors()->add('max_price', 'Max price should be greater than Min price.');
                }
            }

            if (! $this->filled('min_price') && TierRepository::whereNull('min_price')->exists()) {
                $validator->errors()->add('min_price', 'Only one tier can have null as minimum price.');
            }

            if (! $this->filled('max_price') && TierRepository::whereNull('max_price')->exists()) {
                $validator->errors()->add('max_price', 'Only one tier can have null as maximum price.');
            }
        });
    }
}

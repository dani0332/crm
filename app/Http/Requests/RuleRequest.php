<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\LeadSource;
use App\Models\QuoteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RuleRequest extends FormRequest
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
            'rule_type' => 'required',
            'is_active' => 'boolean',
            'rule_users' => 'required|array',
            'quote_type_id' => ['required', 'integer', Rule::exists(QuoteType::class, 'id')],
            'lead_source_id' => [
                'nullable',
                'integer',
                Rule::exists(LeadSource::class, 'id'),
                Rule::requiredIf(function () {
                    // Rule type 1 is "LEAD SOURCE"
                    return $this->rule_type == 1;
                }),
            ],
            'utm_source' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
        ];
    }
}

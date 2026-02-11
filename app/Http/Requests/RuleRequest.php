<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\LeadSource;
use App\Models\QuoteType;
use App\Models\RuleDetail;
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
                function ($attribute, $value, $fail) {
                    // Only validate uniqueness if rule_type is Lead Source (1)
                    if ($this->rule_type != 1 || ! $value) {
                        return;
                    }

                    $this->validateUniqueUtmCombination($value, $fail);
                },
            ],
            'utm_source' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
        ];
    }

    /**
     * Validate unique combination of lead source name and UTM parameters
     */
    protected function validateUniqueUtmCombination($leadSourceId, $fail): void
    {
        $leadSource = LeadSource::find($leadSourceId);

        if (! $leadSource) {
            return;
        }

        $query = RuleDetail::query()
            ->join('lead_sources', 'rule_details.lead_source_id', '=', 'lead_sources.id')
            ->where('lead_sources.name', $leadSource->name)
            ->where('rule_details.utm_source', $this->utm_source)
            ->where('rule_details.utm_campaign', $this->utm_campaign)
            ->where('rule_details.utm_medium', $this->utm_medium);

        // Exclude current rule when updating (resource route parameter is 'rule')
        if ($this->route()->hasParameter('rule')) {
            $query->where('rule_details.rule_id', '!=', $this->route()->parameter('rule'));
        }

        if ($query->exists()) {
            $fail('The combination of Lead Source URL, UTM Source, UTM Campaign, and UTM Medium already exists.');
        }
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'lead_source_id.required_if' => 'The Lead Source URL field is required when rule type is Lead Source.',
        ];
    }
}

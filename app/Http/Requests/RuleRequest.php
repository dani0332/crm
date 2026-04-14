<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use App\Enums\RuleTypeEnum;
use App\Models\LeadSource;
use App\Models\QuoteType;
use App\Models\RuleDetail;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        return match ($this->route()?->getName()) {
            'rule.store' => $user->can(PermissionsEnum::RULE_CONFIG_CREATE),
            'rule.update' => $user->can(PermissionsEnum::RULE_CONFIG_UPDATE),
            default => false,
        };
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
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
                    return $this->rule_type == RuleTypeEnum::LEAD_SOURCE;
                }),
                function ($attribute, $value, $fail) {
                    if ($this->rule_type != RuleTypeEnum::LEAD_SOURCE || ! $value) {
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
            ->join('rules', 'rule_details.rule_id', '=', 'rules.id')
            ->where('lead_sources.name', $leadSource->name)
            ->where('rule_details.utm_campaign', $this->utm_campaign)
            ->where('rules.name', $this->name)
            ->where('rules.rule_type', $this->rule_type)
            ->where('rules.quote_type_id', $this->quote_type_id);

        // Exclude current rule when updating (resource route parameter is 'rule')
        if ($this->route()->hasParameter('rule')) {
            $query->where('rule_details.rule_id', '!=', $this->route()->parameter('rule'));
        }

        if ($query->exists()) {
            $fail('The combination of Quote Type,Lead Source, UTM Campaign,Rule Name and Rule Type already exists.');
        }
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'lead_source_id.required_if' => 'The Lead Source field is required when rule type is Lead Source.',
        ];
    }
}

<?php

namespace App\Http\Requests\V2\Admin;

use App\Enums\QuoteTypes;
use App\Models\Nationality;
use App\Models\QuoteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrivateClientConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quote_type_id' => ['required', 'integer', Rule::exists(QuoteType::class, 'id')],
            'quote_type' => ['required', Rule::enum(QuoteTypes::class)],
            'config' => ['required', 'array'],
            'config.profiles' => ['sometimes', 'array', 'min:1'],
            'config.profiles.*' => ['array'],
            'config.profiles.*.nationalityIds' => ['sometimes', 'array'],
            'config.profiles.*.nationalityIds.*' => ['integer', Rule::exists(Nationality::class, 'id')],
            'config.profiles.*.isDefaultCriteria' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'quote_type_id.required' => 'Quote type is required.',
            'quote_type_id.integer' => 'Quote type must be a valid number.',
            'quote_type_id.exists' => 'The selected quote type does not exist.',

            'quote_type.required' => 'Quote type code is required.',
            'quote_type.enum' => 'The selected quote type is invalid.',

            'version.required' => 'Configuration version is required.',
            'version.integer' => 'Version must be a valid number.',
            'version.min' => 'Version must be at least 1.',

            'config.required' => 'Configuration data is required.',
            'config.array' => 'Configuration must be a valid structure.',

            'config.profiles.array' => 'Profiles must be a valid list.',
            'config.profiles.min' => 'At least one profile is required.',

            'config.profiles.*.array' => 'Each profile must be a valid structure.',

            'config.profiles.*.nationalityIds.array' => 'Nationality IDs must be a valid list.',
            'config.profiles.*.nationalityIds.*.integer' => 'Each nationality ID must be a valid number.',
            'config.profiles.*.nationalityIds.*.exists' => 'One or more selected nationalities do not exist.',

            'config.profiles.*.isDefaultCriteria.boolean' => 'Default criteria flag must be true or false.',
        ];
    }

    public function attributes(): array
    {
        return [
            'quote_type_id' => 'quote type',
            'quote_type' => 'quote type code',
            'config.profiles' => 'profiles',
            'config.profiles.*.nationalityIds' => 'nationalities',
            'config.profiles.*.isDefaultCriteria' => 'default criteria',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('config') && is_array($this->config)) {
            if (!isset($this->config['profiles']) && !empty($this->config)) {
                $this->merge([
                    'config' => ['profiles' => $this->config]
                ]);
            }
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('config.profiles')) {
                $this->validateDefaultCriteria($validator);
                $this->validateDuplicateNationalities($validator);
            }
        });
    }

    private function validateDefaultCriteria($validator): void
    {
        $profiles = $this->input('config.profiles', []);
        $defaultCount = 0;

        foreach ($profiles as $index => $profile) {
            if (isset($profile['isDefaultCriteria']) && $profile['isDefaultCriteria']) {
                $defaultCount++;
            }
        }

        if ($defaultCount > 1) {
            $validator->errors()->add(
                'config.profiles',
                'Only one profile can be set as default criteria.'
            );
        }
    }

    private function validateDuplicateNationalities($validator): void
    {
        $profiles = $this->input('config.profiles', []);
        $usedNationalities = [];

        foreach ($profiles as $profileIndex => $profile) {
            if (isset($profile['isDefaultCriteria']) && $profile['isDefaultCriteria']) {
                continue;
            }

            $nationalityIds = $profile['nationalityIds'] ?? [];

            foreach ($nationalityIds as $nationalityId) {
                if (in_array($nationalityId, $usedNationalities)) {
                    $validator->errors()->add(
                        "config.profiles.{$profileIndex}.nationalityIds",
                        'This nationality is already used in another profile.'
                    );
                } else {
                    $usedNationalities[] = $nationalityId;
                }
            }
        }
    }
}

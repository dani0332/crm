<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\QuoteTypeId;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PqaAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        /** @var mixed $flag */
        $flag = $this->input('reAssignPqaAdvisor');

        if ($flag === null && $this->has('reAssignPqaAdvisor')) {
            $this->merge([
                'reAssignPqaAdvisor' => $this->boolean('reAssignPqaAdvisor'),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quoteUUID' => ['required', 'string'],
            'quoteTypeId' => [
                'required',
                Rule::in([
                    QuoteTypeId::GroupMedical,
                    QuoteTypeId::Business,
                    QuoteTypeId::Corpline,
                    QuoteTypeId::Health,
                ]),
            ],
            'reAssignPqaAdvisor' => ['sometimes', 'boolean'],
        ];
    }
}

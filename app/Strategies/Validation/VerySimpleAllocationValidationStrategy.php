<?php

declare(strict_types=1);

namespace App\Strategies\Validation;

use App\Contracts\AllocationValidationStrategyInterface;
use App\Enums\QuoteTypes;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class VerySimpleAllocationValidationStrategy implements AllocationValidationStrategyInterface
{
    private readonly string $lobName;

    public function __construct(
        private readonly QuoteTypes $quoteType
    ) {
        $this->lobName = $this->quoteType->value;
    }

    public function getRules(): array
    {
        return [
            'advisor_ids' => ['required', 'array', 'min:1'],
            'advisor_ids.*' => ['integer', Rule::exists(User::class, 'id')],
        ];
    }

    public function getMessages(): array
    {
        return [
            'advisor_ids.required' => "Please select at least one advisor for {$this->lobName}.",
            'advisor_ids.array' => 'Advisor selection must be a valid array.',
            'advisor_ids.min' => "Please select at least one advisor for {$this->lobName}.",
            'advisor_ids.*.integer' => 'Invalid advisor selected.',
            'advisor_ids.*.exists' => 'One or more selected advisors do not exist.',
        ];
    }

    public function validate(Validator $validator, array $data): void
    {
        // Check for at least one advisor
        if (empty($data['advisor_ids']) || ! is_array($data['advisor_ids'])) {
            $validator->errors()->add('configuration', "Please select at least one advisor for {$this->lobName} to save the allocation configuration.");

            return;
        }

        // Validate that all advisor IDs are valid
        if (count($data['advisor_ids']) === 0) {
            $validator->errors()->add('advisor_ids', "Please select at least one advisor for {$this->lobName}.");
        }
    }

    public function getValidatedDefaults(): array
    {
        return [
            'advisor_ids' => [],
        ];
    }
}

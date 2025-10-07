<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\QuoteTypes;
use App\Factories\AllocationValidationStrategyFactory;
use App\Models\Allocation\AllocationConfiguration;
use App\Models\QuoteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AllocationConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function getQuoteType(): QuoteTypes
    {
        return QuoteTypes::from($this->quote_type);
    }

    public function rules(): array
    {
        $baseRules = [
            'quote_type_id' => ['required', 'integer', Rule::exists(QuoteType::class, 'id')],
            'quote_type' => ['required', Rule::enum(QuoteTypes::class)],
        ];

        // Get LOB-specific rules from strategy
        if ($this->quote_type) {
            try {
                $quoteType = QuoteTypes::from($this->quote_type);
                $strategy = AllocationValidationStrategyFactory::create($quoteType);
                $lobRules = $strategy->getRules();
                $baseRules = array_merge($baseRules, $lobRules);
            } catch (\Exception $e) {
                // If strategy not found, return base rules only
            }
        }

        // Add uniqueness constraint
        if ($this->route('allocationConfiguration')) {
            $baseRules['quote_type'] = [
                'required',
                Rule::enum(QuoteTypes::class),
                Rule::unique(AllocationConfiguration::class)
                    ->ignore($this->route('allocationConfiguration'))
                    ->where(function ($query) {
                        return $query->where('quote_type_id', $this->quote_type_id);
                    }),
            ];
        } else {
            $baseRules['quote_type'] = [
                'required',
                Rule::enum(QuoteTypes::class),
                Rule::unique(AllocationConfiguration::class)
                    ->where(function ($query) {
                        return $query->where('quote_type_id', $this->quote_type_id);
                    }),
            ];
        }

        return $baseRules;
    }

    public function messages(): array
    {
        $baseMessages = [
            'quote_type_id.required' => 'Quote type ID is required.',
            'quote_type_id.integer' => 'Quote type ID must be a valid integer.',
            'quote_type.required' => 'Quote type is required.',
            'quote_type.in' => 'Selected quote type is invalid.',
            'quote_type.unique' => 'Configuration for this quote type already exists.',
        ];

        // Get LOB-specific messages from strategy
        if ($this->quote_type) {
            try {
                $quoteType = QuoteTypes::from($this->quote_type);
                $strategy = AllocationValidationStrategyFactory::create($quoteType);
                $lobMessages = $strategy->getMessages();
                $baseMessages = array_merge($baseMessages, $lobMessages);
            } catch (\Exception $e) {
                // If strategy not found, return base messages only
            }
        }

        return $baseMessages;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->quote_type) {
                return;
            }

            try {
                $quoteType = QuoteTypes::from($this->quote_type);
                $strategy = AllocationValidationStrategyFactory::create($quoteType);
                $strategy->validate($validator, $this->all());
            } catch (\Exception $e) {
                $validator->errors()->add('quote_type', 'Invalid quote type or validation strategy not found.');
            }
        });
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Set defaults based on quote type strategy
        if ($this->quote_type) {
            try {
                $quoteType = QuoteTypes::from($this->quote_type);
                $strategy = AllocationValidationStrategyFactory::create($quoteType);
                $defaults = $strategy->getValidatedDefaults();

                foreach ($defaults as $field => $defaultValue) {
                    $validated[$field] = $validated[$field] ?? $defaultValue;
                }
            } catch (\Exception $e) {
                // If strategy not found, continue without defaults
            }
        }

        return $validated;
    }
}

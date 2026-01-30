<?php

declare(strict_types=1);

namespace App\Services\CQF;

use App\Services\CQF\Contracts\CQFValidationInterface;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

/**
 * Base CQF validation: common rules required by both Motor and Non-motor FRs.
 * LOB-specific validation should be composed or piped after this.
 */
class BaseCQFValidationService implements CQFValidationInterface
{
    /**
     * Base validation rules (common to Motor and Non-motor).
     *
     * @return array<string, array<int, string>>
     */
    protected function getBaseRules(): array
    {
        return [
            'policy_number' => ['required'],
            'policy_expiry_date' => ['required', 'date'],
            'first_name' => ['required'],
            'email' => ['required', 'email'],
            'mobile_no' => ['required'],
        ];
    }

    public function validateQuote(Model $quote): array
    {
        $rules = $this->getBaseRules();
        $validator = Validator::make($quote->toArray(), $rules, $this->getValidationMessages());

        $errors = [];

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $field => $messages) {
                $errors[$field] = $messages[0];
            }
        }

        if (! empty($errors)) {
            LoggerService::info(self::class.' - Quote validation failed', [
                'errors' => $errors,
                'quote_uuid' => $quote->uuid ?? null,
            ]);

            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        return [
            'success' => true,
            'errors' => [],
        ];
    }

    /**
     * Default duplicate check: subclasses (e.g. Car, Non-motor) override with LOB-specific logic.
     */
    public function isDuplicateQuote(Model $quote): bool
    {
        return false;
    }

    public function getValidationMessages(): array
    {
        return [
            'policy_number.required' => 'Policy number is required.',
            'policy_expiry_date.required' => 'Policy expiry date is required.',
            'policy_expiry_date.date' => 'Policy expiry date must be a valid date.',
            'first_name.required' => 'Customer name is required.',
            'email.required' => 'Customer email is required.',
            'email.email' => 'Customer email must be a valid email address.',
            'mobile_no.required' => 'Customer mobile is required.',
        ];
    }
}

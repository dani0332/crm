<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\Pipes;

use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\InsuranceProvider;
use App\Models\Nationality;
use App\Models\PersonalQuote;
use App\Models\RenewalBatch;
use App\Repositories\LookupRepository;
use App\Services\CQF\NonMotor\BaseCQFQuoteMappingService;
use App\Services\CQF\NonMotor\CQFRenewalContext;
use Closure;

/**
 * Validates that all foreign key references required for the renewal PersonalQuote
 * row exist in the database before the storage step. Prevents integrity constraint
 * violations (e.g. missing customer_id, nationality_id).
 */
class ForeignKeyValidationPipe
{
    public function handle(CQFRenewalContext $context, Closure $next): CQFRenewalContext
    {
        $quote = $context->quote;

        if ($quote instanceof PersonalQuote) {
            $errors = $this->validateForeignKeys($quote);
        } elseif ($quote instanceof CarQuote) {
            $errors = $this->validateCarQuoteForeignKeys($quote);
        } else {
            return $next($context);
        }

        if (! empty($errors)) {
            return $context->fail($errors);
        }

        return $next($context);
    }

    /**
     * Validate FK references that will be written by BaseCQFQuoteMappingService::buildBaseQuoteDataFromPersonalQuote.
     *
     * @return array<string, string>
     */
    protected function validateForeignKeys(PersonalQuote $quote): array
    {
        $errors = [];

        // Helper for checking existence
        $checkExists = function ($model, $id) {
            return $model::where('id', $id)->exists();
        };

        // Handle required FKs
        $this->validateRequiredFk(
            $quote,
            'customer_id',
            Customer::class,
            'Customer id is required for renewal quote.',
            fn ($id) => "Customer with id {$id} does not exist.",
            $errors,
            $checkExists
        );

        // Handle optional FKs in a loop
        $optionalFks = [
            'nationality_id' => [
                'model' => Nationality::class,
                'not_found_msg' => fn ($id) => "Nationality with id {$id} does not exist.",
            ],
            'currently_insured_with_id' => [
                'model' => InsuranceProvider::class,
                'not_found_msg' => fn ($id) => "Currently insured with (insurance provider) id {$id} does not exist.",
            ],
        ];

        foreach ($optionalFks as $field => $meta) {
            $id = $quote->{$field};
            if ($id !== null && ! $checkExists($meta['model'], $id)) {
                $errors[$field] = ($meta['not_found_msg'])($id);
            }
        }

        // Transaction type lookup by id if set
        if ($quote->transaction_type_id !== null &&
            ! LookupRepository::where('id', $quote->transaction_type_id)->exists()) {
            $errors['transaction_type_id'] = "Transaction type with id {$quote->transaction_type_id} does not exist.";
        }

        // renewal_batch_id (derived)
        $renewalBatchId = BaseCQFQuoteMappingService::getRenewalBatchIdForDate($quote->policy_expiry_date);
        if ($renewalBatchId !== null && ! RenewalBatch::where('id', $renewalBatchId)->exists()) {
            $errors['renewal_batch_id'] = "Renewal batch with id {$renewalBatchId} does not exist.";
        }

        return $errors;
    }

    /**
     * Validate FK references for CarQuote-based Bike renewals (mapRenewalQuoteFromCarQuote fields).
     *
     * @return array<string, string>
     */
    protected function validateCarQuoteForeignKeys(CarQuote $quote): array
    {
        $errors = [];

        $customerId = $quote->getAttribute('customer_id');
        if ($customerId === null) {
            $errors['customer_id'] = 'Customer id is required for renewal quote.';
        } elseif (! Customer::where('id', $customerId)->exists()) {
            $errors['customer_id'] = "Customer with id {$customerId} does not exist.";
        }

        // Transaction type lookup by id if set
        if ($quote->transaction_type_id !== null &&
            ! LookupRepository::where('id', $quote->transaction_type_id)->exists()) {
            $errors['transaction_type_id'] = "Transaction type with id {$quote->transaction_type_id} does not exist.";
        }

        return $errors;
    }

    /**
     * Validate a required foreign key field and populate $errors if needed.
     */
    private function validateRequiredFk(
        PersonalQuote $quote,
        string $field,
        string $model,
        string $requiredMsg,
        callable $notFoundMsg,
        array &$errors,
        callable $checkExists
    ): void {
        $id = $quote->{$field};
        if ($id === null) {
            $errors[$field] = $requiredMsg;
        } elseif (! $checkExists($model, $id)) {
            $errors[$field] = $notFoundMsg($id);
        }
    }
}

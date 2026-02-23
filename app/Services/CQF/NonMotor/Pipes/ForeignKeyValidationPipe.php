<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\Pipes;

use App\Enums\LookupsEnum;
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
 * violations (e.g. missing insurance_provider_id, customer_id, nationality_id).
 */
class ForeignKeyValidationPipe
{
    public function handle(CQFRenewalContext $context, Closure $next): CQFRenewalContext
    {
        if (! $context->quote instanceof PersonalQuote) {
            return $next($context);
        }

        $quote = $context->quote;
        $errors = $this->validateForeignKeys($quote);

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

        if ($quote->customer_id !== null) {
            if (! Customer::where('id', $quote->customer_id)->exists()) {
                $errors['customer_id'] = "Customer with id {$quote->customer_id} does not exist.";
            }
        } else {
            $errors['customer_id'] = 'Customer id is required for renewal quote.';
        }

        if ($quote->nationality_id !== null) {
            if (! Nationality::where('id', $quote->nationality_id)->exists()) {
                $errors['nationality_id'] = "Nationality with id {$quote->nationality_id} does not exist.";
            }
        }

        if ($quote->insurance_provider_id !== null) {
            if (! InsuranceProvider::where('id', $quote->insurance_provider_id)->exists()) {
                $errors['insurance_provider_id'] = "Insurance provider with id {$quote->insurance_provider_id} does not exist.";
            }
        } else {
            $errors['insurance_provider_id'] = 'Insurance provider id is required for renewal quote.';
        }

        if ($quote->currently_insured_with_id !== null) {
            if (! InsuranceProvider::where('id', $quote->currently_insured_with_id)->exists()) {
                $errors['currently_insured_with_id'] = "Currently insured with (insurance provider) id {$quote->currently_insured_with_id} does not exist.";
            }
        }

        $transactionTypeLookup = LookupRepository::where('key', LookupsEnum::TRANSACTION_TYPES)
            ->where('code', LookupsEnum::EXT_CUSTOMER_RENWAL)
            ->first();

        if ($quote->transaction_type_id !== null) {
            $transactionTypeLookup = LookupRepository::where('id', $quote->transaction_type_id)->first();
            if ($transactionTypeLookup === null) {
                $errors['transaction_type_id'] = "Transaction type with id {$quote->transaction_type_id} does not exist.";
            }
        }

        $renewalBatchId = BaseCQFQuoteMappingService::getRenewalBatchIdForDate($quote->policy_expiry_date);
        if ($renewalBatchId !== null && ! RenewalBatch::where('id', $renewalBatchId)->exists()) {
            $errors['renewal_batch_id'] = "Renewal batch with id {$renewalBatchId} does not exist.";
        }

        return $errors;
    }
}

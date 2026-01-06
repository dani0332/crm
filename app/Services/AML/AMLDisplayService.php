<?php

namespace App\Services\AML;

use App\Enums\AMLDecisionStatusEnum;
use App\Enums\CustomerTypeEnum;
use App\Enums\quoteStatusCode;
use App\Models\AML;
use App\Models\Insured;
use App\Services\AML\DTOs\AMLPageData;
use App\Traits\GenericQueriesAllLobs;

/**
 * Service for preparing AML data for display
 * Handles all business logic for the AML show page
 */
class AMLDisplayService
{
    use GenericQueriesAllLobs;

    public function __construct(
        private readonly AMLResultsProcessor $resultsProcessor
    ) {}

    /**
     * Prepare AML data for display
     */
    public function prepareShowData(
        AML $aml,
        ?int $insuredId = null,
        ?int $customerId = null
    ): AMLPageData {
        // Eager load relationships to avoid N+1
        $aml->load('quotetype');

        // Add quote type text to AML model
        $aml->quote_type_text = $aml->quotetype->text;

        // Process AML results through dedicated processor
        $processedResults = $this->resultsProcessor->process($aml);

        // Get quote object using trait method
        $quoteObject = $this->getQuoteObject(
            $aml->quotetype->code,
            $aml->quote_request_id
        );

        // Get insured with KYC if ID provided
        $insured = $this->getInsuredWithKyc($insuredId);

        // Prepare enum arrays
        $enums = $this->prepareEnums();

        return new AMLPageData([
            'aml' => $aml,
            'amlResults' => $processedResults,
            'quoteObject' => $quoteObject,
            'insured' => $insured,
            'customerId' => $customerId,
            ...$enums,
        ]);
    }

    /**
     * Get insured with KYC relationship
     */
    private function getInsuredWithKyc(?int $insuredId): ?Insured
    {
        if (! $insuredId) {
            return null;
        }

        return Insured::with('insuredKyc')->find($insuredId);
    }

    /**
     * Prepare enum arrays for view
     */
    private function prepareEnums(): array
    {
        return [
            'quoteStatusCode' => quoteStatusCode::asArray(),
            'amlDecisionStatusCode' => AMLDecisionStatusEnum::asArray(),
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
        ];
    }
}

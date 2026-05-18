<?php

namespace App\Services\AML;

use App\Enums\AMLDecisionStatusEnum;
use App\Enums\CustomerTypeEnum;
use App\Enums\quoteStatusCode;
use App\Models\AML;
use App\Traits\GenericQueriesAllLobs;

class AMLDisplayService
{
    use GenericQueriesAllLobs;

    public function __construct(
        private readonly AMLResultsProcessor $resultsProcessor,
        private readonly AMLInsuredService $insuredService
    ) {}

    public function prepareShowData(AML $aml, mixed $insuredId = null, mixed $customerId = null): array
    {
        // Eager load relationships to avoid N+1
        $aml->load('quotetype');

        $aml->quote_type_text = $aml->quotetype->text;
        $processedResults = $this->resultsProcessor->process($aml);

        $quoteObject = $this->getQuoteObject(
            $aml->quotetype->code,
            $aml->quote_request_id
        );

        $insured = $this->insuredService->getInsuredWithKyc($insuredId);
        $enums = $this->prepareEnums();

        return [
            'aml' => $aml,
            'amlResults' => $processedResults,
            'quoteObject' => $quoteObject,
            'insured' => $insured,
            'customerId' => $customerId,
            ...$enums,
        ];
    }

    private function prepareEnums(): array
    {
        return [
            'quoteStatusCode' => quoteStatusCode::asArray(),
            'amlDecisionStatusCode' => AMLDecisionStatusEnum::asArray(),
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
        ];
    }
}

<?php

namespace App\Repositories;

use App\Enums\DocumentTypeCode;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteTypes;
use App\Models\DocumentType;
use App\Services\ActivitiesService;

class DocumentTypeRepository extends BaseRepository
{
    public function model()
    {
        return DocumentType::class;
    }

    public function fetchSendPolicyDocumentCodes($quoteType)
    {
        $documentTypeCodes = DocumentType::requiredForSendPolicy()->where('quote_type_id', app(ActivitiesService::class)->getQuoteTypeId($quoteType))->pluck('code')->toArray();
        if ($quoteType == QuoteTypes::GROUP_MEDICAL->value) {
            $documentTypeCodes = array_merge($documentTypeCodes, [DocumentTypeCode::Receipt_BUSINESS, DocumentTypeCode::NETWORK_LIST_BUSINESS]);
        }

        return $documentTypeCodes;
    }

    public function fetchTaxDocumentsCode($quoteType)
    {
        return DocumentType::taxDocument()->where('quote_type_id', app(ActivitiesService::class)->getQuoteTypeId($quoteType))->pluck('code')->toArray();
    }

    public function fetchQuoteDocumentsSentToCustomerCode($quoteType, $quote)
    {
        $documentTypes = DocumentType::sendToCustomer()
            ->where('quote_type_id', app(ActivitiesService::class)->getQuoteTypeId($quoteType));

        // If the quote is a business quote and the business type is car fleet, return all document types.
        if ($quoteType === QuoteTypes::BUSINESS->value
            && ($quote->business_type_of_insurance_id === quoteBusinessTypeCode::getId(quoteBusinessTypeCode::carFleet) || ($quote->business_type_of_insurance_id === quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical)))) {
            return $documentTypes->pluck('code')->toArray();
        }

        // If the quote is a business quote, exclude the tax invoice document type.
        $documentTypes->where('code', '!=', DocumentTypeCode::TI);

        return $documentTypes->pluck('code')->toArray();
    }

}

<?php

namespace App\Repositories;

use App\Enums\DocumentTypeCode;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteTypes;
use App\Models\DocumentType;
use App\Models\KycLog;
use App\Services\ActivitiesService;

class DocumentTypeRepository extends BaseRepository
{
    public function model()
    {
        return DocumentType::class;
    }

    public function fetchSendPolicyDocumentCodesOLD($quoteType)
    {
        $documentTypeCodes = DocumentType::requiredForSendPolicy()->where('quote_type_id', app(ActivitiesService::class)->getQuoteTypeId($quoteType))->pluck('code')->toArray();
        if ($quoteType == QuoteTypes::GROUP_MEDICAL->value) {
            $documentTypeCodes = array_merge($documentTypeCodes, [DocumentTypeCode::Receipt_BUSINESS, DocumentTypeCode::NETWORK_LIST_BUSINESS]);
        }

        return $documentTypeCodes;
    }

    public function fetchSendPolicyDocumentCodes($quoteType, $quote)
    {
        $documentTypeCodes = DocumentType::requiredForSendPolicy()->where('quote_type_id', app(ActivitiesService::class)->getQuoteTypeId($quoteType));
        if ($quoteType == QuoteTypes::GROUP_MEDICAL->value || $quoteType == QuoteTypes::CORPLINE->value) {
            $latestKycLog = KycLog::withTrashed()->where('quote_request_id', $quote->id)->latest()->first();
            $businessTypeOfInsurance = $quote->business_type_of_insurance_id;
            $businessTypeOfCustomer = $latestKycLog?->search_type;
            $documentTypeCodes->getBusinessDocument($businessTypeOfInsurance, $businessTypeOfCustomer);
        }

        // dd($documentTypeCodes->pluck('code')->toArray());
        return $documentTypeCodes->pluck('code')->toArray();
    }

    public function fetchTaxDocumentsCode($quoteType, $quote)
    {
        $documentTypeCodes= DocumentType::taxDocument()->where('quote_type_id', app(ActivitiesService::class)->getQuoteTypeId($quoteType));
        if ($quoteType == QuoteTypes::GROUP_MEDICAL->value || $quoteType == QuoteTypes::CORPLINE->value) {
            $latestKycLog = KycLog::withTrashed()->where('quote_request_id', $quote->id)->latest()->first();
            $businessTypeOfInsurance = $quote->business_type_of_insurance_id;
            $businessTypeOfCustomer = $latestKycLog?->search_type;
            $documentTypeCodes->getBusinessDocument($businessTypeOfInsurance, $businessTypeOfCustomer);
        }
        return $documentTypeCodes->pluck('code')->toArray();
    }

    public function fetchQuoteDocumentsSentToCustomerCode($quoteType, $quote)
    {
        $documentTypes = DocumentType::sendToCustomer()
            ->where('quote_type_id', app(ActivitiesService::class)->getQuoteTypeId($quoteType));

        // If the quote is a business quote and the business type is car fleet, return all document types.
        if ($quoteType === QuoteTypes::BUSINESS->value) {
            $latestKycLog = KycLog::withTrashed()->where('quote_request_id', $quote->id)->latest()->first();
            $documentTypes->when($quote->business_type_of_insurance_id, function ($query) use ($quote) {
                return $query->byBusinessTypeOfInsurance($quote->business_type_of_insurance_id);
            })->when($latestKycLog?->search_type, function ($query) use ($latestKycLog) {
                return $query->byBusinessTypeOfCustomer($latestKycLog?->search_type);
            });
            if (($quote->business_type_of_insurance_id === quoteBusinessTypeCode::getId(quoteBusinessTypeCode::carFleet) || ($quote->business_type_of_insurance_id === quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical)))) {
                return $documentTypes->pluck('code')->toArray();
            }
        }

        // If the quote is a business quote, exclude the tax invoice document type.
        $documentTypes->where('code', '!=', DocumentTypeCode::TI);

        return $documentTypes->pluck('code')->toArray();
    }

}

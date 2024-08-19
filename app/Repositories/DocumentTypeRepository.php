<?php

namespace App\Repositories;

use App\Enums\DocumentTypeCode;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteTypes;
use App\Models\BusinessInsuranceType;
use App\Models\DocumentType;
use App\Models\KycLog;
use App\Services\ActivitiesService;

class DocumentTypeRepository extends BaseRepository
{
    public function model()
    {
        return DocumentType::class;
    }

    /**
     * Fetches document type codes required for sending policy documents based on the quote's typex.
     * including handling special cases for business quotes like group medical, business, and corpline insurance types.
     *
     * @return array
     */
    public function fetchSendPolicyDocumentCodes($quoteType, $quote)
    {
        // Fetch document type codes marked as required for policy sending, filtered by quote type.
        $documentTypeCodes = DocumentType::requiredForSendPolicy()->where('quote_type_id', app(ActivitiesService::class)->getQuoteTypeId($quoteType));

        // Handle special document requirements for business insurance types.
        if (in_array($quoteType, [QuoteTypes::GROUP_MEDICAL->value, QuoteTypes::BUSINESS->value, QuoteTypes::CORPLINE->value])) {
            // Retrieve the latest KYC log for additional business/customer details.
            $latestKycLog = KycLog::withTrashed()->where('quote_request_id', $quote->id)->latest()->first();
            $businessTypeOfInsurance = $quote->business_type_of_insurance_id;
            $businessTypeOfCustomer = $latestKycLog?->search_type;

            // Get the insurer name based on the business type of insurance and get business documents
            $businessInsurerName = $this->fetchBusinessInsurerName($businessTypeOfInsurance);
            $documentTypeCodes->getBusinessDocument($businessTypeOfInsurance, $businessTypeOfCustomer, $businessInsurerName);
        }

        return $documentTypeCodes->pluck('code')->toArray();
    }

    /**
     * Fetches the document type codes for tax-related documents based on the quote's type and details.
     *
     * @return array
     */
    public function fetchTaxDocumentsCode($quoteType, $quote)
    {
        // Fetch tax document types based on the quote type ID.
        $documentTypeCodes = DocumentType::taxDocument()->where('quote_type_id', app(ActivitiesService::class)->getQuoteTypeId($quoteType));

        // Check if the quote type is Group medical or corpline .
        if (in_array($quoteType, [QuoteTypes::GROUP_MEDICAL->value, QuoteTypes::BUSINESS->value, QuoteTypes::CORPLINE->value])) {
            $latestKycLog = KycLog::withTrashed()->where('quote_request_id', $quote->id)->latest()->first();

            $businessTypeOfInsurance = $quote->business_type_of_insurance_id;
            $businessTypeOfCustomer = $latestKycLog?->search_type;

            // Get business insurer name and get relevant documents
            $businessInsurerName = $this->fetchBusinessInsurerName($businessTypeOfInsurance);
            $documentTypeCodes->getBusinessDocument($businessTypeOfInsurance, $businessTypeOfCustomer, $businessInsurerName);
        }

        // Return the unique document type codes as an array.
        return $documentTypeCodes->select('code')->distinct()->pluck('code')->toArray();
    }

    /**
     * Fetches document type codes for documents sent to customers based on the quote's type and specifics.
     * Handles special cases for business quotes, including car fleet and group medical insurance types.
     *
     * @return array
     */
    public function fetchQuoteDocumentsSentToCustomerCode($quoteType, $quote)
    {
        $documentTypes = DocumentType::sendToCustomer()
            ->where('quote_type_id', app(ActivitiesService::class)->getQuoteTypeId($quoteType));

        // Return all document types if the quote's insurance type is either car fleet or group medical health.
        if (in_array($quote->business_type_of_insurance_id, [quoteBusinessTypeCode::getId(quoteBusinessTypeCode::carFleet), quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical)])) {
            return $documentTypes->pluck('code')->toArray();
        }

        // Filter document types based on business insurance type and KYC search results.
        if ($quoteType === QuoteTypes::BUSINESS->value) {
            $latestKycLog = KycLog::withTrashed()->where('quote_request_id', $quote->id)->latest()->first();
            $documentTypes->when($quote->business_type_of_insurance_id, function ($query) use ($quote) {
                return $query->byBusinessTypeOfInsurance($quote->business_type_of_insurance_id);
            })->when($latestKycLog?->search_type, function ($query) use ($latestKycLog, $quote) {
                $businessInsurerName = $this->fetchBusinessInsurerName($quote->business_type_of_insurance_id);

                return $query->byBusinessTypeOfCustomer($latestKycLog?->search_type, $businessInsurerName);
            });
        }

        return $documentTypes->pluck('code')->toArray();
    }

    /**
     * Retrieves the insurer name for a given business insurance type ID. If it value is group medical, car fleet
     * it returns a constant representing the company's business type of customer. Otherwise, it returns false
     *
     * @return mixed
     */
    public function fetchBusinessInsurerName($id)
    {
        $businessInsuranceType = BusinessInsuranceType::find($id);
        if ($businessInsuranceType && in_array($businessInsuranceType->code, [quoteBusinessTypeCode::groupMedical, quoteBusinessTypeCode::carFleet])) {
            return DocumentTypeCode::COMPANY_BUSINESS_TYPE_OF_CUSTOMER;
        }

        return false;
    }
}

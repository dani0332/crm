<?php

namespace App\Services;

use App\Enums\DocumentTypeCode;
use App\Enums\InsuranceProviderEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Jobs\PartnerPolicyDocumentJob;
use App\Models\Partner;
use App\Models\Payment;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;

class PartnerService
{
    use GenericQueriesAllLobs;

    private object $insuranceProvider;
    private ?string $partnerEmail = '';

    public function __construct(
        private readonly QuoteDocumentService $quoteDocumentService
    ) {}

    public function isPartnerActive($partnerCode, $insuranceProviderId)
    {
        $partner = Partner::with(['partnerPlans' => function ($query) use ($insuranceProviderId) {
            return $query->where('provider_id', $insuranceProviderId);
        }])->where('code', $partnerCode)->whereNotNull('email')->where('is_active', true)->first();

        return $partner ?? false;
    }

    public function validatePartnerQuote($uuid, $quoteType)
    {
        $quote = $this->getQuoteObject($quoteType, $uuid);

        if (! $quote) {
            LoggerService::info('PartnerService - Quote not found', extra: ['uuid' => $uuid, 'quoteType' => $quoteType]);

            return false;
        }

        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::PARTNER_POLICY_DOCUMENT);

        $payment = Payment::where('code', $quote->code)->first();
        if (! $payment) {
            LoggerService::info('PartnerService - Payment not found');

            return false;
        }

        $insuranceProvider = getInsuranceProvider($payment, $quoteType, $quote);
        if (! $insuranceProvider) {
            LoggerService::info('PartnerService - Insurance provider not found');

            return false;
        }

        $this->insuranceProvider = $insuranceProvider;
        $partner = $this->isPartnerActive($quote->source, $insuranceProvider->id);

        if (! $partner) {
            LoggerService::info('PartnerService - Partner not active', extra: ['partner' => $quote->source]);

            return false;
        }

        $this->partnerEmail = $partner->email;

        return $quote;
    }

    public function sendPolicyDocumentsToPartner($uuid, $quoteType)
    {
        $quote = $this->validatePartnerQuote($uuid, $quoteType);

        if (! $quote) {
            return;
        }

        $providerDocuments = [];

        // will update as new provider added
        if ($this->insuranceProvider->code == InsuranceProviderEnum::AXA->value) {
            $providerDocuments = [DocumentTypeCode::TI, DocumentTypeCode::CTIRBB, DocumentTypeCode::CPC, DocumentTypeCode::CPS];
        }

        // If no documents are required for this provider, return early
        if (empty($providerDocuments)) {
            LoggerService::info('PartnerService - No documents required for this provider');

            return;
        }

        $documents = $this->getProviderPolicyDocuments($quote, $quoteType, $providerDocuments);

        $filterDocuments = collect($documents)->filter(function ($document) use ($providerDocuments) {
            return in_array($document['document_type_code'], $providerDocuments);
        })->toArray();

        // Check if any required document is missing
        $foundDocumentCodes = collect($filterDocuments)->pluck('document_type_code')->toArray();
        $missingDocuments = array_diff($providerDocuments, $foundDocumentCodes);

        if (! empty($missingDocuments)) {
            LoggerService::info('PartnerService - Missing required documents', extra: ['missing_documents' => $missingDocuments]);

            return false;
        }

        $emailPayload = $this->partnerPolicyDocumentsEmailPayload($filterDocuments);

        PartnerPolicyDocumentJob::dispatch($emailPayload, $this->partnerEmail);
    }

    public function partnerPolicyDocumentsEmailPayload($documents)
    {
        $emailPayload = [];
        foreach ($documents as $document) {
            $docUrl = $document['watermarked_doc_url'] ?? $document['doc_url'];
            $emailPayload[$document['document_type_code']] = $this->quoteDocumentService->getDocumentUrl($docUrl, 'azureIMPrivate') ?? '';
            $emailPayload['EXT_'.$document['document_type_code']] = $this->quoteDocumentService->getDocumentExtension($docUrl) ?? '';
        }

        return $emailPayload;
    }

    public function getProviderPolicyDocuments($quote, $quoteType, $providerDocuments)
    {
        return $this->quoteDocumentService->getQuoteDocuments($quoteType, $quote->id, $providerDocuments);
    }
}

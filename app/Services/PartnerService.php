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

    public function validatePartnerQuote($uuid, $quoteType): array|false
    {
        $quote = $this->getQuoteObject($quoteType, $uuid);

        if (! $quote) {
            LoggerService::info('PartnerService - Quote not found', extra: ['uuid' => $uuid, 'quoteType' => $quoteType]);

            return false;
        }

        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::PARTNER_POLICY_DOCUMENT);

        $payment = Payment::where('code', $quote->code)->first();
        $insuranceProvider = $payment ? getInsuranceProvider($payment, $quoteType, $quote) : null;
        $partner = $insuranceProvider ? $this->isPartnerActive($quote->source, $insuranceProvider->id) : null;

        if (! $payment || ! $insuranceProvider || ! $partner) {
            $this->logValidationFailure($payment, $insuranceProvider, $partner, $quote);

            return false;
        }

        return [
            'quote' => $quote,
            'insuranceProvider' => $insuranceProvider,
            'partnerEmail' => $partner->email,
        ];
    }

    private function logValidationFailure($payment, $insuranceProvider, $partner, $quote): void
    {
        if (! $payment) {
            LoggerService::info('PartnerService - Payment not found');
        } elseif (! $insuranceProvider) {
            LoggerService::info('PartnerService - Insurance provider not found');
        } elseif (! $partner) {
            LoggerService::info('PartnerService - Partner not active', extra: ['partner' => $quote->source]);
        }
    }

    public function sendPolicyDocumentsToPartner($uuid, $quoteType): void
    {
        $validation = $this->validatePartnerQuote($uuid, $quoteType);

        if (! $validation) {
            return;
        }

        $quote = $validation['quote'];
        $insuranceProvider = $validation['insuranceProvider'];
        $partnerEmail = $validation['partnerEmail'];

        $providerDocuments = [];

        // will update as new provider added
        if ($insuranceProvider->code == InsuranceProviderEnum::AXA->value) {
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

            return;
        }

        $emailPayload = $this->partnerPolicyDocumentsEmailPayload($filterDocuments);

        PartnerPolicyDocumentJob::dispatch($emailPayload, $partnerEmail);
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

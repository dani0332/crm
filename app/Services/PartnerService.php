<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\InsuranceProviderEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTagEnums;
use App\Jobs\PartnerPolicyDocumentJob;
use App\Models\InsurancePartner;
use App\Models\Payment;
use App\Models\QuoteTag;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;

class PartnerService
{
    use GenericQueriesAllLobs;

    public function __construct(
        private readonly QuoteDocumentService $quoteDocumentService
    ) {}

    public function isPartnerActive($partnerCode, $quoteTypeId, $providerId, $planId)
    {
        $partner = InsurancePartner::active()
            ->forCode($partnerCode)
            ->hasActiveProviderWithPlan($providerId, $quoteTypeId, $planId)
            ->first();

        return $partner ?? false;
    }

    public function validatePartnerQuote($uuid, $quoteType): array|false
    {
        $quote = $this->getQuoteObject($quoteType->value, $uuid);

        if (! $quote) {
            LoggerService::info('PartnerService - Quote not found', extra: ['uuid' => $uuid, 'quoteType' => $quoteType]);

            return false;
        }

        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::PARTNER_POLICY_DOCUMENT);

        $payment = Payment::where('code', $quote->code)->mainLeadPayment()->first();
        $insuranceProvider = $payment ? getInsuranceProvider($payment, $quoteType->value, $quote) : null;
        $partner = $insuranceProvider ? $this->isPartnerActive($quote->source, $quoteType->id(), $insuranceProvider->id, $payment?->plan_id) : null;

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
        $filteredDocuments = $this->resolveFilteredDocuments($quote, $quoteType, $validation['insuranceProvider']);

        if ($filteredDocuments === null) {
            return;
        }

        $alreadySent = QuoteTag::where([
            'quote_type_id' => $quoteType->id(),
            'quote_uuid' => $quote->uuid,
            'name' => QuoteTagEnums::PARTNER_POLICY_DOCUMENT_SENT,
            'value' => 1,
        ])->exists();

        if ($alreadySent) {
            LoggerService::info('PartnerService - Partner policy document already sent, skipping', extra: ['uuid' => $quote->uuid]);

            return;
        }

        PartnerPolicyDocumentJob::dispatch($filteredDocuments, $validation['partnerEmail'], $quote->uuid, $quoteType->id());
    }

    /**
     * @return array<int, array<string, mixed>>|null Returns the filtered documents, or null if dispatch should be aborted.
     */
    private function resolveFilteredDocuments($quote, $quoteType, $insuranceProvider): ?array
    {
        $providerDocumentKeys = [
            InsuranceProviderEnum::AXA->value => ApplicationStorageEnums::AXA_POLICY_MANDATORY_DOCUMENTS,
        ];

        $storageKey = $providerDocumentKeys[$insuranceProvider->code] ?? null;
        $providerDocuments = $storageKey
            ? json_decode(getAppStorageValueByKey($storageKey), true) ?? []
            : [];

        if (empty($providerDocuments)) {
            LoggerService::info('PartnerService - No documents required for this provider');

            return null;
        }

        $filteredDocuments = collect($this->getProviderPolicyDocuments($quote, $quoteType->value, $providerDocuments))
            ->filter(fn ($document) => in_array($document['document_type_code'], $providerDocuments))
            ->values()
            ->toArray();

        $missingDocuments = array_diff($providerDocuments, collect($filteredDocuments)->pluck('document_type_code')->toArray());

        if (! empty($missingDocuments)) {
            LoggerService::info('PartnerService - Missing required documents', extra: ['missing_documents' => $missingDocuments]);

            return null;
        }

        return $filteredDocuments;
    }

    public function getProviderPolicyDocuments($quote, $quoteType, $providerDocuments)
    {
        return $this->quoteDocumentService->getQuoteDocuments($quoteType, $quote->id, $providerDocuments);
    }
}

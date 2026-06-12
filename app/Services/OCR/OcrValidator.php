<?php

declare(strict_types=1);

namespace App\Services\OCR;

use App\Enums\ApplicationStorageEnums;
use App\Enums\InsuranceProviderEnum;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Database\Eloquent\Model;

trait OcrValidator
{
    private const COMMON_OCR_FIELDS = [
        'quote.price_with_vat',
        'quote.vat',
        'quote.price_vat_applicable',
        'payment.insurer_invoice_date',
        'payment.insurer_tax_number',
        'payment.tax_invoice_number',
        'quote.insurer_commmission_invoice_number',
        'quote.insurer_commission_invoice_number', // For send update logs for tax invoice raised by buyer
        'quote.policy_number',
        'quote.policy_start_date',
        'quote.policy_expiry_date',
        'quote.start_date', // For send update logs for CPD
        'quote.expiry_date', // For send update logs for CPD
    ];
    private const PROVIDERS_WITHOUT_COMMISSION_VAT = [
        InsuranceProviderEnum::AXA->value,  // GIG_INSURANCE
        InsuranceProviderEnum::MTL->value,   // METLIFE_INSURANCE
        InsuranceProviderEnum::CIG->value,   // CIGNA_INSURANCE
    ];

    // We might consider this to move to database
    private const PROVIDER_QUOTE_TYPE_MAPPING = [
        // Multi-LOB: CAR, HOME, GROUP_MEDICAL
        InsuranceProviderEnum::AXA->value => [QuoteTypes::CAR, QuoteTypes::HOME, QuoteTypes::GROUP_MEDICAL],
        InsuranceProviderEnum::OIC->value => [QuoteTypes::CAR, QuoteTypes::HOME, QuoteTypes::GROUP_MEDICAL],

        // Car-only
        InsuranceProviderEnum::QIC->value => [QuoteTypes::CAR],
        InsuranceProviderEnum::RSA->value => [QuoteTypes::CAR],
        InsuranceProviderEnum::TM->value => [QuoteTypes::CAR],
        InsuranceProviderEnum::AFNIC->value => [QuoteTypes::CAR],
        // Group Medical-only
        InsuranceProviderEnum::TE->value => [QuoteTypes::GROUP_MEDICAL],
        InsuranceProviderEnum::OI2->value => [QuoteTypes::GROUP_MEDICAL],
        InsuranceProviderEnum::NGI->value => [QuoteTypes::GROUP_MEDICAL, QuoteTypes::DEVICE],
        InsuranceProviderEnum::MTL->value => [QuoteTypes::GROUP_MEDICAL],
        InsuranceProviderEnum::DNIRC->value => [QuoteTypes::GROUP_MEDICAL],
        InsuranceProviderEnum::DIC->value => [QuoteTypes::GROUP_MEDICAL],
        InsuranceProviderEnum::CIG->value => [QuoteTypes::GROUP_MEDICAL],
        InsuranceProviderEnum::SI->value => [QuoteTypes::GROUP_MEDICAL],

        // Cyber-only
        InsuranceProviderEnum::AWNI->value => [QuoteTypes::CYBER],
        // Health only
        InsuranceProviderEnum::ADNIC->value => [QuoteTypes::HEALTH],
    ];
    private const QUOTE_TYPE_PROVIDER_SKIP_OCR = [
        QuoteTypes::SAVINGS,
    ];

    public function isSupportedProvider(QuoteTypes $quoteType, string $provider): bool
    {
        return in_array($quoteType, self::PROVIDER_QUOTE_TYPE_MAPPING[$provider] ?? [], true);
    }

    private function isFieldEnabled(string $provider, string $field): bool
    {
        $supportedFields = $this->getSupportedFields($provider);

        return in_array($field, $supportedFields, true);
    }

    private function getSupportedFields(string $provider): array
    {
        try {
            $fields = [...self::COMMON_OCR_FIELDS];

            if (! in_array($provider, self::PROVIDERS_WITHOUT_COMMISSION_VAT, true)) {
                $fields[] = 'quote.commission_vat_applicable'; // For send update logs for tax invoice raised by buyer
            }

            return $fields;
        } catch (Exception $e) {
            LoggerService::error(
                self::class.' - Exception occurred during getting supported fields - Provider: '.$provider,
                exception: $e
            );

            return [];
        }
    }

    public function isProviderEligibleForOcr(QuoteTypes $quoteType, Model $quote): bool
    {
        // Check if no provider required for this quote type
        if (in_array($quoteType, self::QUOTE_TYPE_PROVIDER_SKIP_OCR)) {
            return true;
        }

        $providerCode = $this->extractProviderCode($quote);

        if (! $providerCode) {
            LoggerService::info(
                'OCR Provider Eligibility - No provider code found',
                [
                    'quote_uuid' => $quote->uuid ?? 'N/A',
                    'quote_type' => $quoteType->value,
                ]
            );

            return false;
        }

        $isSupported = $this->isSupportedProvider($quoteType, $providerCode);

        LoggerService::info(
            'OCR Provider Eligibility Check',
            [
                'quote_uuid' => $quote->uuid ?? 'N/A',
                'quote_type' => $quoteType->value,
                'provider_code' => $providerCode,
                'is_eligible' => $isSupported,
            ]
        );

        return $isSupported;
    }

    /**
     * Determine whether the quote's selected plan qualifies for OCR processing.
     *
     * Plan validation only applies when the current quote type has entries in
     * `\App\Enums\OCRDocumentTypeEnum::getPlanValidation()` and the current
     * document type is mapped for that quote type.
     */
    public function isPlanEligibleForOcr(QuoteTypes $quoteType, OCRDocumentTypeEnum $docType, Model $quote): bool
    {
        $logContext = [
            'quote_uuid' => $quote->uuid ?? 'N/A',
            'quote_type' => $quoteType->value,
            'doc_type' => $docType->value,
        ];

        $quoteTypePlanValidationMapping = OCRDocumentTypeEnum::getPlanValidation()[$quoteType->value] ?? null;

        /* If quote type is not present in mapping >> no validation needed */
        if ($quoteTypePlanValidationMapping === null) {
            LoggerService::info('OCR Plan Eligibility - Accepted (no plan validation mapping for quote type)', $logContext);

            return true;
        }

        /* If doc_type is not present in mapping >> no validation needed */
        if (! in_array($docType->value, $quoteTypePlanValidationMapping, true)) {
            LoggerService::info('OCR Plan Eligibility - Accepted (no plan validation mapping for document type)', $logContext);

            return true;
        }

        $planCode = $this->extractPlanCode($quote);

        if (! $planCode) {
            LoggerService::info('OCR Plan Eligibility - Rejected (no plan selected in quote)', $logContext);

            return false;
        }

        $eligiblePlanCodesRaw = (string) getAppStorageValueByKey(
            ApplicationStorageEnums::OCR_SAVINGS_PASSPORT_ELIGIBLE_PLAN_CODES,
            default: '',
            useCache: true
        );

        $eligiblePlanCodes = array_values(array_filter(
            array_map('trim', explode(',', $eligiblePlanCodesRaw)),
            static fn (string $code): bool => $code !== ''
        ));

        if ($eligiblePlanCodes === []) {
            LoggerService::info('OCR Plan Eligibility - Rejected (no eligible plan codes configured)', array_merge($logContext, [
                'plan_code' => $planCode,
                'eligible_plan_codes' => [],
                'app_storage_key' => ApplicationStorageEnums::OCR_SAVINGS_PASSPORT_ELIGIBLE_PLAN_CODES,
            ]));

            return false;
        }

        $isEligible = in_array($planCode, $eligiblePlanCodes, true);

        LoggerService::info('OCR Plan Eligibility Check - '.($isEligible ? 'passed' : 'failed'), array_merge($logContext, [
            'plan_code' => $planCode,
            'eligible_plan_codes' => $eligiblePlanCodes,
            'is_eligible' => $isEligible,
        ]));

        return $isEligible;
    }

    private function extractPlanCode(Model $quote): ?string
    {
        if (! method_exists($quote, 'insuranceProviderPlan')) {
            return null;
        }

        try {
            $plan = $quote->insuranceProviderPlan()->select(['code'])->first();

            return $plan?->getAttribute('code');
        } catch (Exception $e) {
            LoggerService::error(self::class.' - Exception occurred during extracting plan code for OCR eligibility', exception: $e);

            return null;
        }
    }
}

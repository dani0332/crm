<?php

declare(strict_types=1);

namespace App\Services\OCR;

use App\Enums\InsuranceProviderEnum;
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
        // Group Medical-only
        InsuranceProviderEnum::TE->value => [QuoteTypes::GROUP_MEDICAL],
        InsuranceProviderEnum::OI2->value => [QuoteTypes::GROUP_MEDICAL],
        InsuranceProviderEnum::NGI->value => [QuoteTypes::GROUP_MEDICAL],
        InsuranceProviderEnum::MTL->value => [QuoteTypes::GROUP_MEDICAL],
        InsuranceProviderEnum::DNIRC->value => [QuoteTypes::GROUP_MEDICAL],
        InsuranceProviderEnum::DIC->value => [QuoteTypes::GROUP_MEDICAL],
        InsuranceProviderEnum::CIG->value => [QuoteTypes::GROUP_MEDICAL],
        InsuranceProviderEnum::SI->value => [QuoteTypes::GROUP_MEDICAL],
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
}

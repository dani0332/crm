<?php

namespace App\Services\OCR;

use App\Enums\InsurerProviderEnum;
use App\Enums\QuoteTypes;
use Illuminate\Database\Eloquent\Model;

trait OcrValidator
{
    private function isSupportedProvider(QuoteTypes $quoteType, string $provider): bool
    {
        return match ($provider) {
            // Car & Home & Group Medical
            InsurerProviderEnum::GIG_INSURANCE => in_array($quoteType, [
                QuoteTypes::CAR,
                QuoteTypes::HOME,
                QuoteTypes::GROUP_MEDICAL,
            ]),
            // Car & Home & Group Medical
            InsurerProviderEnum::SUKOON_OMAN_INSURANCE => in_array($quoteType, [
                QuoteTypes::CAR,
                QuoteTypes::HOME,
                QuoteTypes::GROUP_MEDICAL,
            ]),
            InsurerProviderEnum::QATAR_INSURANCE => $quoteType == QuoteTypes::CAR,
            InsurerProviderEnum::LIVANA_INSURANCE => $quoteType == QuoteTypes::CAR,
            InsurerProviderEnum::TOKIO_MARINE => $quoteType == QuoteTypes::CAR,

            // Group Medical
            InsurerProviderEnum::TAKAFUL_EMARAT_INSURANCE => $quoteType == QuoteTypes::GROUP_MEDICAL,
            InsurerProviderEnum::ORIENT_INSURANCE => $quoteType == QuoteTypes::GROUP_MEDICAL,
            InsurerProviderEnum::NATIONAL_GENERAL_INSURANCE => $quoteType == QuoteTypes::GROUP_MEDICAL,
            InsurerProviderEnum::METLIFE_INSURANCE => $quoteType == QuoteTypes::GROUP_MEDICAL,
            InsurerProviderEnum::DUBAI_NATIONAL_INSURANCE => $quoteType == QuoteTypes::GROUP_MEDICAL,
            InsurerProviderEnum::DUBAI_INSURANCE_COMPANY => $quoteType == QuoteTypes::GROUP_MEDICAL,
            InsurerProviderEnum::CIGNA_INSURANCE => $quoteType == QuoteTypes::GROUP_MEDICAL,
            default => false,
        };
    }

    private function isFieldEnabled(string $provider, string $field)
    {
        $supportedFields = $this->getSupportedFields($provider);

        return in_array($field, $supportedFields);
    }

    private function getSupportedFields(string $provider)
    {
        $commonFields = [
            'quote.price_with_vat',
            'quote.vat',
            'quote.price_vat_applicable',
            'payment.insurer_invoice_date',
            'payment.insurer_tax_number',
            'payment.tax_invoice_number',
            'quote.insurer_commmission_invoice_number',
            'quote.insurer_commission_invoice_number', // this is for send update logs for tax invoice raised by buyer
            'quote.commission_vat_applicable', // this is for send update logs for tax invoice raised by buyer
            'quote.policy_number',
            'quote.policy_start_date',
            'quote.policy_expiry_date',
            'quote.start_date', // this is for send update logs for CPD
            'quote.expiry_date' // this is for send update logs for CPD
        ];
        
        return match ($provider) {
            InsurerProviderEnum::GIG_INSURANCE => [
                ...$commonFields,
            ],
            InsurerProviderEnum::SUKOON_OMAN_INSURANCE => [
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsurerProviderEnum::QATAR_INSURANCE => [
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsurerProviderEnum::LIVANA_INSURANCE => [
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsurerProviderEnum::TOKIO_MARINE => [
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsurerProviderEnum::TAKAFUL_EMARAT_INSURANCE => [
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsurerProviderEnum::NATIONAL_GENERAL_INSURANCE => [
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsurerProviderEnum::METLIFE_INSURANCE => [
                ...$commonFields,
            ],
            InsurerProviderEnum::DUBAI_NATIONAL_INSURANCE => [
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsurerProviderEnum::DUBAI_INSURANCE_COMPANY => [
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsurerProviderEnum::CIGNA_INSURANCE => [
                ...$commonFields,
            ],
        };
    }

}

// InsurerProviderEnum::GIG_INSURANCE,
// InsurerProviderEnum::SUKOON_OMAN_INSURANCE,
// InsurerProviderEnum::QATAR_INSURANCE,
// InsurerProviderEnum::LIVANA_INSURANCE,
// InsurerProviderEnum::TOKIO_MARINE,

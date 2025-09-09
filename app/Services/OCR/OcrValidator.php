<?php

namespace App\Services\OCR;

use App\Enums\InsuranceProviderEnum;
use App\Enums\QuoteTypes;
use Illuminate\Database\Eloquent\Model;

trait OcrValidator
{
    public function isSupportedProvider(QuoteTypes $quoteType, string $provider): bool
    {
        return match ($provider) {
            // Car & Home & Group Medical
            InsuranceProviderEnum::AXA->value => in_array($quoteType, [    // GIG_INSURANCE
                QuoteTypes::CAR,
                QuoteTypes::HOME,
                QuoteTypes::GROUP_MEDICAL,
            ]),
            // Car & Home & Group Medical
            InsuranceProviderEnum::OIC->value => in_array($quoteType, [    // SUKOON_OMAN_INSURANCE
                QuoteTypes::CAR,
                QuoteTypes::HOME,
                QuoteTypes::GROUP_MEDICAL,
            ]),
            InsuranceProviderEnum::QIC->value => $quoteType == QuoteTypes::CAR,    // QATAR_INSURANCE
            InsuranceProviderEnum::RSA->value => $quoteType == QuoteTypes::CAR,    // LIVANA_INSURANCE
            InsuranceProviderEnum::TM->value => $quoteType == QuoteTypes::CAR,     // TOKIO_MARINE

            // Group Medical
            InsuranceProviderEnum::TE->value => $quoteType == QuoteTypes::GROUP_MEDICAL,    // TAKAFUL_EMARAT_INSURANCE
            InsuranceProviderEnum::OI2->value => $quoteType == QuoteTypes::GROUP_MEDICAL,   // ORIENT_INSURANCE
            InsuranceProviderEnum::NGI->value => $quoteType == QuoteTypes::GROUP_MEDICAL,   // NATIONAL_GENERAL_INSURANCE
            InsuranceProviderEnum::MTL->value => $quoteType == QuoteTypes::GROUP_MEDICAL,   // METLIFE_INSURANCE
            InsuranceProviderEnum::DNIRC->value => $quoteType == QuoteTypes::GROUP_MEDICAL, // DUBAI_NATIONAL_INSURANCE
            InsuranceProviderEnum::DIC->value => $quoteType == QuoteTypes::GROUP_MEDICAL,   // DUBAI_INSURANCE_COMPANY
            InsuranceProviderEnum::CIG->value => $quoteType == QuoteTypes::GROUP_MEDICAL,   // CIGNA_INSURANCE
            InsuranceProviderEnum::SI->value => $quoteType == QuoteTypes::GROUP_MEDICAL,    // SALAMA_INSURANCE
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
            'quote.expiry_date', // this is for send update logs for CPD
        ];

        return match ($provider) {
            InsuranceProviderEnum::AXA->value => [    // GIG_INSURANCE
                ...$commonFields,
            ],
            InsuranceProviderEnum::OIC->value => [    // SUKOON_OMAN_INSURANCE
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsuranceProviderEnum::QIC->value => [    // QATAR_INSURANCE
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsuranceProviderEnum::RSA->value => [    // LIVANA_INSURANCE
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsuranceProviderEnum::TM->value => [     // TOKIO_MARINE
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsuranceProviderEnum::TE->value => [     // TAKAFUL_EMARAT_INSURANCE
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsuranceProviderEnum::NGI->value => [    // NATIONAL_GENERAL_INSURANCE
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsuranceProviderEnum::MTL->value => [    // METLIFE_INSURANCE
                ...$commonFields,
            ],
            InsuranceProviderEnum::DNIRC->value => [  // DUBAI_NATIONAL_INSURANCE
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsuranceProviderEnum::DIC->value => [    // DUBAI_INSURANCE_COMPANY
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsuranceProviderEnum::CIG->value => [    // CIGNA_INSURANCE
                ...$commonFields,
            ],
            InsuranceProviderEnum::SI->value => [     // SALAMA_INSURANCE
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
            InsuranceProviderEnum::OI2->value => [    // ORIENT_INSURANCE
                ...$commonFields,
                'quote.commission_vat_applicable',
            ],
        };
    }

    public function isProviderEligibleForOcr(QuoteTypes $quoteType, Model $quote): bool
    {
        $providerCode = $this->extractProviderCode($quote);

        if (! $providerCode) {
            return false;
        }

        return $this->isSupportedProvider($quoteType, $providerCode);
    }
}

<?php

declare(strict_types=1);

namespace App\Support\AmlQuoteAutomation;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;

/**
 * Single source of truth for LOBs that may receive API-triggered AML automation
 * (OCR / IMCRM flows). Extend {@see allowed()} when adding new LOBs.
 */
final class AmlAutomatableLobRegistry
{
    /**
     * @return list<QuoteTypes>
     */
    public static function allowed(): array
    {
        return [
            QuoteTypes::SAVINGS,
        ];
    }

    public static function allows(QuoteTypes $quoteType): bool
    {
        foreach (self::allowed() as $allowed) {
            if ($allowed === $quoteType) {
                return true;
            }
        }

        return false;
    }

    /**
     * Savings + OIC: policy issuance API status is not expected before AML automation.
     */
    public static function skipsApiIssuanceStatusCheckForAutomatedAml(QuoteTypes $quoteType, TravelQuote|PersonalQuote $quoteRequest): bool
    {
        if ($quoteType === QuoteTypes::SAVINGS && $quoteRequest instanceof PersonalQuote) {
            // NOTE:: we cannot check provider here because the provider is attached to the quote after doc signing.
            return true;
        }

        return false;
        // NOTE:: need to discuss this we need to initiate AML automation for OIC after the documents sign step only then we have provider and plan details but it is not as per FRD.
        // $quoteRequest->loadMissing('insuranceProvider');
        // return $quoteRequest->insuranceProvider?->code === InsuranceProvidersEnum::OIC;
    }
}

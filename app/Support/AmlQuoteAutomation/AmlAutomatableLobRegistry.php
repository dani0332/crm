<?php

declare(strict_types=1);

namespace App\Support\AmlQuoteAutomation;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use Illuminate\Database\Eloquent\Model;

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
     * For non-OIC insurers, keep the issuance status check in place.
     *
     * Callers must ensure {@see PersonalQuote::$insuranceProvider} is loaded when the quote is a {@see PersonalQuote}
     * (e.g. {@see Model::loadMissing()}), so provider code can be read without an extra query here.
     */
    public static function skipsApiIssuanceStatusCheckForAutomatedAml(QuoteTypes $quoteType, Model $quoteRequest): bool
    {
        if ($quoteType === QuoteTypes::SAVINGS && $quoteRequest instanceof PersonalQuote) {
            return $quoteRequest->insuranceProvider?->code === InsuranceProvidersEnum::OIC;
        }

        return false;
    }
}

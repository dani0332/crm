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
 *
 * Callers must ensure the `insuranceProvider` relation is loaded on `$quoteRequest`
 * before calling any method that reads provider codes (e.g. {@see loadMissing()}).
 */
final class AmlAutomatableLobRegistry
{
    /**
     * Only these LOBs are allowed to trigger AML automation from API calls.
     *
     * @return list<QuoteTypes>
     */
    public static function allowedLobsFromAPI(): array
    {
        return [
            QuoteTypes::SAVINGS,
        ];
    }

    public static function isLobAllowedForAmlAutomationScreeningSucceededEvent(QuoteTypes $quoteType): bool
    {
        return in_array($quoteType, [QuoteTypes::SAVINGS], true);
    }

    /**
     * Whether to skip the `api_issuance_status_id = YES` pre-check for this quote.
     *
     * Savings + OIC: policy issuance API status is not expected before AML automation.
     * All other LOBs and providers keep the status check in place.
     */
    public static function skipsApiIssuanceStatusCheckForAutomatedAml(QuoteTypes $quoteType, Model $quoteRequest): bool
    {
        if ($quoteType === QuoteTypes::SAVINGS && $quoteRequest instanceof PersonalQuote) {
            return $quoteRequest->insuranceProvider?->code === InsuranceProvidersEnum::OIC;
        }

        return false;
    }

}

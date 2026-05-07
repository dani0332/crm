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
     */
    public static function skipsApiIssuanceStatusCheckForAutomatedAml(QuoteTypes $quoteType, Model $quoteRequest): bool
    {
        if ($quoteType === QuoteTypes::SAVINGS && $quoteRequest instanceof PersonalQuote) {
            // Provider can be missing in early stages, so we load relation defensively.
            // Only OIC Savings skips the issuance status precondition for AML automation.
            $quoteRequest->loadMissing('insuranceProvider');

            return $quoteRequest->insuranceProvider?->code === InsuranceProvidersEnum::OIC;
        }

        return false;
    }
}

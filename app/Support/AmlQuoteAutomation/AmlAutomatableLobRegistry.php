<?php

declare(strict_types=1);

namespace App\Support\AmlQuoteAutomation;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use Carbon\Carbon;
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
    /** Number of days a Device/NGI booking remains eligible for AML automation. */
    private const DEVICE_NGI_BOOKING_WINDOW_DAYS = 7;

    /**
     * Only these LOBS are allowed to trigger AML automation from API calls
     *
     * @return string[]
     */
    public static function allowedLobsFromAPI(): array
    {
        return [
            QuoteTypes::SAVINGS,
        ];
    }

    public static function isLobAllowedForAmlAutomationScreeningSucceededEvent(QuoteTypes $quoteType): bool
    {
        return in_array($quoteType->value, [QuoteTypes::SAVINGS->value], true);
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

    /**
     * Run LOB-specific eligibility constraints that go beyond the common checks
     * performed by {@see AmlAutomationEligibilityService}.
     *
     * Returns a blocking result when the quote must not be dispatched, or null when
     * the LOB has no additional constraints (or they all pass).
     *
     * Currently enforced rules:
     *   - Device + NGI: `policy_booking_date` must be within the last
     *     {@see DEVICE_NGI_BOOKING_WINDOW_DAYS} days so that stale bookings are
     *     never re-submitted for automated AML screening.
     */
    public static function checkLobSpecificEligibility(QuoteTypes $quoteType, Model $quote): ?AmlAutomationEligibilityResult
    {
        if ($quoteType === QuoteTypes::DEVICE) {
            return self::checkDeviceNgiEligibility($quote);
        }

        return null;
    }

    /**
     * Device + NGI constraint: the booking date must fall within the recent window.
     *
     * Only applied when the insurance provider is NGI; other Device providers have
     * no additional date constraint at this time.
     */
    private static function checkDeviceNgiEligibility(Model $quote): ?AmlAutomationEligibilityResult
    {
        if ($quote->insuranceProvider?->code !== InsuranceProvidersEnum::NGI) {
            return null;
        }

        $bookingDate = $quote->policy_booking_date ?? null;

        if (empty($bookingDate)) {
            return AmlAutomationEligibilityResult::block(
                'Device/NGI AML automation requires a policy booking date',
                'device_ngi_booking_date_missing'
            );
        }

        $cutoff = Carbon::now()->subDays(self::DEVICE_NGI_BOOKING_WINDOW_DAYS)->startOfDay();
        if (Carbon::parse($bookingDate)->isBefore($cutoff)) {
            return AmlAutomationEligibilityResult::block(
                'Device/NGI AML automation: booking date is older than '.self::DEVICE_NGI_BOOKING_WINDOW_DAYS.' days',
                'device_ngi_booking_date_expired'
            );
        }

        return null;
    }
}

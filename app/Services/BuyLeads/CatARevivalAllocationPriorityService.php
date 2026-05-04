<?php

declare(strict_types=1);

namespace App\Services\BuyLeads;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use Illuminate\Database\Eloquent\Builder;

class CatARevivalAllocationPriorityService
{
    /**
     * Matches SQL in {@see self::effectiveCarValueExpressionSql()} for ordering and comparisons.
     */
    public static function effectiveCarValue(CarQuote $lead): float
    {
        $carValue = $lead->car_value;
        if ($carValue !== null && $carValue !== '' && $carValue !== '?' && (float) $carValue > 0) {
            return (float) $carValue;
        }

        $tier = $lead->car_value_tier;
        if ($tier !== null && $tier !== '' && (float) $tier > 0) {
            return (float) $tier;
        }

        return 0.0;
    }

    /**
     * SQL expression for "effective" car value (admin tier bands derive from this value on the lead).
     *
     * @param  string  $table  Table name or alias (e.g. car_quote_request)
     */
    public static function effectiveCarValueExpressionSql(string $table = 'car_quote_request'): string
    {
        return "CASE WHEN {$table}.car_value IS NOT NULL AND {$table}.car_value > 0 THEN {$table}.car_value WHEN {$table}.car_value_tier IS NOT NULL AND {$table}.car_value_tier > 0 THEN {$table}.car_value_tier ELSE 0 END";
    }

    /**
     * Days of history for CAT A Revival priority / retry windows (see `constants.CAR_CAT_A_REVIVAL_ALLOCATION_LOOKBACK_DAYS`).
     */
    public static function lookbackDays(): int
    {
        return (int) getAppStorageValueByKey(ApplicationStorageEnums::CAR_CAT_A_REVIVAL_ALLOCATION_LOOKBACK_DAYS, useCache: true);
    }

    /**
     * Unassigned Revival leads eligible for CAT A buy allocation (nationality from configuration).
     * Only considers leads created within the last {@see self::lookbackDays()} days.
     */
    public static function unassignedRevivalCatABaseQuery(): Builder
    {
        $nationalityIds = BuyLeadService::getNationalitiesIds(QuoteTypes::CAR_CAT_A);

        $query = CarQuote::query()
            ->where('car_quote_request.source', LeadSourceEnum::REVIVAL)
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->where('car_quote_request.created_at', '>=', now()->subDays(self::lookbackDays())->startOfDay());

        if ($nationalityIds === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('car_quote_request.nationality_id', $nationalityIds)
            ->whereNull('car_quote_request.advisor_id');
    }

    public static function hasHigherPriorityUnassignedLead(CarQuote $lead): bool
    {
        if (! $lead->isCatABuyLeadApplicable(QuoteTypes::CAR_CAT_A)) {
            return false;
        }

        $current = self::effectiveCarValue($lead);
        $expr = self::effectiveCarValueExpressionSql('car_quote_request');

        return self::unassignedRevivalCatABaseQuery()
            ->where('car_quote_request.id', '!=', $lead->id)
            ->whereRaw("({$expr}) > ?", [$current])
            ->exists();
    }
}

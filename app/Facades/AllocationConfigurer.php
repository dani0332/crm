<?php

declare(strict_types=1);

namespace App\Facades;

use App\Enums\QuoteTypes;
use App\Models\BusinessQuote;
use App\Models\HomeQuote;
use App\Models\PersonalQuote;
use App\Services\AllocationConfiguration\AllocationConfigurationService;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for Allocation Configuration Service
 *
 * @method static array getSavingsEligibleAdvisorIds(PersonalQuote $lead)
 * @method static array getCommonEligibleAdvisorIds(QuoteTypes $quoteType)
 * @method static array getLifeEligibleAdvisorIds(PersonalQuote $lead)
 * @method static array getGroupMedicalEligibleAdvisorIds(BusinessQuote $lead)
 * @method static array getCorplineEligibleAdvisorIds(BusinessQuote $lead)
 * @method static array getHomeEligibleAdvisorIds(HomeQuote $lead)
 *
 * @see AllocationConfigurationService
 */
class AllocationConfigurer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AllocationConfigurationService::class;
    }
}

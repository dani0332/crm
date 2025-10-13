<?php

declare(strict_types=1);

namespace App\Facades;

use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\AllocationConfiguration\AllocationConfigurationService;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for Allocation Configuration Service
 *
 * @method static array getSavingsEligibleAdvisorIds(PersonalQuote $lead)
 * @method static array getCommonEligibleAdvisorIds(QuoteTypes $quoteType)
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

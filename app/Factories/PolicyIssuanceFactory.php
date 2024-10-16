<?php

namespace App\Factories;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteTypes;
use App\Services\PolicyIssuanceAutomation\Travel\AllianceInsuranceService;

class PolicyIssuanceFactory
{
    public static function make($quoteType, $insurerCode)
    {
        return match (ucfirst($quoteType)) {
            QuoteTypes::TRAVEL->value => match ($insurerCode) {
                InsuranceProvidersEnum::ALNC => new AllianceInsuranceService(),
                default => null,
            },
            default => null,
        };
    }
}

<?php

namespace App\Enums\ProcessTracker;

use App\Enums\Enumable;
use App\Enums\ProcessTracker\StepsEnums\ProcessTrackerAllocationEnum;

enum ProcessTrackerTypeEnum: string
{
    use Enumable;

    case CAR_ALLOCATION = 'car-allocation';
    case HEALTH_ALLOCATION = 'health-allocation';
    case HOME_ALLOCATION = 'home-allocation';
    case TRAVEL_ALLOCATION = 'travel-allocation';
    case LIFE_ALLOCATION = 'life-allocation';
    case BUSINESS_ALLOCATION = 'business-allocation';
    case PET_ALLOCATION = 'pet-allocation';

    public function steps()
    {
        return match ($this) {
            self::TRAVEL_ALLOCATION => [
                ProcessTrackerAllocationEnum::REQUEST_DETAILS,
                ProcessTrackerAllocationEnum::LEAD_NOT_FOUND,
                ProcessTrackerAllocationEnum::LEAD_FOUND,
                ProcessTrackerAllocationEnum::ADVISOR_NOT_FOUND,
                ProcessTrackerAllocationEnum::ADVISOR_FOUND,
                ProcessTrackerAllocationEnum::LEAD_ASSIGNED,
                ProcessTrackerAllocationEnum::EXCEPTION_RAISED,
            ],
            default => [],
        };
    }
}

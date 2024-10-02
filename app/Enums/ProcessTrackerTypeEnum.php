<?php

namespace App\Enums;

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

    public function label()
    {
        return match ($this) {
            self::CAR_ALLOCATION => 'Car Allocation',
            self::HEALTH_ALLOCATION => 'Health Allocation',
            self::HOME_ALLOCATION => 'Home Allocation',
            self::TRAVEL_ALLOCATION => 'Travel Allocation',
            self::LIFE_ALLOCATION => 'Life Allocation',
            self::BUSINESS_ALLOCATION => 'Business Allocation',
            self::PET_ALLOCATION => 'Pet Allocation',
        };
    }
}

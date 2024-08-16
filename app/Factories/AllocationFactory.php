<?php

namespace App\Factories;

use App\Enums\QuoteTypeId;
use App\Services\BikeAllocationService;
use App\Services\CarAllocationService;
use App\Services\HealthAllocationService;
use App\Services\TravelAllocationService;
use App\Strategies\BikeAllocation;
use App\Strategies\CarAllocation;
use App\Strategies\HealthAllocation;
use App\Strategies\TravelAllocation;

class AllocationFactory
{
    public static function createStrategy($allocationType, $allocationId, $teamId = false)
    {
        $strategy = null;
        if ($allocationType == QuoteTypeId::Car) {
            $strategy = new CarAllocation(new CarAllocationService(), $allocationId, $teamId);
        } elseif ($allocationType == QuoteTypeId::Health) {
            $strategy = new HealthAllocation(new HealthAllocationService(), $allocationId);
        } elseif ($allocationType == QuoteTypeId::Bike) {
            $strategy = new BikeAllocation(new BikeAllocationService(), $allocationId);
        } elseif ($allocationType == QuoteTypeId::Travel) {
            $strategy = new TravelAllocation(new TravelAllocationService(), $allocationId, $teamId);
        }

        return $strategy;
    }
}

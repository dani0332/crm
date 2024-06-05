<?php

namespace App\Factories;

use App\Enums\QuoteTypeId;
use App\Services\CarAllocationService;
use App\Services\HealthAllocationService;
use App\Strategies\CarAllocation;
use App\Strategies\HealthAllocation;

class AllocationFactory
{
    public static function createStrategy($allocationType, $allocationId, $teamId = false)
    {
        $strategy = null;
        if ($allocationType == QuoteTypeId::Car) {
            $strategy = new CarAllocation(new CarAllocationService(), $allocationId, $teamId);
        } elseif ($allocationType == QuoteTypeId::Health) {
            $strategy = new HealthAllocation(new HealthAllocationService(), $allocationId);
        }

        return $strategy;
    }
}

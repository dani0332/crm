<?php

namespace App\Strategy;

use App\Enums\QuoteTypeId;
use App\Services\CarAllocationService;
use App\Services\HealthAllocationService;

class AllocationFactory
{
    public static function createStrategy($allocationType, $allocationId)
    {
        $strategy = null;

        if ($allocationType === QuoteTypeId::Car) {
            $strategy = new CarAllocationStrategy(new CarAllocationService(), $allocationId);
        } elseif ($allocationType === QuoteTypeId::Health) {
            $strategy = new HealthAllocationStrategy(new HealthAllocationService(), $allocationId);
        }

        return $strategy;
    }
}

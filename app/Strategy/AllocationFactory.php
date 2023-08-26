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
        info('Inside strategy creation for quoteTypeId : '.$allocationType.' and quote id is : '.$allocationId);
        if ($allocationType == QuoteTypeId::Car) {
            info('Car Allocation is about to trigger');
            $strategy = new CarAllocation(new CarAllocationService(), $allocationId);
        } elseif ($allocationType == QuoteTypeId::Health) {
            info('Health Allocation is about to trigger');
            $strategy = new HealthAllocation(new HealthAllocationService(), $allocationId);
        }

        return $strategy;
    }
}

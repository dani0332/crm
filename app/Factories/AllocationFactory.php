<?php

namespace App\Factories;

use App\Enums\QuoteTypeId;
use App\Services\BikeAllocationService;
use App\Services\CarAllocationService;
use App\Services\HealthAllocationService;
use App\Services\TravelAllocationService;
use App\Strategies\Allocations\BikeAllocation;
use App\Strategies\Allocations\CarAllocation;
use App\Strategies\Allocations\HealthAllocation;
use App\Strategies\Allocations\TravelAllocation;

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


    public static function createResponse(int $advisorId, string $message, int $status): array
    {
        return [
            'advisorId' => $advisorId,
            'message' => $message,
            'status' => $status
        ];
    }
}

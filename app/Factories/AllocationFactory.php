<?php

namespace App\Factories;

use App\Enums\ProcessTracker\ProcessTrackerTypeEnum;
use App\Enums\ProcessTracker\StepsEnums\ProcessTrackerAllocationEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Services\BikeAllocationService;
use App\Services\CarAllocationService;
use App\Services\HealthAllocationService;
use App\Services\ProcessTracker\ProcessTrackerService;
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
            $strategy = new CarAllocation(new CarAllocationService, $allocationId, $teamId);
        } elseif ($allocationType == QuoteTypeId::Health) {
            $strategy = new HealthAllocation(new HealthAllocationService, $allocationId);
        } elseif ($allocationType == QuoteTypeId::Bike) {
            $strategy = new BikeAllocation(new BikeAllocationService, $allocationId);
        } elseif ($allocationType == QuoteTypeId::Travel) {
            $tracker = self::generateTrackerService(ProcessTrackerTypeEnum::TRAVEL_ALLOCATION, QuoteTypes::TRAVEL, $allocationId, $teamId);
            $strategy = new TravelAllocation(new TravelAllocationService, $tracker, $allocationId, $teamId);
        }

        return $strategy;
    }

    public static function createResponse(int $advisorId, string $message, int $status, int $tierId = 0): array
    {
        return [
            'advisorId' => $advisorId,
            'message' => $message,
            $tierId != 0 && 'tierId' => $tierId,
            'status' => $status,
        ];
    }

    private static function generateTrackerService(ProcessTrackerTypeEnum $processType, QuoteTypes $quoteType, string $uuid, $teamId)
    {
        return (new ProcessTrackerService)->initQuoteProcess($processType, $quoteType, $uuid)
            ->addStep(
                ProcessTrackerAllocationEnum::REQUEST_DETAILS,
                ['teamId' => ($teamId ?: null), 'requestParams' => request()->all()],
            );
    }
}

<?php

namespace App\Enums\Traits;

use App\Enums\ProcessTracker\ProcessTrackerTypeEnum;
use App\Enums\ProcessTracker\StepsEnums\ProcessTrackerAllocationEnum;
use App\Services\ProcessTracker\ProcessTrackerService;

trait QuoteTypable
{
    public function getTracker(ProcessTrackerTypeEnum $processType, string $uuid, $teamId)
    {
        return (new ProcessTrackerService)->initQuoteProcess($processType, $this, $uuid)
            ->addStep(
                ProcessTrackerAllocationEnum::REQUEST_DETAILS,
                ['teamId' => ($teamId ?: null), 'requestParams' => request()->all()],
            );
    }
}

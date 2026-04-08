<?php

declare(strict_types=1);

namespace App\Pipes\Allocation\Car;

use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\BuyLeads\CatARevivalAllocationPriorityService;
use App\Services\Logger\LoggerService;
use Closure;

class DeferLowerPriorityCatARevivalLeadPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        if ($this->allocationRequest->isEvaluateTierOnlyRequest()) {
            return $next($request);
        }

        if ($this->allocationRequest->isOverrideAdvisorRequest()) {
            return $next($request);
        }

        $lead = $this->lead;

        if (! $lead instanceof CarQuote) {
            return $next($request);
        }

        if (! $lead->isCatABuyLeadApplicable(QuoteTypes::CAR_CAT_A)) {
            return $next($request);
        }

        $effectiveCarValue = CatARevivalAllocationPriorityService::effectiveCarValue($lead);

        if (CatARevivalAllocationPriorityService::hasHigherPriorityUnassignedLead($lead)) {
            LoggerService::info(self::class.'::handle - Deferring allocation: higher car value Revival CAT A lead exists in queue', [
                'uuid' => $lead->uuid,
                'effective_car_value' => $effectiveCarValue,
            ]);
            $this->allocationRequest->markAsFailed();
            $this->throw('Advisor assignment is in progress and will be assigned shortly', self::OK);
        }

        LoggerService::info(self::class.'::handle - Proceeding as highest priority CAT A Revival lead in queue', [
            'uuid' => $lead->uuid,
            'effective_car_value' => $effectiveCarValue,
        ]);

        return $next($request);
    }
}

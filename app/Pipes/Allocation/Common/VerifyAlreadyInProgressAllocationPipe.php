<?php

namespace App\Pipes\Allocation\Common;

use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class VerifyAlreadyInProgressAllocationPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     *
     * @param  mixed  $passable
     * @return mixed
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        // Allow evaluate-tier-only requests to proceed even if allocation is marked in progress
        if (! $this->allocationRequest->isEvaluateTierOnlyRequest() && $this->lead->isAllocationInProgress()) {
            LoggerService::info("Allocation is already started at {$this->lead->lead_allocation_started_at}");

            $this->throw('Allocation is in progress', self::OK);
        }

        // For evaluate-tier-only, do not flip allocation-in-progress flag
        if (! $this->allocationRequest->isEvaluateTierOnlyRequest()) {
            $this->lead->startAllocation();
        }

        return $next($request);
    }
}

<?php

namespace App\Pipes\Allocation\Claim;

use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class VerifyAlreadyInProgressAllocationPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     * Prevents duplicate/concurrent allocations by marking the lead as in-progress
     * and rejecting further attempts until the allocation completes or times out.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        if ($this->lead->isAllocationInProgress()) {
            LoggerService::info("Claim allocation already in progress at {$this->lead->lead_allocation_started_at}");

            $this->throw('Allocation is in progress', self::OK);
        }

        $this->lead->startAllocation();
        $this->allocationRequest->set('allocation_started_by_this_request', true);

        return $next($request);
    }
}

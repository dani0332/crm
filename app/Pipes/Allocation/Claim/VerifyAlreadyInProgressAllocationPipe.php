<?php

namespace App\Pipes\Allocation\Claim;

use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;
use App\Pipes\Allocation\Claim\BaseAllocationPipe;

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

        if ($this->lead->isAllocationInProgress()) {
            LoggerService::info("Allocation is already started at {$this->lead->lead_allocation_started_at}");

            $this->throw('Allocation is in progress', self::OK);
        }

        $this->lead->startAllocation();

        return $next($request);
    }
}

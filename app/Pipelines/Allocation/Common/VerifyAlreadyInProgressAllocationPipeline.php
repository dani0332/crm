<?php

namespace App\Pipelines\Allocation\Common;

use App\Services\Logger\LoggerService;
use App\Strategies\Allocations\PipelineHandlers\AllocationRequest;
use Closure;

class VerifyAlreadyInProgressAllocationPipeline extends BaseAllocationPipeline
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
            LoggerService::info("Allocation is already started at {$this->lead->allocation_started_at}");

            $this->throw('Allocation is in progress', self::OK);
        }

        $this->lead->startAllocation();

        return $next($request);
    }
}

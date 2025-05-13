<?php

namespace App\Pipelines\Allocation\Common;

use App\Strategies\Allocations\PipelineHandlers\AllocationRequest;
use Closure;

class FetchLeadPipeline extends BaseAllocationPipeline
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request, true);

        $this->resolveLead();

        return $next($request);
    }
}

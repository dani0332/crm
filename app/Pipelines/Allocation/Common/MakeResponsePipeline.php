<?php

namespace App\Pipelines\Allocation\Common;

use App\Strategies\Allocations\PipelineHandlers\AllocationRequest;
use Closure;

class MakeResponsePipeline extends BaseAllocationPipeline
{
    /**
     * Handle the incoming request.
     *
     * @param  mixed  $passable
     * @return mixed
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        return $this->createResponse2($request);
    }
}

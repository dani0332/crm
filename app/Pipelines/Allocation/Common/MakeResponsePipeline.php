<?php

namespace App\Pipelines\Allocation\Common;

use Closure;
use App\Strategies\Allocations\PipelineHandlers\AllocationRequest;

class MakeResponsePipeline extends BaseAllocationPipeline
{
    /**
     * Handle the incoming request.
     *
     * @param  mixed  $passable
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        return $this->createResponse2($request);
    }
}

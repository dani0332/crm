<?php

namespace App\Pipes\Allocation\Common;

use App\Pipes\Allocation\Handlers\AllocationRequest;
use Closure;

class MakeResponsePipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     *
     * @param  mixed  $passable
     * @return mixed
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        return $this->resolveAllocationResponse($request);
    }
}

<?php

namespace App\Pipes\Allocation\Common;

use App\Pipes\Allocation\Handlers\AllocationRequest;
use Closure;

class FetchLeadPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request, true);

        $this->resolveLead();

        return $next($request);
    }
}

<?php

namespace App\Pipes\Allocation\Claim;

use App\Models\User;
use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\Logger\LoggerService;
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

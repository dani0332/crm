<?php

namespace App\Pipes\Allocation\Claim;

use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class AssignLeadPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        if ($request->isSameManager()) {
            return $next($request);
        }

        $this->assign(function () {
            LoggerService::info('Claim lead assigned to Manager');
        });

        return $next($request);
    }

}

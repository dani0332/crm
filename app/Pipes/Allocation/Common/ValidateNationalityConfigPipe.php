<?php

namespace App\Pipes\Allocation\Common;

use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\NationalityAllocationService;
use Closure;

class ValidateNationalityConfigPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $config = $this->getNationalityConfig();

        if (! $config) {
            return $next($request);
        }

        $this->allocationRequest->setNationalityConfig($config);

        $this->allocationRequest->setAdvisorIDs(NationalityAllocationService::getUserIDs($config));

        return $next($request);
    }

    private function getNationalityConfig()
    {
        return NationalityAllocationService::find($this->allocationRequest->getQuoteType(), $this->lead->nationality_id);
    }
}

<?php

namespace App\Pipelines\Allocation\Common;

use App\Strategies\Allocations\PipelineHandlers\AllocationRequest;
use Closure;

class FetchLeadPipeline extends BaseAllocationPipeline
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request, true);

        $lead = $this->resolveLead();

        if (! $lead) {
            $this->throw('Lead not found', self::NOT_FOUND);
        }

        $request->set('lead', $lead);

        return $next($request);
    }

    private function resolveLead()
    {
        $lead = $this->getBaseLead();

        if (! $lead) {
            return null;
        }

        return $lead;
    }
}

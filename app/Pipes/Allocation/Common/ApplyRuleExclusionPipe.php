<?php

namespace App\Pipes\Allocation\Common;

use App\Services\RuleService;
use Closure;

class ApplyRuleExclusionPipe extends BaseAllocationPipe
{
    public function handle($request, Closure $next)
    {

        $this->setRequest($request);

        $lead = $request->getLead();

        $rules = app(RuleService::class)->getUsersByLeadSourceRules($lead->source, $this->allocationRequest->getQuoteType()->id());
        $this->allocationRequest->set('rules', $rules);

        return $next($request);
    }
}

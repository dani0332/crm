<?php

namespace App\Pipes\Allocation\Common;

use Closure;

class ResetNationalityConfigPipe extends BaseAllocationPipe
{
    public function handle($request, Closure $next)
    {
        $this->setRequest($request);

        $eligibleAdvisors = $request->get('eligibleAdvisors');

        if ($this->allocationRequest->hasNationalityConfig() && empty($eligibleAdvisors)) {
            $this->allocationRequest->resetNationalityConfig();
        } else {
            $this->allocationRequest->set('dontRetryAdvisor', true);
        }

        return $next($request);
    }
}

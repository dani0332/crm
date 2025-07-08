<?php

namespace App\Pipes\Allocation\Common;

use App\Services\Logger\LoggerService;
use Closure;

class ResetNationalityConfigPipe extends BaseAllocationPipe
{
    public function handle($request, Closure $next)
    {
        LoggerService::info('ResetNationalityConfigPipe::handle - Resetting nationality config');
        $this->setRequest($request);

        $eligibleAdvisors = $request->get('eligibleAdvisors');

        if ($this->allocationRequest->hasNationalityConfig() && empty($eligibleAdvisors)) {
            $this->allocationRequest->resetNationalityConfig();
            $this->resolveExcludedAdvisorIds();
            LoggerService::info('ResetNationalityConfigPipe::handle - Going to retry advisor allocation');
        } else {
            $this->allocationRequest->set('skipAdvisorEligibilityFetch', true);
            $this->allocationRequest->set('skipRuleExclusion', true);
        }

        return $next($request);
    }
}

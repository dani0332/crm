<?php

namespace App\Pipes\Allocation\Common;

use App\Services\NationalityAllocationService;
use App\Services\Logger\LoggerService;
use Closure;

class ResetNationalityConfigPipe extends BaseAllocationPipe
{
    public function handle($request, Closure $next)
    {
        LoggerService::info("ResetNationalityConfigPipe::handle - Resetting nationality config");
        $this->setRequest($request);

        $eligibleAdvisors = $request->get('eligibleAdvisors');

        if ($this->allocationRequest->hasNationalityConfig() && empty($eligibleAdvisors)) {
            $this->allocationRequest->resetNationalityConfig();
            $this->resolveExcludedAdvisorIds();
            LoggerService::info("ResetNationalityConfigPipe::handle - Going to retry advisor allocation");
        } else {
            $this->allocationRequest->set('dontRetryAdvisor', true);
            $this->allocationRequest->set('dontRetryRuleExclusion', true);
        }

        return $next($request);
    }

    private function resolveExcludedAdvisorIds()
    {
        $excludedAdvisorIds = NationalityAllocationService::getExcludedUserIds($this->allocationRequest->getQuoteType());

        if (empty($excludedAdvisorIds)) {
            return;
        }

        $this->allocationRequest->excludedAdvisorIds($excludedAdvisorIds);
    }
}

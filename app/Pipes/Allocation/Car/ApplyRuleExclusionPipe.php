<?php

namespace App\Pipes\Allocation\Car;

use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;
use Closure;

class ApplyRuleExclusionPipe extends BaseAllocationPipe
{
    use Carable;

    public function handle($request, Closure $next)
    {
        if ($request->hasNationalityConfig() || $request->get('skipRuleExclusion', false)) {
            return $next($request);
        }

        $this->setRequest($request);

        $tierUserIds = $request->get('tierUserIds');
        $lead = $request->getLead();

        $rules = $this->getRulesForLeadSource($lead);

        $tierUserIds = $this->applyRuleExclusions($rules, $tierUserIds, $lead);

        $this->allocationRequest->set('rules', $rules);
        $this->allocationRequest->set('ruleUsers', $this->getRuleUsers());
        $this->allocationRequest->set('tierUserIds', $tierUserIds);

        return $next($request);
    }

    private function finalizeTierUsers($tierUserIds, $rules)
    {
        $ruleUserIds = $this->getUserIdsFromRuleRecords($rules);

        $finalEligibleUserIds = array_intersect($tierUserIds, $ruleUserIds);

        return array_values($finalEligibleUserIds);
    }

    private function applyRuleExclusions($rules, $tierUserIds, $lead)
    {
        if ($rules->isEmpty()) {

            $ruleUserIds = $this->getRuleUsers();
            LoggerService::info('No rules found, excluding rule users: ', json_encode($ruleUserIds));

            $ruleUserIds = $this->finalizeExcludedAdvisorIds($ruleUserIds);

            LoggerService::info('Final rule users after excluding non rule users: ', json_encode($ruleUserIds));

            return array_diff($tierUserIds, $ruleUserIds);
        } else {
            return $this->finalizeTierUsers($tierUserIds, $rules);
        }
    }

    private function getRuleUsers(): mixed
    {
        return app(RuleService::class)->getRuleUserIds($this->allocationRequest->getQuoteType());
    }
}

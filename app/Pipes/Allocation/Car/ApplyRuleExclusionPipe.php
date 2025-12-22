<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\CarRegistrationType;
use App\Enums\CarVehicleUse;
use App\Enums\LeadSourceEnum;
use App\Enums\RuleEnum;
use App\Enums\RuleTypeEnum;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CommercialKeyword;
use App\Models\LeadSource;
use App\Models\Rule;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;
use Closure;
use Illuminate\Support\Facades\DB;

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
            if ($lead->registration_type == CarRegistrationType::PERSONAL) {
                $ruleUserIds = $this->getRuleUsers(excludeVehicleUseRule: true);
            } else {
                $ruleUserIds = $this->getRuleUsers(excludeVehicleUseRule: false);
            }

            LoggerService::info('No rules found, excluding rule users: ', json_encode($ruleUserIds));

            return array_diff($tierUserIds, $ruleUserIds);
        } else {
            return $this->finalizeTierUsers($tierUserIds, $rules);
        }
    }

    private function getRuleUsers($excludeVehicleUseRule = true): mixed
    {
        return app(RuleService::class)->getRuleUserIds($this->allocationRequest->getQuoteType(), $excludeVehicleUseRule);
    }
}

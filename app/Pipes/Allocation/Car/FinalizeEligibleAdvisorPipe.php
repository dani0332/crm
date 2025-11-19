<?php

namespace App\Pipes\Allocation\Car;

use App\Models\BuyLeadRequest;
use App\Models\CarQuote;
use App\Models\LeadAllocation;
use App\Models\Tier;
use App\Models\User;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FinalizeEligibleAdvisorPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $eligibleAdvisors = $request->get('eligibleAdvisors');

        $availableUserIds = collect($eligibleAdvisors)->pluck('user_id')->toArray();
        LoggerService::info('Available User IDs are: '.json_encode($availableUserIds));

        if (! $request->hasNationalityConfig()) {
            $lead = $request->getLead();
            $teamId = $request->getTeamId();
            $rules = $request->get('rules', []);
            $availableUserIds = $this->determineFinalAdvisorIdsBasedOnRules($lead, $availableUserIds, $rules, $teamId);
        }

        $advisorId = $this->getFinalAdvisorId($availableUserIds);
        $advisor = User::find($advisorId);

        if (! $advisor) {
            LoggerService::warning('No advisor found');

            $this->allocationRequest->markAsFailed();

            $this->throw('Advisor assignment is in progress and will be assigned shortly', self::OK);
        }

        $this->allocationRequest->setAdvisor($advisor);

        $this->verifyIfAdvisorIsSameAsPreviousAdvisor($advisor);

        return $next($request);
    }

    private function getFinalAdvisorId($finalEligibleUserIds)
    {
        if ($this->allocationRequest->get('hasBuyLeadAdvisors')) {
            return $this->evaluateBuyLeadAdvisor($finalEligibleUserIds, $this->allocationRequest->getTier());
        }

        // Return the first user ID from the final eligible user IDs if any, otherwise return 0.
        return count($finalEligibleUserIds) > 0 ? reset($finalEligibleUserIds) : 0;
    }

    private function evaluateBuyLeadAdvisor($finalEligibleUserIds, Tier $tier)
    {
        foreach ($finalEligibleUserIds as $advisorId) {
            $buyLeadRequest = BuyLeadRequest::getRequest(
                $this->allocationRequest->getQuoteType(),
                $this->allocationRequest->isSIC(),
                $advisorId,
                $tier->isValue()
            );

            if ($buyLeadRequest) {
                $this->allocationRequest->setBuyLeadRequest($buyLeadRequest);

                LoggerService::info("Buy Lead Request {$buyLeadRequest->id} found for advisor ID: {$advisorId} and tier ID: {$tier->id}");
                $this->allocationRequest->getBuyLeadRequest()->startProcessing();

                return $advisorId;
            }
        }

        return 0;
    }

    private function determineFinalAdvisorIdsBasedOnRules(CarQuote $lead, $availableUserIds, $rules, $teamId): mixed
    {
        if (count($rules) > 0) {
            // If there are rules, retrieve user IDs from the rule records.
            $ruleUserIds = $this->getUserIdsFromRuleRecords($rules);

            LoggerService::info('Rule user IDs are: '.json_encode($ruleUserIds));

            // Find the intersection of available user IDs and rule user IDs.
            $finalEligibleUserIds = array_intersect($availableUserIds, $ruleUserIds);

            // Check if the lead source indicates a SAP lead.
            $isSAPLead = str_contains($lead->source, 'sap-') || str_contains($lead->source, 'partner.alfred.ae') || str_contains($lead->source, 'partner/car-insurance/gems');
            if ($isSAPLead) {
                // If the lead source is SAP, get eligible users for SAP leads.
                LoggerService::info('SAP lead found, so filtering eligible users for SAP lead');
                $finalEligibleUserIds = $this->getEligibleUserForSAPLead($ruleUserIds);
            }

            // if finalEligibleUserIds count is zero then it means all rule users are unavailable
            if (count($finalEligibleUserIds) == 0 && $rules->first()?->ruleName == 'Commercial') {
                LoggerService::warning('All rule users are unavailable, so checking for commercial rule users regardless of availability.');
                // if commercial then we need to assign lead to one of the $ruleUserIds based on max cap
                // and other allocation criteria like round robin
                $finalEligibleUserIds = $this->fetchUsersOnAllocationCriteria($ruleUserIds);
            }

            LoggerService::info('Rule found, and users against the rule are: '.json_encode($finalEligibleUserIds));
        } else {
            // If no rules are found, get user IDs from rule lead sources.
            $ruleUsers = (empty($teamId) || $teamId == 0) ? $this->allocationRequest->get('ruleUsers') : [];

            LoggerService::info('No rule found, so filtering rule users: '.json_encode($ruleUsers).' and teamId is : '.$teamId);

            // Find the difference between available user IDs and rule users.
            $finalEligibleUserIds = array_diff(
                $availableUserIds,
                is_array($ruleUsers) ? $ruleUsers : []
            );

            LoggerService::info('Final login and available users after rule exclusion are: '.json_encode($finalEligibleUserIds));
        }

        return $finalEligibleUserIds;
    }

    private function getEligibleUserForSAPLead($ruleUserIds): array
    {
        // Create a query to fetch lead allocations with their associated users.
        return LeadAllocation::with('leadAllocationUser')
            ->activeUser()
            ->whereIn('user_id', $ruleUserIds) // it will be the rule user ids for SAP rule only
            ->where('quote_type_id', $this->allocationRequest->getQuoteType()->id())
            ->orderBy('last_allocated')
            ->pluck('user_id')
            ->toArray();
    }

    private function fetchUsersOnAllocationCriteria($ruleUserIds)
    {
        $excludedUserIds = $this->allocationRequest->get('excludedUserIds');

        // Create a query to fetch lead allocations with their associated users.
        return LeadAllocation::where(function ($query) {
            // Apply allocation count and max capacity conditions.
            $query->whereRaw('allocation_count < max_capacity')
                ->orWhere('max_capacity', -1);
        })
            ->whereIn('user_id', $ruleUserIds)
            ->whereNotIn('user_id', $excludedUserIds)
            ->where('quote_type_id', $this->allocationRequest->getQuoteType()->id())
            ->when(
                ! $this->allocationRequest->hasNationalityConfig(),
                function ($q) {
                    if ($this->allocationRequest->hasExcludedAdvisorIds()) {
                        $q->whereNotIn('user_id', $this->allocationRequest->getExcludedAdvisorIds());
                    }
                },
            )
            ->activeUser()
            ->orderBy('last_allocated')
            ->pluck('user_id')
            ->toArray();
    }
}

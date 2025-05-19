<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\QuoteTypes;
use App\Models\BuyLeadRequest;
use App\Models\CarQuote;
use App\Models\LeadAllocation;
use App\Models\Tier;
use App\Models\User;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Services\Logger\LoggerService;
use Closure;

class FinalizeEligibleAdvisorPipe extends BaseAllocationPipe
{
    public function handle($request, Closure $next)
    {
        $this->setRequest($request);

        $eligibleAdvisors = $request->get('eligibleAdvisors');
        $lead = $request->getLead();
        $tier = $request->getTier();
        $teamId = $request->getTeamId();
        $rules = $request->get('rules');

        $advisorId = $this->determineFinalUserId($lead, $eligibleAdvisors, $rules, $teamId, $tier);
        $advisor = User::find($advisorId);

        if (! $advisor) {
            LoggerService::warning('No advisor found');

            $this->allocationRequest->markAsFailed();

            $this->throw('Advisor not found', self::OK);
        }

        $this->allocationRequest->setAdvisor($advisor);

        $this->verifyIfAdvisorIsSameAsPreviousAdvisor($advisor);

        return $next($request);
    }

    private function determineFinalUserId(CarQuote $lead, $eligibleUsers, $rules, $teamId, Tier $tier): mixed
    {
        // Extract user IDs from the eligible user data and convert them to an array.
        $availableUserIds = collect($eligibleUsers)->pluck('user_id')->toArray();
        LoggerService::info('Available User IDs are: '.json_encode($availableUserIds));

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
            if (count($finalEligibleUserIds) == 0) {
                //  check if the found rule is commercial rule
                if ($rules->first()->ruleName == 'Commercial') {
                    LoggerService::warning('All rule users are unavailable, so checking for commercial rule users regardless of availability.');
                    // if commercial then we need to assign lead to one of the $ruleUserIds based on max cap
                    // and other allocation criteria like round robin
                    $finalEligibleUserIds = $this->fetchUsersOnAllocationCriteria($ruleUserIds);
                }
            }

            LoggerService::info('Rule found, and users against the rule are: '.json_encode($finalEligibleUserIds));
        } else {
            // If no rules are found, get user IDs from rule lead sources.
            $ruleUsers = (empty($teamId) || $teamId == 0) ? $this->allocationRequest->get('ruleUsers') : [];

            LoggerService::info('No rule found, so filtering rule users: '.json_encode($ruleUsers).' and teamId is : '.$teamId);

            // Find the difference between available user IDs and rule users.
            $finalEligibleUserIds = array_diff($availableUserIds, $ruleUsers);

            LoggerService::info('Final login and available users after rule exclusion are: '.json_encode($finalEligibleUserIds));
        }

        if ($this->allocationRequest->get('hasBuyLeadAdvisors')) {
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

        // Return the first user ID from the final eligible user IDs if any, otherwise return 0.
        return count($finalEligibleUserIds) > 0 ? reset($finalEligibleUserIds) : 0;
    }

    private function getUserIdsFromRuleRecords($matchedRuleRecords): array
    {
        // Get the lead source users from the first matched rule record.
        $leadSourceUsers = $matchedRuleRecords->first()->leadSourceUsers;

        // Check if the lead source users contain a comma (,) indicating multiple users.
        if (str_contains($leadSourceUsers, ',')) {
            // If there are multiple users, split the string by commas, convert each part to an integer, and store them in an array.
            $userIds = array_map('intval', explode(',', $leadSourceUsers));
        } else {
            // If there's only one user, cast it to an integer and store it in a single-element array.
            $userIds = [(int) $leadSourceUsers];
        }

        // Return the array of user IDs.
        return $userIds;
    }

    private function getEligibleUserForSAPLead($ruleUserIds): array
    {
        // Create a query to fetch lead allocations with their associated users.
        return LeadAllocation::with('leadAllocationUser')
            ->activeUser()
            ->whereIn('user_id', $ruleUserIds) // it will be the rule user ids for SAP rule only
            ->where('quote_type_id', QuoteTypes::CAR->id())
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
            ->where('quote_type_id', QuoteTypes::CAR->id())
            ->activeUser()
            ->orderBy('last_allocated')
            ->pluck('user_id')
            ->toArray();
    }
}

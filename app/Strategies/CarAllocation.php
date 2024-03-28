<?php

namespace App\Strategies;

use App\Enums\AssignmentTypeEnum;
use App\Models\Tier;
use App\Services\CarAllocationService;
use Illuminate\Database\Eloquent\Collection;

class CarAllocation implements Allocation
{
    private $carAllocationService;
    private $allocationId;
    private $teamId;

    public function __construct(CarAllocationService $carAllocationService, $allocationId, $teamId)
    {
        $this->carAllocationService = $carAllocationService;
        $this->allocationId = $allocationId;
        $this->teamId = $teamId;
    }

    public function executeSteps($overrideAdvisorId = false, $teamId = false, $evaluateTierOnly = false)
    {
        try {
            // Fetch the lead to process
            $lead = $this->fetchLead($overrideAdvisorId);

            if (! $lead) {
                info('Lead not found or not under fetch criteria for allocation id: '.$this->allocationId);

                return false; // when lead is not on criteria or not found
            }

            // Find the appropriate tier for the lead
            $tier = $lead->tier_id != null ? $this->getTier($lead->tier_id) : $this->findTier($lead);

            // If a valid tier is found
            if ($tier) {

                if ($evaluateTierOnly) {
                    info('Evaluate tier only. Tier finalized for lead : '.$lead->uuid.' is : '.$tier->name);
                    $lead->tier_id = $tier->id;
                    $lead->save();

                    return $tier->id;
                }
                info('Tier finalized for lead : '.$lead->uuid.' is : '.$tier->name);
                // Find available users for the tier
                $availableUsers = $this->findAvailableUsers($tier->id, $lead->source);

                // Find custom rules for the lead
                $rules = $this->findRules($lead);

                // Determine the final advisor for the lead based on tier, users, and rules
                $advisorId = $this->finalizeAdvisors($lead, $tier, $availableUsers, $rules);

                if (! empty($advisorId) && $advisorId == $lead->advisor_id) {
                    info('Advisor is same as previous advisor. Skipping for now.');

                    return $advisorId;
                }

                if ($advisorId && $advisorId != 0) {
                    $this->assignLead($lead, $advisorId, $tier);
                } else {
                    info('Advisor not found. Skipping for now.');
                    // Update the lead's tier information
                    $this->updateLeadTier($lead, $tier);
                }

                return $advisorId;
            } else {
                // Log that tier was not found for the lead and skip processing
                info('Tier not found for lead: '.$lead->uuid.'. Skipping for now.');

                return 0;
            }
        } catch (\Throwable $th) {
            info('exception occurred in car lead allocation with error : '.$th->getMessage());
            info('exception occurred in car lead allocation with error stack as  : '.$th->getTraceAsString());

            return null;
        }
    }

    protected function fetchLead($overrideAdvisorId): mixed
    {
        return $this->carAllocationService->fetchLead($this->allocationId, $overrideAdvisorId);
    }

    protected function getTier($tierId)
    {
        return $this->carAllocationService->getTier($tierId);
    }

    protected function findTier($lead): Tier
    {
        if ($lead->tier_id == null) {
            return $this->carAllocationService->findTier($lead);
        }

        return $this->carAllocationService->getTierById($lead->tier_id);
    }

    protected function findAvailableUsers($tierId, $leadSource): array|Collection
    {
        return $this->carAllocationService->getEligibleUserForAllocation($tierId, null, false, $leadSource, $this->teamId);
    }

    protected function findRules($lead)
    {
        return $this->carAllocationService->getRules($lead);
    }

    protected function finalizeAdvisors($lead, $tier, $users, $rules): int
    {
        return $this->carAllocationService->determineFinalUserId($lead, $users, $rules, $this->teamId);
    }

    protected function assignLead($lead, $userId, $tier): void
    {
        $this->carAllocationService->processLeadAssignment($lead, $userId, $tier, AssignmentTypeEnum::SYSTEM_ASSIGNED);
    }

    private function updateLeadTier($lead, $tier): void
    {
        $this->carAllocationService->updateLeadTier($lead, $tier);
    }
}

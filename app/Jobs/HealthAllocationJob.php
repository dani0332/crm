<?php

namespace App\Jobs;

use App\Models\Tier;
use App\Services\HealthAllocationService;

class HealthAllocationJob extends LeadAllocationJobInterface
{
    protected HealthAllocationService $healthAllocationService;

    public function __construct(HealthAllocationService $healthAllocationService)
    {
        $this->healthAllocationService = $healthAllocationService;
    }

    public function handle()
    {
        // Fetch the leads to process, including deferred leads if needed
        $leads = $this->fetchLeads();

        foreach ($leads as $lead) {
            // Find the appropriate tier for the lead
            $tier = $this->findTier($lead);

            // If a valid tier is found
            if ($tier) {
                // Find available users for the tier
                $availableUsers = $this->findAvailableUsers($tier->id);

                // Find custom rules for the lead
                $rules = $this->findRules($lead);

                // Determine the final advisor for the lead based on tier, users, and rules
                $advisorId = $this->finalizeAdvisors($lead, $tier, $availableUsers, $rules);

                if ($advisorId) {
                    // Assign the lead to the advisor and send an email
                    $this->assignLeadAndSendEmail($lead, $advisorId, $tier);
                } else {
                    // Update the lead's tier information
                    $this->updateLeadTier($lead, $tier);
                }
            } else {
                // Log that tier was not found for the lead and skip processing
                info('Tier not found for lead: '.$lead->uuid.'. Skipping for now.');
            }
        }
    }

    protected function fetchLeads(): mixed
    {
        return $this->healthAllocationService->fetchLeads();
    }

    protected function findTier($lead): Tier
    {
        if ($lead->tier_id == null) {
            return $this->healthAllocationService->findTier($lead);
        }

        return $this->healthAllocationService->getTierById($lead->tier_id);
    }

    protected function findAvailableUsers($tierId): array
    {
        return $this->healthAllocationService->getEligibleUsersForAllocation($tierId);
    }

    protected function findRules($lead): array
    {
        return $this->healthAllocationService->getRules($lead);
    }

    protected function finalizeAdvisors($lead, $tier, $users, $rules): int
    {
        return $this->healthAllocationService->determineFinalUserId($lead, $users, $rules);
    }

    protected function assignLeadAndSendEmail($lead, $userId, $tier): void
    {
        $this->healthAllocationService->processLeadAssignmentAndSendEmail($lead, $userId, $tier);
    }

    private function updateLeadTier($lead, $tier): void
    {
        $this->healthAllocationService->updateLeadTier($lead, $tier);
    }
}

<?php

namespace App\Jobs;

use App\Models\Tier;
use App\Services\CarAllocationService;

class CarJob extends JobInterface
{
    protected CarAllocationService $carAllocationService;

    public function __construct(CarAllocationService $carAllocationService)
    {
        $this->carAllocationService = $carAllocationService;
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
                info('Tier not found for lead: ' . $lead->uuid . '. Skipping for now.');
            }
        }
    }

    /**
     * @return mixed
     */
    protected function fetchLeads(): mixed
    {
        return $this->carAllocationService->fetchLeads();
    }

    /**
     * @param $lead
     * @return Tier
     */
    protected function findTier($lead): Tier
    {
        if ($lead->tier_id == null) {
            return $this->carAllocationService->findTier($lead);
        }

        return $this->carAllocationService->getTierById($lead->tier_id);
    }

    /**
     * @param $tierId
     * @return array
     */
    protected function findAvailableUsers($tierId): array
    {
        return $this->carAllocationService->getEligibleUsersForAllocation($tierId);
    }

    /**
     * @param $lead
     * @return array
     */
    protected function findRules($lead): array
    {
       return $this->carAllocationService->getRules($lead);
    }

    /**
     * @param $lead
     * @param $tier
     * @param $users
     * @param $rules
     * @return int
     */
    protected function finalizeAdvisors($lead, $tier, $users, $rules): int
    {
        return $this->carAllocationService->determineFinalUserId($lead, $users, $rules);
    }

    /**
     * @param $lead
     * @param $userId
     * @param $tier
     * @return void
     */
    protected function assignLeadAndSendEmail($lead, $userId, $tier): void
    {
        $this->carAllocationService->processLeadAssignmentAndSendEmail($lead, $userId, $tier);
    }

    /**
     * @param $lead
     * @param $tier
     * @return void
     */
    private function updateLeadTier($lead, $tier): void
    {
        $this->carAllocationService->updateLeadTier($lead, $tier);
    }
}

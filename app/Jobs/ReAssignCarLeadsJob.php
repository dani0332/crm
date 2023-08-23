<?php

namespace App\Jobs;

use App\Enums\AssignmentTypeEnum;
use App\Models\Tier;
use App\Services\CarAllocationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReAssignCarLeadsJob extends LeadAllocationJobInterface
{
    protected CarAllocationService $carAllocationService;
    protected $advisorId;

    public function __construct(CarAllocationService $carAllocationService, $advisorId)
    {
        $this->carAllocationService = $carAllocationService;
        $this->advisorId = $advisorId;
    }

    public function handle()
    {
        if (! $this->shouldProceed()) {
            return false;
        }
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
                    DB::beginTransaction();
                    try {
                        // Assign the lead to the advisor and send an email
                        $this->assignLead($lead, $advisorId, $tier);
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollback();
                        Log::error($e->getMessage());
                    }
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

    protected function shouldProceed(): bool
    {
        return $this->carAllocationService->shouldProceed();
    }

    protected function fetchLeads(): mixed
    {
        return $this->carAllocationService->fetchLeadsForReAssignment($this->advisorId);
    }

    protected function findTier($lead): Tier
    {
        if ($lead->tier_id == null) {
            return $this->carAllocationService->findTier($lead);
        }

        return $this->carAllocationService->getTierById($lead->tier_id);
    }

    protected function findAvailableUsers($tierId): array
    {
        return $this->carAllocationService->getEligibleUsersForAllocation($tierId, $this->advisorId);
    }

    protected function findRules($lead): array
    {
        return $this->carAllocationService->getRules($lead);
    }

    protected function finalizeAdvisors($lead, $tier, $users, $rules): int
    {
        return $this->carAllocationService->determineFinalUserId($lead, $users, $rules);
    }

    protected function assignLead($lead, $userId, $tier): void
    {
        $this->carAllocationService->processLeadAssignment($lead, $userId, $tier, AssignmentTypeEnum::SYSTEM_REASSIGNED);
    }

    private function updateLeadTier($lead, $tier): void
    {
        $this->carAllocationService->updateLeadTier($lead, $tier);
    }
}

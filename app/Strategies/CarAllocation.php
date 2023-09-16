<?php

namespace App\Strategies;

use App\Enums\AssignmentTypeEnum;
use App\Models\Tier;
use App\Services\CarAllocationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CarAllocation implements Allocation
{
    private $carAllocationService;
    private $allocationId;

    public function __construct(CarAllocationService $carAllocationService, $allocationId)
    {
        $this->carAllocationService = $carAllocationService;
        $this->allocationId = $allocationId;
    }

    public function executeSteps()
    {
        try {
            // Fetch the lead to process
            $lead = $this->fetchLead();

            if (! $lead) {
                info('Lead not found or either was not under assignment criteria');

                return false; // when lead is not on criteria or not found
            }

            // Find the appropriate tier for the lead
            $tier = $lead->tier_id != null ? $this->getTier($lead->tier_id) : $this->findTier($lead);

            // If a valid tier is found
            if ($tier) {
                // Find available users for the tier
                $availableUsers = $this->findAvailableUsers($tier->id);

                // Find custom rules for the lead
                $rules = $this->findRules($lead);

                // Determine the final advisor for the lead based on tier, users, and rules
                $advisorId = $this->finalizeAdvisors($lead, $tier, $availableUsers, $rules);

                if ($advisorId && $advisorId != 0) {
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
        } catch (\Throwable $th) {
            info('exception occurred in car lead allocation with error : '.$th->getMessage());
            info('exception occurred in car lead allocation with error stack as  : '.$th->getTraceAsString());

            return false;
        }
    }

    protected function fetchLead(): mixed
    {
        return $this->carAllocationService->fetchLead($this->allocationId);
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

    protected function findAvailableUsers($tierId): array|Collection
    {
        return $this->carAllocationService->getEligibleUserForAllocation($tierId);
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
        $this->carAllocationService->processLeadAssignment($lead, $userId, $tier, AssignmentTypeEnum::SYSTEM_ASSIGNED);
    }

    private function updateLeadTier($lead, $tier): void
    {
        $this->carAllocationService->updateLeadTier($lead, $tier);
    }
}

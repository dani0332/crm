<?php

namespace App\Jobs;

use App\Enums\AssignmentTypeEnum;
use App\Models\Tier;
use App\Services\BikeAllocationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReAssignBikeLeadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 2;
    public $timeout = 15;
    public $backoff = 30;
    private $bikeAllocationService;
    private $advisorId;

    /**
     * Create a new job instance.
     */
    public function __construct(BikeAllocationService $bikeAllocationService, $advisorId)
    {
        $this->bikeAllocationService = $bikeAllocationService;
        $this->advisorId = $advisorId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        info('-------- Reassignment bike job started at : '.now().' ---------');
        if (! $this->shouldProceed() && ! now()->isWeekend()) {
            info('Reassignment job is not proceeding as per business timings');

            return false;
        }
        $leads = $this->fetchLeads();
        if (count($leads) == 0) {
            info('No bike lead found or either lead is not under assignment criteria');
            info('-------- Reassignment bike job ended at : '.now().' ---------');

            return false; // when lead is not on criteria or not found
        }
        foreach ($leads as $lead) {
            info('--------------- ReAssignment processing current lead : '.$lead->uuid.' ---------------');

            if ($lead->isAllocationInProgress()) {
                info("Allocation is already started for lead: {$lead->uuid} at {$lead->allocation_started_at}");

                continue;
            }

            $lead->startAllocation();

            // Find the appropriate tier for the lead
            $tier = $this->findTier($lead);

            // If a valid tier is found
            if ($tier) {
                // Find available users for the tier
                $availableUsers = $this->findAvailableUsers($tier->id, $lead->source);

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

            $lead->endAllocation();

            info('--------------- ReAssignment processing ended for current lead : '.$lead->uuid.' ---------------');
        }
        info('-------- Reassignment bike job ended at : '.now().' ---------');
    }

    protected function shouldProceed(): bool
    {
        return $this->bikeAllocationService->shouldProceed();
    }

    protected function fetchLeads(): mixed
    {
        return $this->bikeAllocationService->fetchLeadsForReAssignment($this->advisorId);
    }

    protected function findTier($lead): Tier
    {
        if ($lead->tier_id == null) {
            return $this->bikeAllocationService->findTier($lead);
        }

        return $this->bikeAllocationService->getTierById($lead->tier_id);
    }

    protected function findAvailableUsers($tierId, $leadSource)
    {
        return $this->bikeAllocationService->getEligibleUserForAllocation($tierId, $this->advisorId, true, $leadSource, null);
    }

    protected function findRules($lead)
    {
        return $this->bikeAllocationService->getRules($lead);
    }

    protected function finalizeAdvisors($lead, $tier, $users, $rules)
    {
        return $this->bikeAllocationService->determineFinalUserId($lead, $users, $rules, null);
    }

    protected function assignLead($lead, $userId, $tier)
    {
        $this->bikeAllocationService->processLeadAssignment($lead, $userId, $tier, AssignmentTypeEnum::SYSTEM_REASSIGNED);
    }

    private function updateLeadTier($lead, $tier)
    {
        $this->bikeAllocationService->updateLeadTier($lead, $tier);
    }

    public function middleware()
    {
        // Lock Job in storage session only if advisor id is not zero
        if ($this->advisorId) {
            return [(new WithoutOverlapping($this->advisorId))->dontRelease()];
        }

        return [];
    }
}

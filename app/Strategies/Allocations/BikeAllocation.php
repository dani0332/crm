<?php

namespace App\Strategies\Allocations;

use App\Enums\AssignmentTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\Tier;
use App\Services\BikeAllocationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;

class BikeAllocation implements Allocation
{
    private $bikeAllocationService;
    private $allocationId;
    private bool $overrideAdvisorId = false;

    public function __construct(BikeAllocationService $bikeAllocationService, $allocationId, bool $overrideAdvisorId = false)
    {
        $this->bikeAllocationService = $bikeAllocationService;
        $this->allocationId = $allocationId;
        $this->overrideAdvisorId = $overrideAdvisorId;
    }

    public function executeSteps()
    {
        $response = $this->bikeAllocationService->createResponse(0, '', Response::HTTP_INTERNAL_SERVER_ERROR);

        try {
            info('Bike Allocation Started.');

            // Fetch the lead to process
            $lead = $this->fetchLead();

            if (! $lead) {
                info('Lead not found or not under fetch criteria for allocation id: '.$this->allocationId.' in BIKE allocation');

                $response = $this->bikeAllocationService->createResponse(0, 'Lead not found or not under fetch criteria', Response::HTTP_NOT_FOUND);
            } else {
                if ($lead->isAllocationInProgress()) {
                    info("Allocation is already started for lead: {$lead->uuid} at {$lead->allocation_started_at}");

                    return $this->bikeAllocationService->createResponse(0, 'Allocation is in progress', Response::HTTP_OK);
                }

                $lead->startAllocation();

                // Find the appropriate tier for the lead
                $tier = $lead->tier_id != null ? $this->getTier($lead->tier_id) : $this->findTier($lead);

                // If a valid tier is found
                if ($tier) {
                    info('Tier finalized for lead : '.$lead->uuid.' is : '.$tier->name);
                    // Find available users for the tier
                    $availableUsers = $this->findAvailableUsers($tier->id, $lead->bikeQuote->source);

                    // Find custom rules for the lead
                    $rules = $this->findRules($lead);
                    // Determine the final advisor for the lead based on tier, users, and rules
                    $advisorId = $this->finalizeAdvisors($lead, $availableUsers, $rules);

                    if (! empty($advisorId) && $advisorId == $lead->advisor_id) {
                        info('Advisor is same as previous advisor. Skipping for now.');
                        $lead->endAllocation();
                        $response = $this->bikeAllocationService->createResponse($advisorId, 'Advisor is same as previous advisor. Skipping for now', Response::HTTP_OK);
                    } elseif ($advisorId && $advisorId != 0) {
                        $this->assignLead($lead, $advisorId, $tier);
                        $lead->endAllocation();
                        $response = $this->bikeAllocationService->createResponse($advisorId, 'Advisor assigned successfully!', Response::HTTP_OK);
                    } else {
                        $this->bikeAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::BIKE);

                        info('Advisor not found. Skipping for now.');
                        // Update the lead's tier information
                        $this->updateLeadTier($lead, $tier);
                        $response = $this->bikeAllocationService->createResponse(0, 'Advisor not found', Response::HTTP_NOT_FOUND);
                    }
                } else {
                    // Log that tier was not found for the lead and skip processing
                    info('Tier not found for lead: '.$lead->uuid.'. Skipping for now.');
                    $lead->endAllocation();
                    $response = $this->bikeAllocationService->createResponse(0, 'Tier not found', Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }
            info('Bike Allocation Ended.');
        } catch (\Throwable $th) {
            $this->bikeAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::BIKE);

            $message = $th->getMessage() ?? '';
            info('exception occurred in bike lead allocation with error : '.$message);
            info('exception occurred in bike lead allocation with error stack as  : '.$th->getTraceAsString());
            $response = $this->bikeAllocationService->createResponse(0, 'exception occurred in bike lead allocation with error : '.$message, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $response;
    }

    protected function fetchLead(): mixed
    {
        return $this->bikeAllocationService->fetchLead($this->allocationId, $this->overrideAdvisorId);
    }

    protected function getTier($tierId)
    {
        return $this->bikeAllocationService->getTier($tierId);
    }

    protected function findTier($lead): ?Tier
    {
        if ($lead->tier_id == null) {
            return $this->bikeAllocationService->findTier($lead);
        }

        return $this->bikeAllocationService->getTierById($lead->tier_id);
    }

    protected function findAvailableUsers($tierId, $leadSource): array|Collection
    {
        return $this->bikeAllocationService->getEligibleUserForAllocation($tierId, null, false, $leadSource);
    }

    protected function findRules($lead)
    {
        return $this->bikeAllocationService->getRules($lead);
    }

    protected function finalizeAdvisors($lead, $users, $rules): int
    {
        return $this->bikeAllocationService->determineFinalUserId($lead, $users, $rules);
    }

    protected function assignLead($lead, $userId, $tier): void
    {
        $this->bikeAllocationService->processLeadAssignment($lead, $userId, $tier, AssignmentTypeEnum::SYSTEM_ASSIGNED);
    }

    private function updateLeadTier($lead, $tier): void
    {
        $this->bikeAllocationService->updateLeadTier($lead, $tier);
    }
}

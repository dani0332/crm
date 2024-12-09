<?php

namespace App\Strategies\Allocations;

use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Factories\AllocationFactory;
use App\Models\Tier;
use App\Services\CarAllocationService;
use App\Services\SendEmailCustomerService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;

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
        $response = [
            'advisorId' => 0,
            'message' => '',
            'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ];

        try {
            $lead = $this->fetchLead($overrideAdvisorId);

            if (! $lead) {
                info('Lead not found or not under fetch criteria for allocation id: '.$this->allocationId);

                return AllocationFactory::createResponse(0, 'Lead not found or not under fetch criteria', Response::HTTP_NOT_FOUND);
            }

            $tier = $this->determineTier($lead);

            if ($tier) {
                $response = $this->processTier($lead, $tier, $evaluateTierOnly);
            } else {
                info('Tier not found for lead: '.$lead->uuid.'. Skipping for now.');

                $this->carAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::CAR);

                $response = AllocationFactory::createResponse(0, 'Tier not found', Response::HTTP_NOT_FOUND);
            }
        } catch (\Throwable $th) {
            $this->carAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::CAR);

            $message = $th->getMessage() ?? '';
            info('exception occurred in car lead allocation with error : '.$message);
            info('exception occurred in car lead allocation with error stack as  : '.$th->getTraceAsString());
            $response = AllocationFactory::createResponse(0, 'exception occurred in car lead allocation with error : '.$message, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $response;
    }

    private function determineTier($lead)
    {
        $tier = $lead->tier_id != null ? $this->getTier($lead->tier_id) : $this->findTier($lead);

        if ($tier) {
            info('check the lead and identify if the tier update is required : '.$lead->uuid);
            $updatedTierId = $this->carAllocationService->updateTierBeforeEligibleUserIdentification($lead);

            if (! empty($updatedTierId) && $updatedTierId != $lead->tier_id) {
                $lead->tier_id = $updatedTierId;
                $lead->save();
                $tier = $this->getTier($updatedTierId);
            }
        }

        return $tier;
    }

    private function processTier($lead, $tier, $evaluateTierOnly)
    {
        info('Tier identified. Proceeding to finalize the tier for lead : '.$lead->uuid.' with UUID : '.$lead->uuid.' and tier name : '.$tier->name);

        if ($evaluateTierOnly) {
            info('Evaluate tier only. Tier finalized for lead : '.$lead->uuid.' is : '.$tier->name);
            $lead->tier_id = $tier->id;
            $lead->save();

            return AllocationFactory::createResponse(0, 'Tier evaluated successfully!', Response::HTTP_OK, $tier->id);
        }

        info('Tier finalized for lead : '.$lead->uuid.' is : '.$tier->name);
        $availableUsers = $this->findAvailableUsers($tier, $lead->source, $lead);
        $rules = $this->findRules($lead);
        $advisorId = $this->finalizeAdvisors($lead, $tier, $availableUsers, $rules);

        if (! empty($advisorId) && $advisorId == $lead->advisor_id) {
            info('Advisor is same as previous advisor. Skipping for now.');

            return AllocationFactory::createResponse($advisorId, 'Advisor is same as previous advisor. Skipping for now', Response::HTTP_OK);
        }

        if ($advisorId && $advisorId != 0) {
            $this->assignLead($lead, $advisorId, $tier);

            if ($lead->source != LeadSourceEnum::RENEWAL_UPLOAD) {
                app(SendEmailCustomerService::class)->sendWhatsappNotificationToCustomer($lead, $advisorId);
            }

            return AllocationFactory::createResponse($advisorId, 'Advisor assigned successfully!', Response::HTTP_OK);
        } else {
            $this->carAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::CAR);

            info('Advisor not found. Skipping for now.');
            $this->updateLeadTier($lead, $tier);

            return AllocationFactory::createResponse(0, 'Advisor not found', Response::HTTP_NOT_FOUND);
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

    protected function findAvailableUsers($tier, $leadSource, $lead): array|Collection
    {
        return $this->carAllocationService->getEligibleUserForAllocation($tier, null, false, $leadSource, $this->teamId, $lead);
    }

    protected function findRules($lead)
    {
        return $this->carAllocationService->getRules($lead);
    }

    protected function finalizeAdvisors($lead, $tier, $users, $rules): int
    {
        return $this->carAllocationService->determineFinalUserId($lead, $users, $rules, $this->teamId, $tier);
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

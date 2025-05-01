<?php

namespace App\Strategies\Allocations;

use App\Enums\AssignmentTypeEnum;
use App\Enums\CarRegistrationType;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Enums\TeamNameEnum;
use App\Enums\TiersEnum;
use App\Models\CarQuote;
use App\Models\Tier;
use App\Services\CarAllocationService;
use App\Services\Logger\LoggerService;
use App\Services\SendEmailCustomerService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;

class CarAllocation implements Allocation
{
    private $carAllocationService;
    private $allocationId;
    private $teamId;
    private bool $evaluateTierOnly = false;
    private bool $overrideAdvisorId = false;
    private bool $sicAdvisorRequested = false;

    public function __construct(CarAllocationService $carAllocationService, $allocationId, $teamId, bool $evaluateTierOnly = false, bool $overrideAdvisorId = false, bool $sicAdvisorRequested = false)
    {
        $this->carAllocationService = $carAllocationService;
        $this->allocationId = $allocationId;
        $this->teamId = $teamId;
        $this->evaluateTierOnly = $evaluateTierOnly;
        $this->overrideAdvisorId = $overrideAdvisorId;
        $this->sicAdvisorRequested = $sicAdvisorRequested;
    }

    public function executeSteps()
    {
        $response = [
            'advisorId' => 0,
            'message' => '',
            'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ];

        try {
            $lead = $this->fetchLead();

            if (! $lead) {
                LoggerService::info('Lead not found or not under fetch criteria');

                return $this->carAllocationService->createResponse(0, 'Lead not found or not under fetch criteria', Response::HTTP_NOT_FOUND);
            }

            LoggerService::debug('Processing record for Quote Allocation', extra: [
                'payment_status_id' => $lead->payment_status_id,
                'source' => $lead->source,
                'is_renewal_tier_email_sent' => $lead->is_renewal_tier_email_sent,
                'lead_allocation_failed_at' => $lead->lead_allocation_failed_at,
                'sic_flow_enabled' => $lead->sic_flow_enabled,
                'sic_advisor_requested' => $lead->sic_advisor_requested,
                'quote_status_id' => $lead->quote_status_id,
            ]);

            if ($lead->isAllocationInProgress()) {
                LoggerService::warning("Allocation is already started at {$lead->allocation_started_at}");

                return $this->carAllocationService->createResponse(0, 'Allocation is in progress', Response::HTTP_OK);
            }

            if (isLeadSic($lead->uuid) && $lead->registration_type == CarRegistrationType::COMPANY) {
                info(self::class." - Lead is SIC and Registration type Company Webform. Skipping allocation for Ref-ID: {$lead->uuid} | Time: ".now());
                $this->carAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::CAR);

                return $this->carAllocationService->createResponse(0, 'Lead is SIC and the registration type is Company. Skipping allocation.', Response::HTTP_OK);
            }

            $lead->startAllocation();

            $tier = $this->determineTier($lead);

            if ($tier->name == TiersEnum::TIER_R) {
                info(self::class." - Lead is Tier R and from Company Webform. Skipping allocation for Ref-ID: {$lead->uuid} | Time: ".now());

                $this->carAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::CAR);

                return $this->carAllocationService->createResponse(0, 'Lead is Tier R Skipping allocation.', Response::HTTP_OK);
            }

            if ($tier) {
                $response = $this->processTier($lead, $tier);
            } else {
                LoggerService::info('Tier not found. Skipping for now.');

                $this->carAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::CAR);

                $response = $this->carAllocationService->createResponse(0, 'Tier not found', Response::HTTP_OK);
            }
        } catch (Exception $th) {
            $this->carAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::CAR);
            $this->carAllocationService->endBuyLeadProcessing();

            $message = $th->getMessage() ?? '';
            LoggerService::error('exception occurred in car lead allocation', exception: $th);
            $response = $this->carAllocationService->createResponse(0, "exception occurred in car lead allocation with error : {$message}", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $response;
    }

    private function determineTier($lead)
    {
        $tier = $lead->tier_id != null ? $this->getTier($lead->tier_id) : $this->findTier($lead);

        if ($tier) {
            LoggerService::info('check the lead and identify if the tier update is required');
            $updatedTierId = $this->carAllocationService->updateTierBeforeEligibleUserIdentification($lead);

            if (! empty($updatedTierId) && $updatedTierId != $lead->tier_id) {
                $lead->tier_id = $updatedTierId;
                $lead->save();
                $tier = $this->getTier($updatedTierId);
            }
        }

        return $tier;
    }

    private function evaluateTeamId(CarQuote $lead)
    {
        if ($this->teamId && $lead->isSIC(QuoteTypes::CAR) && $lead->isPUA()) {
            $sicTeamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
            if ($sicTeamId == $this->teamId) {
                $this->teamId = getTeamId(TeamNameEnum::ORGANIC);
            }
        }

        $this->isAIGLeadEligibleForAllocation($lead);
    }

    private function isAIGLeadEligibleForAllocation(CarQuote $lead): void
    {
        if ($lead->isAIG(QuoteTypes::CAR) && empty($this->teamId) && $this->sicAdvisorRequested) {
            $this->teamId = getTeamId(TeamNameEnum::ORGANIC);
            LoggerService::info('AIG lead detected with SIC advisor requested. Assigning to Organic team.');
        }
    }

    private function processTier($lead, $tier)
    {
        LoggerService::info("Tier identified. Proceeding to finalize the tier and tier name : {$tier->name}");

        if ($this->evaluateTierOnly) {
            $lead->tier_id = $tier->id;
            $lead->save();

            $lead->endAllocation();

            return $this->carAllocationService->createResponse(0, 'Tier evaluated successfully!', Response::HTTP_OK, $tier);
        }

        $this->evaluateTeamId($lead);

        $availableUsers = $this->findAvailableUsers($tier, $lead->source, $lead);
        $rules = $this->findRules($lead);
        $advisorId = $this->finalizeAdvisors($lead, $tier, $availableUsers, $rules);

        if (! empty($advisorId) && $advisorId == $lead->advisor_id) {
            LoggerService::info('Advisor is same as previous advisor. Skipping for now.');

            $lead->endAllocation();
            $this->carAllocationService->endBuyLeadProcessing();

            return $this->carAllocationService->createResponse($advisorId, 'Advisor is same as previous advisor. Skipping for now', Response::HTTP_OK);
        }

        if ($advisorId && $advisorId != 0) {

            $this->assignLead($lead, $advisorId, $tier);

            if ($lead->source != LeadSourceEnum::RENEWAL_UPLOAD) {
                app(SendEmailCustomerService::class)->sendWhatsappNotificationToCustomer($lead, $advisorId);
            }

            return $this->carAllocationService->createResponse($advisorId, 'Advisor assigned successfully!', Response::HTTP_OK);
        } else {
            $this->carAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::CAR);
            $this->carAllocationService->endBuyLeadProcessing();

            LoggerService::info('Advisor not found. Skipping for now.');
            $this->updateLeadTier($lead, $tier);

            return $this->carAllocationService->createResponse(0, 'Advisor not found', Response::HTTP_OK);
        }
    }

    protected function fetchLead(): mixed
    {
        return $this->carAllocationService->fetchLead($this->allocationId, $this->overrideAdvisorId, $this->evaluateTierOnly);
    }

    protected function getTier($tierId)
    {
        return $this->carAllocationService->getTier($tierId);
    }

    protected function findTier($lead): ?Tier
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

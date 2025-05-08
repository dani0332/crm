<?php

namespace App\Strategies\Allocations;

use App\Enums\AssignmentTypeEnum;
use App\Enums\ProcessTracker\StepsEnums\ProcessTrackerAllocationEnum;
use App\Enums\QuoteTypes;
use App\Models\TravelQuote;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Services\ProcessTracker\ProcessTrackerService;
use App\Services\TravelAllocationService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Enums\TeamNameEnum;

class TravelAllocation implements Allocation
{
    public $travelAllocationService;
    public $allocationId;
    public $teamId;
    public $tracker;
    private bool $overrideAdvisorId = false;

    public function __construct(TravelAllocationService $travelAllocationService, ProcessTrackerService $tracker, $allocationId, $teamId = false, bool $overrideAdvisorId = false)
    {
        $this->travelAllocationService = $travelAllocationService;
        $this->allocationId = $allocationId;
        $this->teamId = $teamId;
        $this->tracker = $tracker;
        $this->overrideAdvisorId = $overrideAdvisorId;
    }

    public function executeSteps()
    {
        $this->travelAllocationService->resetProps();

        $response = [
            'advisorId' => 0,
            'message' => '',
            'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ];

        try {
            LoggerService::info(self::class." - executeSteps: Travel Allocation started for allocation id : {$this->allocationId}");
            $lead = $this->fetchLead();

            if (! $lead) {
                LoggerService::info(self::class.' - executeSteps: Lead not found');

                $this->tracker->saveResult(ProcessTrackerAllocationEnum::LEAD_NOT_FOUND, [
                    '@statuses' => ['Fake', 'Duplicate', 'Lost'],
                ]);

                $response = $this->travelAllocationService->createResponse(0, 'Lead not found or not under fetch criteria', Response::HTTP_NOT_FOUND);
            } else {
                LoggerService::debug('Processing record for Travel Allocation', extra: [
                    'payment_status_id' => $lead->payment_status_id,
                    'sic_flow_enabled' => $lead->sic_flow_enabled,
                    'sic_advisor_requested' => $lead->sic_advisor_requested,
                    'quote_status_id' => $lead->quote_status_id,
                ]);
                
                if ($lead->isAllocationInProgress()) {
                    LoggerService::info("Allocation is already started at {$lead->allocation_started_at}");

                    return $this->travelAllocationService->createResponse(0, 'Allocation is in progress', Response::HTTP_OK);
                }

                $lead->startAllocation();
                
                // Evaluate team ID before fetching advisor
                $this->evaluateTeamId($lead);

                $advisor = $this->fetchAvailableAdvisor($lead);

                if (! $advisor) {
                    $this->travelAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::TRAVEL);

                    LoggerService::info(self::class.' - executeSteps: No advisor found');

                    $response = $this->travelAllocationService->createResponse(0, 'Advisor not found', Response::HTTP_NOT_FOUND);

                    $this->tracker->saveResult(ProcessTrackerAllocationEnum::ADVISOR_NOT_FOUND, ignoreStep: true);
                } else {
                    $this->assignLead($lead, $advisor); // Assign the lead to the advisor
                    $lead->endAllocation();
                    $response = $this->travelAllocationService->createResponse($advisor->id, 'Advisor assigned successfully!', Response::HTTP_OK);
                }
            }
        } catch (\Throwable $th) {
            $this->travelAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::TRAVEL);

            $message = $th->getMessage() ?? '';
            LoggerService::error('exception occurred in travel lead allocation with error : '.$message);
            LoggerService::error('exception occurred in travel lead allocation with error stack as  : '.$th->getTraceAsString());
            $response = $this->travelAllocationService->createResponse(0, 'exception occurred in travel lead allocation with error : '.$message, Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->tracker->saveResult(ProcessTrackerAllocationEnum::EXCEPTION_RAISED, summary: "Exception Occurred in Lead Allocation with error : {$message}");
        }

        $this->travelAllocationService->resetProps();

        return $response;
    }

    private function fetchLead()
    {
        return $this->travelAllocationService->fetchLead($this->tracker, $this->allocationId, $this->overrideAdvisorId);
    }

    private function fetchAvailableAdvisor(TravelQuote $lead)
    {
        return $this->travelAllocationService->fetchAvailableAdvisor(teamId: $this->teamId, lead: $lead, tracker: $this->tracker);
    }

    private function assignLead(TravelQuote $lead, User $advisor)
    {
        DB::beginTransaction();
        try {
            $this->travelAllocationService->assignLead($lead, $advisor, AssignmentTypeEnum::SYSTEM_ASSIGNED, tracker: $this->tracker);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            LoggerService::error($e->getMessage());

            $this->tracker->saveResult(ProcessTrackerAllocationEnum::EXCEPTION_RAISED, summary: "Assign Lead Failed with error : {$e->getMessage()}");
        }
    }

    private function evaluateTeamId(TravelQuote $lead)
    {
        if (!$lead || !$lead->uuid) {
            LoggerService::warning('Cannot evaluate team ID - invalid lead', extra: [
                'allocationId' => $this->allocationId
            ]);
            return;
        }
        
        // Extract lead properties with null safety
        $isSIC = method_exists($lead, 'isSIC') ? $lead->isSIC(QuoteTypes::TRAVEL) : false;
        $isAIG = method_exists($lead, 'isAIG') ? $lead->isAIG(QuoteTypes::TRAVEL) : false;
        $hasPaidStatus = method_exists($lead, 'hasOneOfPaidStatus') ? $lead->hasOneOfPaidStatus() : false;
        $isAdvisorRequested = isset($lead->sic_advisor_requested) ? (bool)$lead->sic_advisor_requested : false;
        $sicUnassistedTeamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
        
        // Determine team based on priority rules
        if ($isSIC && !$isAIG && $hasPaidStatus) {
            // Priority 1: SIC paid leads get SIC Unassisted team
            $this->teamId = $sicUnassistedTeamId;
            $reason = "SIC paid lead";
        }
        elseif ($isSIC && !$isAIG && $isAdvisorRequested) {
            // Priority 2: SIC leads with advisor requested get SIC Unassisted team
            $this->teamId = $sicUnassistedTeamId;
            $reason = "SIC with advisor requested";
        }
        elseif ($isAIG && $isAdvisorRequested) {
            // Priority 3: AIG leads with advisor requested get SIC Unassisted team
            $this->teamId = $sicUnassistedTeamId;
            $reason = "AIG with advisor requested";
        }
        else {
            // Default: All other leads have no specific team
            $this->teamId = false;
            $reason = "Default case - no specific team";
        }
        
        // Log the final team assignment using debug with extra parameter
        LoggerService::debug('Team assigned for Travel Allocation', extra: [
            'reason' => $reason,
            'teamId' => $this->teamId,
            'isSIC' => $isSIC,
            'isAIG' => $isAIG,
            'hasPaidStatus' => $hasPaidStatus,
            'isAdvisorRequested' => $isAdvisorRequested,
        ]);
    }
}

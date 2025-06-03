<?php

namespace App\Strategies\Allocations;

use App\Enums\AssignmentTypeEnum;
use App\Enums\ProcessTracker\StepsEnums\ProcessTrackerAllocationEnum;
use App\Enums\QuoteTypes;
use App\Enums\TeamNameEnum;
use App\Models\TravelQuote;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Services\ProcessTracker\ProcessTrackerService;
use App\Services\TravelAllocationService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TravelAllocation implements Allocation
{
    public $travelAllocationService;
    public $allocationId;
    public $teamId;
    public $tracker;
    private bool $overrideAdvisorId = false;

    // Default team ID (no specific team assignment) for travel team
    private const DEFAULT_TEAM_ID = false;

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

    /**
     * Evaluates and sets the appropriate team ID for the travel quote lead
     * based on business rules and lead properties.
     *
     * @param  TravelQuote  $lead  The lead to evaluate
     */
    private function evaluateTeamId(TravelQuote $lead): void
    {
        if (! $lead || ! $lead->uuid) {
            LoggerService::warning('Cannot evaluate team ID - invalid lead', extra: [
                'allocationId' => $this->allocationId,
            ]);

            return;
        }

        // Extract lead properties with null safety
        $isSIC = $this->checkLeadMethod($lead, 'isSIC', [QuoteTypes::TRAVEL]);
        $isAIG = $this->checkLeadMethod($lead, 'isAIG', [QuoteTypes::TRAVEL]);
        $isPaymentAuthorizedOrLinkRequested = $this->checkLeadMethod($lead, 'isPaymentAuthorizedOrLinkRequested');
        $isLeadFromInstantAlfred = $this->checkLeadMethod($lead, 'isLeadFromInstantAlfred');

        $sicUnassistedTeamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);

        // Determine team assignment based on business rules
        $isAIGWithInstantAlfred = $isAIG && $isLeadFromInstantAlfred;
        $isSICOrAIGWithPayment = (($isSIC && ! $isAIG) || $isAIG) && $isPaymentAuthorizedOrLinkRequested;
        $isNonSICNonAIGWithPayment = (! $isSIC && ! $isAIG) && $isPaymentAuthorizedOrLinkRequested;

        // Apply team assignment rules
        if ($isAIGWithInstantAlfred) {
            // Rule 1: AIG leads from Instant Alfred go to default team
            $this->teamId = self::DEFAULT_TEAM_ID;
            $reason = 'AIG and Lead from Instant Alfred';
        } elseif ($isSICOrAIGWithPayment) {
            // Rule 2: SIC or AIG leads with payment authorized or link requested
            $this->teamId = $sicUnassistedTeamId;
            $reason = $isAIG ? 'AIG with payment authorized or link requested' :
                              'SIC with payment authorized or link requested';
        } elseif ($isNonSICNonAIGWithPayment) {
            // Rule 3: Non-SIC, Non-AIG leads with payment authorized or link requested
            $this->teamId = $sicUnassistedTeamId;
            $reason = 'Non-SIC, Non-AIG lead with payment authorized or link requested';
        } else {
            // Rule 4: Default - all other leads have no specific team
            $this->teamId = self::DEFAULT_TEAM_ID;
            $reason = 'Default case - no specific team';
        }

        // Log the final team assignment using debug with extra parameter
        LoggerService::debug('Team assigned for Travel Allocation', extra: [
            'reason' => $reason,
            'teamId' => $this->teamId,
            'isSIC' => $isSIC,
            'isAIG' => $isAIG,
            'isPaymentAuthorizedOrLinkRequested' => $isPaymentAuthorizedOrLinkRequested,
            'isLeadFromInstantAlfred' => $isLeadFromInstantAlfred,
        ]);
    }

    /**
     * Helper method to safely check if a method exists and call it with parameters
     *
     * @param  TravelQuote  $lead  The lead object
     * @param  string  $methodName  The method name to check and call
     * @param  array  $params  Optional parameters to pass to the method
     * @return bool The result of the method call or false if method doesn't exist
     */
    private function checkLeadMethod(TravelQuote $lead, string $methodName, array $params = []): bool
    {
        if (! method_exists($lead, $methodName)) {
            return false;
        }

        return $lead->{$methodName}(...$params);
    }
}

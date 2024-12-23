<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\UserStatusEnum;
use App\Models\QuoteBatches;
use App\Models\Team;
use App\Models\TravelQuote;
use App\Models\TravelQuoteRequestDetail;
use App\Models\User;
use App\Repositories\PaymentRepository;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Support\Facades\Log;

class TravelAllocationService extends AllocationService
{
    public const TYPE = quoteTypeCode::Travel;

    public bool $isCHSAdvisor = false;
    public bool $isSICAdvisor = false;
    public bool $isMixEnquiryWithAutomation = false;

    public function resetProps()
    {
        $this->isCHSAdvisor = false;
        $this->isSICAdvisor = false;
        $this->isMixEnquiryWithAutomation = false;
    }

    private function verifyFetchLeadPreChecks(TravelQuote $travelQuote, $quoteUUID)
    {
        // Run Alliance Check only when the travel quote is a parent lead and the members are adult
        if (getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_ALLIANCE_TRAVEL_POLICY_ISSUANCE) == '1' && $travelQuote->isParent() && $travelQuote->isAdult()) {
            info(self::class.":verifyFetchLeadPreChecks - {$quoteUUID} is parent lead so checking for Alliance Travel Automation");
            // Check if the lead is associated with the ALNC provider
            $payment = PaymentRepository::mainQuotePayment($travelQuote);
            $insurer = getInsuranceProvider($payment, QuoteTypes::TRAVEL->value);
            $insurerCode = $insurer?->code;

            $isALNC = $insurerCode == InsuranceProvidersEnum::ALNC;

            $isALNC && info(self::class.":verifyFetchLeadPreChecks - {$quoteUUID} is Alliance so checking for automation status with insurer code: {$insurerCode} and payment code: {$payment?->code}");

            $isAutomationEnabled = (new PolicyIssuanceService)->init(self::TYPE, $insurerCode)?->isPolicyIssuanceAutomationEnabled();
            info(self::class." - verifyFetchLeadPreChecks: {$quoteUUID} - isALNC: {$isALNC} - isAutomationEnabled: {$isAutomationEnabled}");

            if ($isALNC && $isAutomationEnabled && $travelQuote->isSingleTrip() && $travelQuote->isPaid()) {
                if ($travelQuote->isAutomationCompleted() || $travelQuote->isBookingFailed()) {
                    $travelQuote->isAutomationCompleted() && info(self::class.":fetchLead - {$quoteUUID} is Alliance and automation is completed so proceed with allocation");
                    $travelQuote->isBookingFailed() && info(self::class.":fetchLead - {$quoteUUID} is Alliance and booking failed so proceed with allocation");
                    $this->isCHSAdvisor = true;
                    $this->isMixEnquiryWithAutomation = $travelQuote->hasChild();
                } else {
                    if (! $travelQuote->isAutomationCompleted()) {
                        info(self::class.":fetchLead - {$quoteUUID} is Alliance and automation is not yet completed so check fail cases");
                        if ($travelQuote->isPolicyIssuanceFailed()) {
                            info(self::class.":fetchLead - {$quoteUUID} is Alliance and automation is not yet completed but policy issuance failed so proceed with allocation");
                            $this->isSICAdvisor = true;

                            return true;
                        }
                    }

                    return false;
                }
            }
        }

        return true;
    }

    public function fetchLead($quoteId, $overrideAdvisorId = false)
    {
        $travelQuote = TravelQuote::where('uuid', $quoteId)->first();

        // Return null if no record is found
        if (! $travelQuote) {
            info(self::class.' : '.__FUNCTION__.' - Quote ID : '.$quoteId.' - lead not found.');

            return null;
        }

        if ($this->verifyFetchLeadPreChecks($travelQuote, $quoteId) === false) {
            return null;
        }

        // allocate the lead if it's Alliance Provider, Automation is disabled for Alliance
        return TravelQuote::where('uuid', $quoteId)
            ->whereNotIn('quote_status_id', [
                QuoteStatusEnum::Fake,
                QuoteStatusEnum::Duplicate,
                QuoteStatusEnum::Lost,
            ])
            ->when(! $overrideAdvisorId, fn ($q) => $q->whereNull('advisor_id'))
            ->where(function ($query) {
                $query->sicFlowDisabled()
                    ->orWhere(function ($subQuery) {
                        $subQuery->sicFlowEnabled()->requestedAdvisorOrPaymentAuthorized();
                    });
            })
            ->first();
    }

    private function assignAvailableAdvisorToChild(TravelQuote $parentLead)
    {
        $this->resetProps();
        $lead = TravelQuote::where('parent_id', $parentLead->id)->first();
        if ($lead) {
            info(self::class.":assignAvailableAdvisorToChild - Finding Advisor for Child Lead: {$lead->uuid} of parent lead: {$parentLead->uuid}");
            $advisor = $this->fetchAvailableAdvisor(false, getTeamId(TeamNameEnum::SIC_UNASSISTED), $lead->uuid, $lead);
            if (! $advisor) {
                info(self::class.":assignAvailableAdvisorToChild - No Advisor found for Child Lead: {$lead->uuid} of parent lead: {$parentLead->uuid}");
                $this->leadAllocationFailed($lead->uuid, QuoteTypes::TRAVEL);
            } else {
                info(self::class.":assignAvailableAdvisorToChild - Advisor found for Child Lead: {$lead->uuid} of parent lead: {$parentLead->uuid}");
                $this->assignLead($lead, $advisor, AssignmentTypeEnum::SYSTEM_ASSIGNED);
            }
        }
    }

    public function fetchAvailableAdvisor($isReassignmentJob = false, $teamId = null, $quoteUUID = null, ?TravelQuote $lead = null)
    {
        Log::info(self::class." - fetchAvailableAdvisor: {$isReassignmentJob} - {$teamId} - {$quoteUUID}");

        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (! $isReassignmentJob) {
            $statusOrder[] = UserStatusEnum::UNAVAILABLE;
        }

        foreach ($statusOrder as $status) {
            info(self::class." - trying to get advisors with current status as {$status} for lead uuid: {$quoteUUID}");
            $eligibleUser = $this->getAdvisorByStatus($status, $teamId, $lead);

            if ($eligibleUser) {
                info(self::class." - eligible user found with status: {$status} and user id : {$eligibleUser->user_id} and uuid: {$quoteUUID}");

                return User::find($eligibleUser->user_id);
            }
        }

        return null;
    }

    public function getAdvisorByStatus($status, $teamId = null, ?TravelQuote $lead = null)
    {
        if ($this->isCHSAdvisor) {
            info(self::class." - getAdvisorByStatus: CHS Advisor is required for lead: {$lead->uuid}");

            return User::select('users.id as user_id')->chs()->first();
        }

        if ($this->isSICAdvisor) {
            info(self::class." - getAdvisorByStatus: SIC Advisor is required for lead: {$lead->uuid}");

            $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
        }

        $user = User::select('users.id as user_id')
            ->join('lead_allocation as la', 'la.user_id', '=', 'users.id')
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('users.status', $status)
            ->where(function ($query) {
                // Apply allocation count and max capacity conditions.
                $query->whereRaw('la.allocation_count < la.max_capacity')
                    ->orWhere('la.max_capacity', -1);
            })
            ->when($teamId, function ($q) use ($teamId) {
                $q->whereIn('users.id', fn ($query) => $query->select('user_id')->from('user_team')->where('team_id', $teamId));
            }, function ($q) {
                // if no team provided then user must not be part of SIC Unassisted 2.0 Team
                $sicUnassistedTeam = Team::where('name', TeamNameEnum::SIC_UNASSISTED)->first();
                if ($sicUnassistedTeam) {
                    $q->whereNotIn('users.id', fn ($query) => $query->select('user_id')->from('user_team')->where('team_id', $sicUnassistedTeam->id));
                }
            })
            ->whereIn('r.name', [RolesEnum::TravelAdvisor])
            ->where('la.quote_type_id', QuoteTypes::TRAVEL->id())
            ->activeUser()
            ->when($lead->isSIC(QuoteTypes::TRAVEL), function ($q) {
                $q->where('la.is_hardstop', true); // fetch users only with hardstop as true as they are eligible for allocation
            })
            ->orderBy('la.last_allocated', 'asc');
        info(self::class." - getAdvisorByStatus query: {$user->toSql()}, bindings: ".json_encode($user->getBindings()));

        return $user->first();
    }

    public function assignLead(TravelQuote $lead, User $advisor, $assignmentType)
    {
        info(self::class." - assignLead: Going to Assign Advisor to Lead: {$lead->uuid}");
        $previousAssignmentType = $lead->assignment_type;
        $previousUserId = $lead->advisor_id;
        $lead->advisor_id = $advisor->id;
        $lead->assignment_type = $assignmentType;
        $lead->quote_updated_at = now();
        $quoteBatch = QuoteBatches::latest()->first();
        $lead->quote_batch_id = $quoteBatch->id;
        $lead->save();
        info(self::class." - Lead Id {$lead->uuid} assigned to advisor : {$advisor->name} Quote Batch with ID: {$quoteBatch->id} and Name: {$quoteBatch->name}");

        $previousAdvisorAssignedDate = $this->updateQuoteDetail($lead->id);

        if ($lead->source != LeadSourceEnum::REFERRAL) {
            info(self::class.' - lead source is not referral so about to update allocation record');
            $assignmentType == AssignmentTypeEnum::SYSTEM_ASSIGNED ? $this->addAllocationCounts($advisor->id, QuoteTypes::TRAVEL->id()) : $this->adjustAllocationCounts($advisor->id, $lead, $previousUserId, $previousAdvisorAssignedDate, $previousAssignmentType, QuoteTypes::TRAVEL->id());
        }

        if ($this->isMixEnquiryWithAutomation) {
            info(self::class.":assignLead - Mix Enquiry with Automation so going to assign advisor to child lead for parent lead: {$lead->uuid}");
            $this->assignAvailableAdvisorToChild(parentLead: $lead);
        }
        $this->resetProps();
    }

    public function updateQuoteDetail($leadId)
    {
        info(self::class." - about to update travel quote detail record for : {$leadId}");

        $quoteDetail = TravelQuoteRequestDetail::where('travel_quote_request_id', $leadId)->first();
        $oldAdvisorAssignedDate = $quoteDetail->advisor_assigned_date ?? '';
        $this->upsertQuoteDetail($leadId, TravelQuoteRequestDetail::class, 'travel_quote_request_id');

        return $oldAdvisorAssignedDate;
    }
}

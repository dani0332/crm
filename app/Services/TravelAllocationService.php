<?php

namespace App\Services;

use App\Enums\AssignmentTypeEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
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
use Illuminate\Support\Facades\Log;

class TravelAllocationService extends AllocationService
{
    public function fetchLead($quoteId, $overrideAdvisorId = false)
    {
        $travelQuote = TravelQuote::where('uuid', $quoteId)->first();

        // Return null if no record is found
        if (! $travelQuote) {
            info(self::class.' : '.__FUNCTION__.' - Quote ID : '.$quoteId.' - lead not found.');

            return null;
        }

        // Run Alliance Check only when the travel quote is a parent lead
        if ($travelQuote->isParentLead()) {
            info(self::class.' : '.__FUNCTION__.' - Quote ID : '.$quoteId.' - isParentLead : '.$travelQuote->isParentLead());

            // Check if the lead is associated with the ALNC provider
            $payment = PaymentRepository::mainQuotePayment($travelQuote);
            $insurer = getInsuranceProvider($payment, QuoteTypes::TRAVEL->value);
            $insurerCode = $insurer?->code;

            $isALNC = $insurerCode == InsuranceProvidersEnum::ALNC;

            info(self::class.' : '.__FUNCTION__.' - Quote ID : '.$quoteId.' - isALNC : '.$isALNC.' - Insurer Code : '.$insurerCode.' - Payment ID : '.$payment?->code);
            // Check if the insurer_api_status_id is in the list of failed statuses
            $isFailedStatus = in_array($travelQuote->insurer_api_status_id, [
                PolicyIssuanceEnum::AUTO_CAPTURE_FAILED_STATUS_ID,
                PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
            ]);

            info(self::class.' : '.__FUNCTION__.' - Quote ID : '.$quoteId.' - isALNC : '.$isALNC.' - isFailedStatus : '.$isFailedStatus.' - isAllianceTravelAutomationEnabled : '.isAllianceTravelAutomationEnabled());

            // Skip the lead if it's Alliance Provider, Automation is enabled for Alliance and insurer api status is failed
            if ($isALNC && ! $isFailedStatus && isAllianceTravelAutomationEnabled()) {
                return null;
            }
        }

        // allocate the lead if it's Alliance Provider, Automation is disabled for Alliance
        return TravelQuote::where('uuid', $quoteId)
            ->whereNotIn('quote_status_id', [
                QuoteStatusEnum::Fake,
                QuoteStatusEnum::Duplicate,
                QuoteStatusEnum::Lost,
            ])
            ->when(! $overrideAdvisorId, fn ($q) => $q->whereNull('advisor_id'))
            ->first();
    }

    public function fetchAvailableAdvisor($isReassignmentJob = false, $teamId = null, $quoteUUID = null)
    {
        Log::info(self::class." - fetchAvailableAdvisor: {$isReassignmentJob} - {$teamId} - {$quoteUUID}");

        $quote = $this->fetchLead($quoteUUID);
        if ($quote) {
            $isSIC = $quote->isSIC(QuoteTypes::TRAVEL);
        }

        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (! $isReassignmentJob) {
            $statusOrder[] = UserStatusEnum::UNAVAILABLE;
        }

        foreach ($statusOrder as $status) {
            info(self::class." - trying to get advisors with current status as {$status} for lead uuid: {$quoteUUID}");
            $eligibleUser = $this->getAdvisorByStatus($status, $teamId, $isSIC);

            if ($eligibleUser) {
                info(self::class." - eligible user found with status: {$status} and user id : {$eligibleUser->user_id} and uuid: {$quoteUUID}");

                return User::find($eligibleUser->user_id);
            }
        }

        return null;
    }

    public function getAdvisorByStatus($status, $teamId = null, $isSIC = false)
    {
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
            ->where('users.is_active', true)
            ->when($isSIC, function ($q) {
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

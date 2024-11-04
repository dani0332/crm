<?php

namespace App\Services;

use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadSourceEnum;
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
use Illuminate\Support\Facades\Log;

class TravelAllocationService extends AllocationService
{
    public function fetchLead($quoteId, $overrideAdvisorId = false)
    {
        return TravelQuote::where('uuid', $quoteId)
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
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
            if ($quote->source == LeadSourceEnum::RENEWAL_UPLOAD) {
                $eligibleUser = $this->getAdvisorByStatus($status, $teamId, $isSIC, true, $quote);
            } else {
                $eligibleUser = $this->getAdvisorByStatus($status, $teamId, $isSIC);
            }
            if ($eligibleUser) {
                info(self::class." - eligible user found with status: {$status} and user id : {$eligibleUser->user_id} and uuid: {$quoteUUID}");

                return User::find($eligibleUser->user_id);
            }
        }

        return null;
    }

    public function getAdvisorByStatus($status, $teamId = null, $isSIC = false, $enableRoundRobin = false, $quote = null)
    {
        $query = User::select('users.id as user_id')
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
        info(self::class." - getAdvisorByStatus query: {$query->toSql()}, bindings: ".json_encode($query->getBindings()));
        // Get the list of eligible advisors
        $advisors = $query->get();

        // Implement round-robin logic if enabled and advisors are available
        if ($enableRoundRobin && $advisors->isNotEmpty()) {
            // Fetch the last assigned advisor for this specific lead
            $lastAssignedAdvisor = $this->getPreviousAdvisor($quote);
            info(self::class.' - Last assigned advisor ID: '.($lastAssignedAdvisor->id ?? 'null')." for quote UUID: {$quote->uuid}");

            // Find the index of the last assigned advisor in the list of eligible advisors
            $lastIndex = $lastAssignedAdvisor
                ? $advisors->search(fn ($advisor) => $advisor->user_id == $lastAssignedAdvisor->id)
                : false;
            info(self::class.' - Last index of assigned advisor: '.($lastIndex !== false ? $lastIndex : 'none').' in eligible advisors list');

            // Calculate the index of the next advisor to assign
            $nextIndex = ($lastIndex === false || $lastIndex === $advisors->count() - 1) ? 0 : $lastIndex + 1;
            info(self::class." - Next index to assign: {$nextIndex}");

            // Assign and log the next advisor
            if ($advisors->has($nextIndex)) {
                $assignedAdvisor = $advisors[$nextIndex];
                info(self::class." - Advisor assigned: {$assignedAdvisor->user_id}");
                return $assignedAdvisor;
            }
            else {
                info(self::class." - No eligible advisors found for this lead - {$nextIndex} - Ref-ID: {$quote->uuid} | Time: ".now());
                return null;
            }
        }

        // If round-robin is not enabled or no advisors found, return the first eligible advisor
        return $advisors->first();
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

    private function getPreviousAdvisor($lead = null)
    {
        // Retrieve the most recent TravelQuote for the given customer with an assigned advisor
        return TravelQuote::query()
            // ->where('customer_id', $lead->customer_id)
            ->where('source', LeadSourceEnum::RENEWAL_UPLOAD)
            ->whereNotNull('advisor_id')
            ->with('advisor')
            ->latest('created_at')
            ->first()
            ?->advisor;
    }
}

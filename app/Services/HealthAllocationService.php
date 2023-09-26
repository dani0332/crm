<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\HealthTeamType;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\UserStatusEnum;
use App\Jobs\CammyJob;
use App\Jobs\GetQuotePlansJob;
use App\Mail\HealthAssignmentIssueEmail;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class HealthAllocationService extends AllocationService
{
    public function fetchLead($quoteId)
    {
        return HealthQuote::where('uuid', $quoteId)
            ->where('quote_status_id', QuoteStatusEnum::Quoted)
            ->whereNotNull('health_quote_request.price_starting_from')
            ->where('health_quote_request.is_error_email_sent', false)
            ->whereNull('health_quote_request.advisor_id')
            ->first();
    }

    public function fetchReAssignmentLead($advisorId)
    {
        $from = now()->subDay()->setTime(18, 30)->format(config('constants.DB_DATE_FORMAT_MATCH'));
        info('leads will be picked up in reassignment from : '.$from);

        $leads = HealthQuote::whereBetween('created_at', [$from, now()])
            ->whereNotNull('health_quote_request.price_starting_from')
            ->whereIn('quote_status_id', [QuoteStatusEnum::Quoted]);
        if ($advisorId != 0) {
            $leads->where('advisor_id', $advisorId);
        }

        return $leads->get();
    }

    public function assignTeamBasedOnPrice($lead)
    {
        info('Inside assignHealthTeamBasedOnStartingPrice for quote : '.$lead->uuid);

        $priceStartingFrom = $lead->price_starting_from;

        $healthTeam = Team::where('allocation_threshold_enabled', true)
            ->where('min_price', '<=', $priceStartingFrom)
            ->where('max_price', '>=', $priceStartingFrom)
            ->first();

        if ($healthTeam) {
            info('assignHealthTeamBasedOnStartingPrice filtered team is : '.$healthTeam->name);
            $lead->update([
                'health_team_type' => $healthTeam->name,
            ]);
        } else {
            info('assignHealthTeamBasedOnStartingPrice team not found against : '.$lead->uuid);
            $lead->update([
                'is_error_email_sent' => true,
            ]);
            Mail::send(new HealthAssignmentIssueEmail($lead->code, $priceStartingFrom));
        }
    }

    public function fetchAvailableAdvisor($leadTeam)
    {
        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
            UserStatusEnum::UNAVAILABLE,
        ];

        foreach ($statusOrder as $status) {
            $eligibleUser = $this->getAdvisorByStatus($status, $leadTeam);
            if ($eligibleUser) {
                info('eligible user found for team : '.$leadTeam.' with status : '.$status.' and user id :'.$eligibleUser->user_id);

                return User::where('id', $eligibleUser->user_id)->first();
            }
        }

        return [];
    }

    public function getAdvisorByStatus($status, $leadTeam)
    {
        info('trying to get advisors for team : '.$leadTeam.' with current status as '.$status);

        return User::join('lead_allocation as la', 'la.user_id', '=', 'users.id')
            ->join('teams as t', 't.id', '=', 'users.sub_team_id')
            ->where('users.status', $status)
            ->where(function ($query) {
                $query->whereRaw('la.allocation_count < la.max_capacity')
                    ->orWhere('la.max_capacity', '=', -1);
            })
            ->where('t.name', $leadTeam)
            ->orderBy('la.last_allocated', 'asc')->first();
    }

    public function assignLead($lead, $advisor, $assignmentType)
    {
        $previousUserId = $lead->advisor_id;
        $lead->advisor_id = $advisor->id;
        $lead->assignment_type = $assignmentType;
        $lead->save();
        info('Lead Id '.$lead->uuid.' assigned to advisor : '.$advisor->name);

        $previousAdvisorAssignedDate = $this->updateQuoteDetail($lead->id);

        if ($lead->source != LeadSourceEnum::REFERRAL) {
            info('lead source is not referral so about to update allocation record');
            $assignmentType == AssignmentTypeEnum::SYSTEM_ASSIGNED ? $this->addAllocationCounts($advisor->id) : $this->adjustAllocationCounts($advisor->id, $lead, $previousUserId, $previousAdvisorAssignedDate);
        }

        $releaseDate = Carbon::parse('2022-10-10 11:00:00')->timestamp;
        $leadCreated = Carbon::parse($lead->created_at)->timestamp;

        if (in_array($lead->health_team_type, [HealthTeamType::EBP, HealthTeamType::RM_NB, HealthTeamType::RM_SPEED])
                && $leadCreated > $releaseDate && $lead->quote_status_id == QuoteStatusEnum::Quoted) {
            CammyJob::dispatch($lead, 'intro');
        }

        GetQuotePlansJob::dispatch($lead);
    }

    public function updateQuoteDetail($leadId)
    {
        info('about to update car quote detail record for : '.$leadId);

        $quoteDetail = HealthQuoteRequestDetail::where('health_quote_request_id', $leadId)->first();
        $oldAdvisorAssignedDate = '';

        if ($quoteDetail) {

            $oldAdvisorAssignedDate = $quoteDetail->advisor_assigned_date;
            $this->updateExistingQuoteDetail($quoteDetail, $leadId);

        } else {
            $this->createNewQuoteDetail($leadId, HealthQuoteRequestDetail::class, 'health_quote_request_id');
        }

        return $oldAdvisorAssignedDate;
    }

    public function shouldProceed(): bool
    {
        $start_time = Carbon::createFromFormat('H:i', $this->getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_START_TIME));
        $end_time = Carbon::createFromFormat('H:i', $this->getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_END_TIME));
        $shouldProceed = now()->between($start_time, $end_time);

        return $shouldProceed;
    }
}

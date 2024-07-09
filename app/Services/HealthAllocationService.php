<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\HealthTeamType;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Jobs\CammyJob;
use App\Jobs\GetQuotePlansJob;
use App\Jobs\IntroEmailJob;
use App\Mail\HealthAssignmentIssueEmail;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\QuoteBatches;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class HealthAllocationService extends AllocationService
{
    public function fetchLead($quoteId, $overrideAdvisorId)
    {
        $healthQuoteQuery = HealthQuote::where('uuid', $quoteId)
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->whereNotNull('health_quote_request.price_starting_from');

        if (! $overrideAdvisorId) {
            $healthQuoteQuery->whereNull('health_quote_request.advisor_id');
        }

        return $healthQuoteQuery->first();
    }

    public function fetchReAssignmentLead($advisorId)
    {
        $from = now()->subDay()->setTime(12, 30)->format(config('constants.DB_DATE_FORMAT_MATCH'));
        info('leads will be picked up in reassignment from : '.$from);

        $leads = HealthQuote::whereBetween('created_at', [$from, now()])
            ->whereNotNull('health_quote_request.price_starting_from')
            ->where('health_quote_request.is_error_email_sent', false)
            ->whereIn('quote_status_id', [QuoteStatusEnum::Quoted]);
        if ($advisorId != 0) {
            $leads->where('advisor_id', $advisorId);
        } else {
            // If advisor ID is not provided, get unavailable advisors and filter leads by them
            $advisors = $this->getUnavailableAdvisor();
            if (count($advisors) > 0) {
                $advisorIds = $advisors->pluck('user_id');
                info('Inside reassignment general run');
                $leads->whereIn('advisor_id', $advisorIds);
            }
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
            $lead->health_team_type = $healthTeam->name;
            $lead->save();
        } else {
            info('assignHealthTeamBasedOnStartingPrice team not found against : '.$lead->uuid);
            $lead->is_error_email_sent = true;
            $lead->save();
            Mail::send(new HealthAssignmentIssueEmail($lead->code, $priceStartingFrom));
        }
    }

    public function fetchAvailableAdvisor($leadTeam, $isReassignmentJob)
    {
        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (! $isReassignmentJob) {
            $statusOrder[] = UserStatusEnum::UNAVAILABLE;
        }

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
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->join('user_team as ut', 'ut.user_id', '=', 'users.id')
            ->join('teams as t', 't.id', '=', 'ut.team_id')
            ->where('users.status', $status)
            ->where(function ($query) {
                $query->whereRaw('la.allocation_count < la.max_capacity')
                    ->orWhere('la.max_capacity', '=', -1);
            })
            ->whereIn('r.name', [RolesEnum::EBPAdvisor, RolesEnum::RMAdvisor])
            ->where('la.quote_type_id', QuoteTypes::HEALTH->id())
            ->where('users.is_active', true)
            ->where('t.name', $leadTeam)
            ->orderBy('la.last_allocated', 'asc')->first();
    }

    public function assignLead($lead, $advisor, $assignmentType)
    {
        $previousAssignmentType = $lead->assignment_type;
        $previousUserId = $lead->advisor_id;
        $isReassignment = $previousUserId != null;
        $lead->advisor_id = $advisor->id;
        $lead->assignment_type = $assignmentType;
        $lead->quote_updated_at = now();
        $quoteBatch = QuoteBatches::latest()->first();
        $lead->quote_batch_id = $quoteBatch->id;
        $lead->save();
        info('Lead Id '.$lead->uuid.' assigned to advisor : '.$advisor->name.' Quote Batch with ID: '.$quoteBatch->id.' and Name: '.$quoteBatch->name);

        $previousAdvisorAssignedDate = $this->updateQuoteDetail($lead->id);

        if ($lead->source != LeadSourceEnum::REFERRAL) {
            info('lead source is not referral so about to update allocation record');
            $assignmentType == AssignmentTypeEnum::SYSTEM_ASSIGNED ? $this->addAllocationCounts($advisor->id, QuoteTypes::HEALTH->id()) : $this->adjustAllocationCounts($advisor->id, $lead, $previousUserId, $previousAdvisorAssignedDate, $previousAssignmentType, QuoteTypes::HEALTH->id());
        }

        Haystack::build()
            ->addJob(new GetQuotePlansJob($lead))
            ->then(function () use ($lead, $isReassignment, $previousUserId) {
                if (in_array($lead->health_team_type, [HealthTeamType::EBP, HealthTeamType::RM_NB, HealthTeamType::RM_SPEED])) {
                    IntroEmailJob::dispatch(quoteTypeCode::Health, 'Capi', $lead->uuid, 'send-rm-intro-email', $previousUserId, $isReassignment)->delay(now()->addSeconds(15));
                    if ($lead->quote_status_id == QuoteStatusEnum::FollowedUp) {
                        CammyJob::dispatch($lead, 'intro')->delay(now()->addSeconds(15));
                    }
                }
            })->dispatch();
    }

    public function updateQuoteDetail($leadId)
    {
        info('about to update health quote detail record for : '.$leadId);

        $quoteDetail = HealthQuoteRequestDetail::where('health_quote_request_id', $leadId)->first();
        $oldAdvisorAssignedDate = $quoteDetail->advisor_assigned_date ?? '';
        $this->upsertQuoteDetail($leadId, HealthQuoteRequestDetail::class, 'health_quote_request_id');

        return $oldAdvisorAssignedDate;
    }

    public function shouldProceed(): bool
    {
        $start_time = Carbon::createFromFormat('H:i', $this->getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_START_TIME));
        $end_time = Carbon::createFromFormat('H:i', $this->getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_END_TIME));
        $shouldProceed = now()->between($start_time, $end_time) && ((int) config('constants.HEALTH_LEAD_ALLOCATION_MASTER_SWITCH') == 1);

        return $shouldProceed;
    }
}

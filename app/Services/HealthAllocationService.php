<?php

namespace App\Services;

use App\Enums\HealthTeamType;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\UserStatusEnum;
use App\Jobs\GetQuotePlansJob;
use App\Jobs\SyncSIBContactJob;
use App\Mail\HealthAssignmentIssueEmail;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\LeadAllocation;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class HealthAllocationService extends AllocationService
{
    public function fetchLead($quoteId)
    {
        return HealthQuote::where('uuid', $quoteId)
                ->where('quote_status_id', QuoteStatusEnum::Qualified)
                ->whereNotNull('health_quote_request.price_starting_from')
                ->where('health_quote_request.is_error_email_sent', false)
                ->whereNull('health_quote_request.advisor_id')
                ->first();
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
            $eligibleUser = $this->getAdvisorsByStatus($status, $leadTeam);
            if ($eligibleUser) {
                return $eligibleUser;
            }
        }
    }

    public function getAdvisorsByStatus($status, $leadTeam)
    {
        return LeadAllocation::with('leadAllocationUser')
            ->join('teams as t', 't.id', '=', 'u.sub_team_id')
            ->whereHas('leadAllocationUser', function ($query) use ($status) {
                $query->where('last_login', '>', DB::raw('DATE_ADD(CURDATE(), INTERVAL 1 SECOND)'))
                    ->where('is_available', 1)->where('status', $status);
            })
            ->where(function ($query) {
                $query->whereRaw('allocation_count < max_capacity')
                    ->orWhere('max_capacity', -1);
            })
            ->where('t.name', $leadTeam)
            ->orderByDesc('last_allocated')
            ->first();
    }


    public function assignLead($lead, $advisorId, $assignmentType)
    {
        $lead->advisor_id = $advisorId;
        $lead->assignment_type = $assignmentType;
        $lead->save();
        info('Lead Id '.$lead->uuid.' assigned to advisor '.$advisorId);
        if ($lead->source != LeadSourceEnum::REFERRAL) {
            $this->updateLeadAllocationRecord($advisorId, false);
        }

        $this->updateLeadDetailRecord($lead->id, $lead->uuid);
        $releaseDate = Carbon::parse('2022-10-10 11:00:00')->timestamp;
        $leadCreated = Carbon::parse($lead->created_at)->timestamp;
        if ($lead->health_team_type == HealthTeamType::EBP && $leadCreated > $releaseDate && $lead->quote_status_id == QuoteStatusEnum::Quoted) {
            SyncSIBContactJob::dispatch($lead);
        }

        GetQuotePlansJob::dispatch($lead);
    }


    public function updateLeadAllocationRecord($userId, $isManualAssignment)
    {
        info('updateLeadAllocationRecord -- started');
        $leadAllocation = LeadAllocation::where('user_id', $userId)->first();
        info('Max capacity for user '.$userId.' is '.$leadAllocation->max_capacity.' and allocation count is '.$leadAllocation->allocation_count);
        $leadAllocation->allocation_count += 1;
        if ($isManualAssignment) {
            $leadAllocation->manual_assignment_count = $leadAllocation->manual_assignment_count + 1;
        } else {
            $leadAllocation->auto_assignment_count = $leadAllocation->auto_assignment_count + 1;
        }
        $leadAllocation->last_allocated = now()->timestamp;
        $leadAllocation->save();
        info('Lead allocation record for user '.$userId.' updated. Current allocation count is '.$leadAllocation->allocation_count);
    }

    public function updateLeadDetailRecord($leadId, $leadUId)
    {
        info('updateLeadDetailRecord -- started for lead UUID: '.$leadUId);
        $leadDetail = HealthQuoteRequestDetail::where('health_quote_request_id', $leadId)->first();
        if ($leadDetail) {
            $leadDetail->advisor_assigned_date = now();
            $leadDetail->advisor_assigned_by_id = auth()->id();
            $leadDetail->save();
        }
        info('updateLeadDetailRecord -- completed for lead uuid: '.$leadUId);
    }
}

<?php

namespace App\Services;

use App\Enums\HealthTeamType;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\UserStatusEnum;
use App\Jobs\GetQuotePlansJob;
use App\Jobs\SyncSIBContactJob;
use App\Models\HealthQuote;
use App\Models\LeadAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HealthAllocationService extends AllocationService
{
    public function fetchLeads($quoteId)
    {
        return HealthQuote::where('id', $quoteId)->get();
    }

    public function getEligibleUsersForAllocation()
    {
        $OnlineEligibleUsers = $this->getAdvisorsByStatus(UserStatusEnum::ONLINE);
        $OfflineEligibleUsers = $this->getAdvisorsByStatus(UserStatusEnum::OFFLINE);
        $eligibleUsers = count($OnlineEligibleUsers) > 0 ? $OnlineEligibleUsers : $OfflineEligibleUsers;

        return $eligibleUsers;
    }

    public function getAdvisorsByStatus($status)
    {
        return LeadAllocation::with('leadAllocationUser')
            ->whereHas('leadAllocationUser', function ($query) use ($status) {
                $query->where('last_login', '>', DB::raw('DATE_ADD(CURDATE(), INTERVAL 1 SECOND)'))
                    ->where('is_available', 1)->where('status', $status);
            })
            ->where(function ($query) {
                $query->whereRaw('allocation_count < max_capacity')
                    ->orWhere('max_capacity', -1);
            })
            ->orderByDesc('last_allocated')->get()->pluck('leadAllocationUser.id');
    }

    public function processAssignment($lead, $advisorId, $assignmentType)
    {

        if ($lead->advisor_id != null) {
            $this->removeLeadAllocationForOldAdvisor($lead);
        }
        info('Lead Id '.$lead->uuid.' assigned to advisor '.$advisorId);
        if ($lead->source != LeadSourceEnum::REFERRAL) {
            $this->updateLeadAllocationRecord($advisorId, $assignmentType);
        }
        $releaseDate = Carbon::parse('2022-10-10 11:00:00')->timestamp;
        $leadCreated = Carbon::parse($lead->created_at)->timestamp;
        if ($lead->health_team_type == HealthTeamType::EBP && $leadCreated > $releaseDate && $lead->quote_status_id == QuoteStatusEnum::Quoted) {
            SyncSIBContactJob::dispatch($lead);
        }
        GetQuotePlansJob::dispatch($lead);
    }
}

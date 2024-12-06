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
use App\Jobs\GetQuotePlansJob;
use App\Jobs\IntroEmailJob;
use App\Mail\HealthAssignmentIssueEmail;
use App\Models\BuyLeadRequest;
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
    public bool $isBuyLeadAdvisor = false;
    protected ?BuyLeadRequest $buyLeadRequest = null;

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
            ->whereIn('quote_status_id', [QuoteStatusEnum::Quoted])
            ->where('source', '!=', LeadSourceEnum::IMCRM);
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

    public function assignTeamBasedOnPrices($lead)
    {
        info("Inside assignHealthTeamBasedOnStartingPrice for quote: {$lead->uuid}");

        $priceStartingFrom = $this->determinePriceStartingFrom($lead);

        $healthTeam = Team::where('allocation_threshold_enabled', true)
            ->where('min_price', '<=', $priceStartingFrom)
            ->where('max_price', '>=', $priceStartingFrom)
            ->first();

        if ($healthTeam) {
            info("Filtered team for {$lead->uuid} is: {$healthTeam->name}");
            $lead->health_team_type = ($healthTeam->name === HealthTeamType::PCP && $lead->members->count() > 2) ? HealthTeamType::RM_NB : $healthTeam->name;
        } else {
            info("No team found for {$lead->uuid}");
            $lead->is_error_email_sent = true;
            Mail::send(new HealthAssignmentIssueEmail($lead->code, $priceStartingFrom));
        }

        $lead->save();
    }

    private function determinePriceStartingFrom(HealthQuote $lead)
    {
        if ($lead->isSIC(QuoteTypes::HEALTH)) {
            $price = ! empty($lead->plan_id) && ! empty($lead->premium) ? $lead->premium : $lead->price_starting_from;
            $planStatus = ! empty($lead->plan_id) ? 'found' : 'not found';
            info("Plan {$planStatus} for {$lead->uuid} with plan id: {$lead->plan_id} | premium: {$lead->premium} | Time: ".now());
        } else {
            $price = $lead->price_starting_from;
            info("No SIC lead for {$lead->uuid} | plan id: {$lead->plan_id} | premium: {$lead->premium} | Time: ".now());
        }

        return $price;
    }

    private function fetchAdvisor(string $findAdvisorFn, $leadTeam, $isReassignmentJob, HealthQuote $lead)
    {
        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (! $isReassignmentJob) {
            $statusOrder[] = UserStatusEnum::UNAVAILABLE;
        }

        foreach ($statusOrder as $status) {
            $eligibleUser = $this->{$findAdvisorFn}($status, $leadTeam, $lead);

            if ($eligibleUser) {
                info('eligible user found for team : '.$leadTeam.' with status : '.$status.' and user id :'.$eligibleUser->user_id);

                return User::where('id', $eligibleUser->user_id)->first();
            }
        }

        return [];
    }

    public function fetchAvailableAdvisor($leadTeam, $isReassignmentJob, HealthQuote $lead)
    {
        // Reset Buy Lead Advisor flag and Buy Lead Request object.
        $this->isBuyLeadAdvisor = false;
        $this->buyLeadRequest = null;

        $advisor = null;

        if ($lead->isBuyLeadApplicable() && ($lead->isValueLead() || $lead->isVolumeLead())) {
            $advisor = $this->fetchAdvisor('getBLAdvisorByStatus', $leadTeam, $isReassignmentJob, $lead);
        }

        if (empty($advisor) || ! $advisor) {
            $advisor = $this->fetchAdvisor('getAdvisorByStatus', $leadTeam, $isReassignmentJob, $lead);
        }

        return $advisor;
    }

    private function getAdvisorBaseQuery($status, $leadTeam)
    {
        return User::select('users.id as user_id')
            ->join('lead_allocation as la', 'la.user_id', '=', 'users.id')
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->join('user_team as ut', 'ut.user_id', '=', 'users.id')
            ->join('teams as t', 't.id', '=', 'ut.team_id')
            ->where('users.status', $status)
            ->whereIn('r.name', [RolesEnum::EBPAdvisor, RolesEnum::RMAdvisor])
            ->where('la.quote_type_id', QuoteTypes::HEALTH->id())
            ->where('users.is_active', true)
            ->where('t.name', $leadTeam);
    }

    public function getBLAdvisorByStatus($status, $leadTeam, HealthQuote $lead)
    {
        info(self::class."::getBLAdvisorByStatus - trying to get advisors for team : {$leadTeam} with current status as {$status} for UUID: {$lead->uuid}");

        $buyLeadRequestedUserIds = BuyLeadRequest::getRequestedUserIds(QuoteTypes::HEALTH, $lead->isValueLead());

        $advisor = $this->getAdvisorBaseQuery($status, $leadTeam)
            ->when($lead->isValueLead(), function ($q) {
                $q->isValueUser();
            }, function ($q) {
                $q->isVolumeUser();
            })
            ->whereIn('users.id', $buyLeadRequestedUserIds)
            ->where('la.buy_lead_status', true)
            ->where(function ($query) {
                $query->whereRaw('la.buy_lead_allocation_count < la.buy_lead_max_capacity')->orWhere('la.buy_lead_max_capacity', -1);
            })
            ->orderBy('la.buy_lead_last_allocated', 'asc')
            ->first();

        if ($advisor) {
            info(self::class."::getBLAdvisorByStatus - found Advisor : {$advisor->user_id} for team : {$leadTeam} with current status as {$status} for UUID: {$lead->uuid}");
            $this->buyLeadRequest = BuyLeadRequest::getRequest(QuoteTypes::HEALTH, $advisor->user_id, $lead->isValueLead());
            if ($this->buyLeadRequest) {
                $this->buyLeadRequest->startProcessing();
                $this->isBuyLeadAdvisor = true;
            } else {
                info(self::class."::getBLAdvisorByStatus - Advisor found but Buy Lead Request not found for Advisor : {$advisor->user_id} for UUID: {$lead->uuid}");
                $advisor = null;
            }
        }

        return $advisor;
    }

    public function getAdvisorByStatus($status, $leadTeam, HealthQuote $lead)
    {
        info(self::class."::getAdvisorByStatus - trying to get advisors for team : {$leadTeam} with current status as {$status} for UUID: {$lead->uuid}");

        return $this->getAdvisorBaseQuery($status, $leadTeam)
            ->where(function ($query) {
                $query->whereRaw('la.allocation_count < la.max_capacity')->orWhere('la.max_capacity', '=', -1);
            })
            ->orderBy('la.last_allocated', 'asc')
            ->first();
    }

    public function assignLead($lead, $advisor, $assignmentType)
    {
        if ($lead->advisor_id === $advisor->id) {
            info('Advisor is same as current advisor for lead : '.$lead->uuid.' so skipping assignment');

            return;
        }

        if (! empty($lead->advisor_id) && $assignmentType !== AssignmentTypeEnum::SYSTEM_REASSIGNED) {
            $assignmentType = AssignmentTypeEnum::SYSTEM_REASSIGNED;
        }

        if ($assignmentType === AssignmentTypeEnum::SYSTEM_ASSIGNED && $this->isBuyLeadAdvisor) {
            $assignmentType = AssignmentTypeEnum::BOUGHT_LEAD;
        }

        if ($assignmentType === AssignmentTypeEnum::SYSTEM_REASSIGNED && $this->isBuyLeadAdvisor) {
            $assignmentType = AssignmentTypeEnum::REASSIGNED_TO_BOUGHT_LEAD;
        }

        $previousAssignmentType = $lead->assignment_type;
        $previousUserId = $lead->advisor_id;
        $isReassignment = $previousUserId != null;
        $lead->advisor_id = $advisor->id;
        $lead->assignment_type = $assignmentType;
        $lead->quote_updated_at = now();
        $quoteBatch = QuoteBatches::latest()->first();
        $lead->quote_batch_id = $quoteBatch->id;
        $lead->save();

        if ($this->isBuyLeadAdvisor) {
            $this->buyLeadRequest->buyLead($lead, QuoteTypes::HEALTH);
            info('Lead Id '.$lead->uuid.' assigned to advisor : '.$advisor->name.' Quote Batch with ID: '.$quoteBatch->id.' and Name: '.$quoteBatch->name.' as bought lead');
        } else {
            info('Lead Id '.$lead->uuid.' assigned to advisor : '.$advisor->name.' Quote Batch with ID: '.$quoteBatch->id.' and Name: '.$quoteBatch->name);
        }

        $previousAdvisorAssignedDate = $this->updateQuoteDetail($lead->id);

        if ($lead->source != LeadSourceEnum::REFERRAL) {
            info('lead source is not referral so about to update allocation record');
            match ($assignmentType) {
                AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::BOUGHT_LEAD => $this->addAllocationCounts($advisor->id, QuoteTypes::HEALTH->id(), $this->isBuyLeadAdvisor),
                default => $this->adjustAllocationCounts($advisor->id, $lead, $previousUserId, $previousAdvisorAssignedDate, $previousAssignmentType, QuoteTypes::HEALTH->id(), $this->isBuyLeadAdvisor),
            };
        }

        Haystack::build()
            ->addJob(new GetQuotePlansJob($lead))
            ->then(function () use ($lead, $isReassignment, $previousUserId) {
                if (in_array($lead->health_team_type, [HealthTeamType::EBP, HealthTeamType::RM_NB, HealthTeamType::RM_SPEED, HealthTeamType::PCP])) {
                    IntroEmailJob::dispatch(quoteTypeCode::Health, 'Capi', $lead->uuid, 'send-rm-intro-email', $previousUserId, $isReassignment)->delay(now()->addSeconds(15));
                }
            })->dispatch();

        // Reset Buy Lead Advisor flag and Buy Lead Request object.
        $this->isBuyLeadAdvisor = false;
        $this->buyLeadRequest = null;
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

        return now()->between($start_time, $end_time) && ((int) config('constants.HEALTH_LEAD_ALLOCATION_MASTER_SWITCH') == 1);
    }
}

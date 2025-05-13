<?php

namespace App\Pipes\Allocation\Common;

use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\UserStatusEnum;
use App\Exceptions\Allocation\AllocationException;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\QuoteBatches;
use App\Models\TravelQuote;
use App\Models\User;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\AllocationService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;

abstract class BaseAllocationPipe extends AllocationService
{
    public const NOT_FOUND = Response::HTTP_NOT_FOUND;
    public const OK = Response::HTTP_OK;
    public const SERVER_ERROR = Response::HTTP_INTERNAL_SERVER_ERROR;

    protected AllocationRequest $allocationRequest;
    protected CarQuote|TravelQuote|HealthQuote|BusinessQuote|null $lead = null;

    protected function setRequest(AllocationRequest $allocationRequest, bool $startLogging = true)
    {
        $this->allocationRequest = $allocationRequest;

        if ($startLogging) {
            $this->startQuoteLogging();
        }

        if ($lead = $this->allocationRequest->getLead()) {
            $this->setLead($lead);
        }
    }

    protected function startQuoteLogging()
    {
        LoggerService::startQuoteLogging(
            $this->allocationRequest->getRefID(),
            LoggerFeatureEnum::ALLOCATION
        );
    }

    protected function setLead(Model $lead)
    {
        $this->lead = $lead;
    }

    protected function resolveLead()
    {
        $lead = $this->getBaseLead();

        if (! $lead) {
            $this->throw('Lead not found', self::NOT_FOUND);
        }

        $this->allocationRequest->setLead($lead);

        return $lead;
    }

    protected function logLeadData(Model $lead)
    {
        LoggerService::info(self::class.'::logLeadData', [
            'payment_status_id' => $lead->payment_status_id,
            'sic_advisor_requested' => $lead->sic_advisor_requested,
            'quote_status_id' => $lead->quote_status_id,
            'lead_allocation_failed_at' => $lead->lead_allocation_failed_at,
            'sic_flow_enabled' => $lead->sic_flow_enabled,
            'parent_quote_id' => $lead->parent_id,
            'source' => $lead->source,
        ]);
    }

    protected function getBaseLead(): ?Model
    {
        $lead = $this->allocationRequest->model()->where('uuid', $this->allocationRequest->getQuoteUUID())->first();

        if (! $lead) {
            LoggerService::info('Lead not found');

            return null;
        }

        $this->logLeadData($lead);

        return $lead;
    }

    protected function getLeadBaseQuery()
    {
        return $this->allocationRequest->model()
            ->where('uuid', $this->lead->uuid)
            ->whereNotIn('quote_status_id', [
                QuoteStatusEnum::Fake,
                QuoteStatusEnum::Duplicate,
                QuoteStatusEnum::Lost,
            ])
            ->when(! $this->allocationRequest->getOverrideAdvisorId(), fn ($q) => $q->whereNull('advisor_id'));
    }

    protected function throw(string $message, int $code = 500)
    {
        throw new AllocationException($message, $code);
    }

    protected function getQuoteBatch()
    {
        return QuoteBatches::latest()->first();
    }

    protected function getAdvisorBaseQuery($onlineStatus, $teamId, $roles)
    {
        return User::select('users.id as user_id')
            ->join('lead_allocation as la', 'la.user_id', '=', 'users.id')
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('users.status', $onlineStatus)
            ->where(function ($query) {
                $query->whereRaw('la.allocation_count < la.max_capacity')->orWhere('la.max_capacity', -1);
            })
            ->when($teamId, function ($q) use ($teamId) {
                $q->whereIn('users.id', fn ($query) => $query->select('user_id')->from('user_team')->where('team_id', $teamId));
            })
            ->whereIn('r.name', $roles)
            ->where('la.quote_type_id', $this->allocationRequest->getQuoteType()->id())
            ->activeUser()
            ->orderBy('la.last_allocated', 'asc');
    }

    protected function getOnlineStatusesInOrder()
    {
        $statuses = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (! $this->allocationRequest->isReassignmentJob()) {
            $statuses[] = UserStatusEnum::UNAVAILABLE;
        }

        return $statuses;
    }

    protected function findAvailableAdvisor($teamId = null)
    {
        $teamId = $teamId ?? $this->allocationRequest->getTeamId();

        $statusOrder = $this->getOnlineStatusesInOrder();

        foreach ($statusOrder as $status) {
            info(self::class." - trying to get advisors with current status as {$status} and team id: {$teamId}");
            $eligibleUser = $this->getAdvisorByStatus($status, $teamId);

            if ($eligibleUser) {
                info(self::class." - eligible user found with status: {$status} and user id : {$eligibleUser->user_id}");

                return User::find($eligibleUser->user_id);
            }
        }

        return null;
    }

    protected function getAdvisorByStatus($onlineStatus, $teamId)
    {
        /*
            override this method in child classes to get the advisor by status
            this method will stay in base class to make it optional for child classes to override
            default implementation is to return null
        */

        return null;
    }

    protected function assign()
    {
        $advisor = $this->allocationRequest->getAdvisor();
        $assignmentType = $this->allocationRequest->getAssignmentType();

        LoggerService::info(self::class.' - assignLead: Going to Assign Advisor');
        $previousAssignmentType = $this->lead->assignment_type;
        $previousUserId = $this->lead->advisor_id;

        $this->lead->advisor_id = $advisor->id;
        $this->lead->assignment_type = $assignmentType;

        $quoteBatch = $this->getQuoteBatch();
        $this->lead->quote_batch_id = $quoteBatch->id;
        $this->lead->save();

        $this->lead->endAllocation();

        LoggerService::info(self::class." - Assigned to advisor : {$advisor->name} Quote Batch with ID: {$quoteBatch->id} and Name: {$quoteBatch->name}");

        $previousAdvisorAssignedDate = $this->updateQuoteDetail($this->lead->id);

        if ($this->lead->source != LeadSourceEnum::REFERRAL) {
            LoggerService::info(self::class.' - lead source is not referral so about to update allocation record');

            $quoteTypeId = $this->allocationRequest->getQuoteType()->id();

            if ($assignmentType == AssignmentTypeEnum::SYSTEM_ASSIGNED) {
                $this->addAllocationCounts($advisor->id, $quoteTypeId);
            } else {
                $this->adjustAllocationCounts($advisor->id, $this->lead, $previousUserId, $previousAdvisorAssignedDate, $previousAssignmentType, $quoteTypeId);
            }
        }
    }

    protected function updateQuoteDetail()
    {
        LoggerService::info(self::class.' - about to update quote detail record');

        $oldAdvisorAssignedDate = $this->lead->quoteDetail?->advisor_assigned_date ?? '';

        $quoteType = $this->allocationRequest->getQuoteType();

        $this->upsertQuoteDetail($this->lead->id, $quoteType->detailModel(), $quoteType->model()->getForeignKey());

        return $oldAdvisorAssignedDate;
    }
}

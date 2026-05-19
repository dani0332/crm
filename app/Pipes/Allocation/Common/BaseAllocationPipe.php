<?php

namespace App\Pipes\Allocation\Common;

use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadAssignmentTriggerEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\UserStatusEnum;
use App\Exceptions\Allocation\AllocationException;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Models\QuoteBatches;
use App\Models\TravelQuote;
use App\Models\User;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\AllocationService;
use App\Services\Logger\LoggerService;
use App\Services\NationalityAllocationService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

abstract class BaseAllocationPipe extends AllocationService
{
    public const NOT_FOUND = Response::HTTP_NOT_FOUND;
    public const OK = Response::HTTP_OK;
    public const SERVER_ERROR = Response::HTTP_INTERNAL_SERVER_ERROR;

    protected AllocationRequest $allocationRequest;
    protected CarQuote|TravelQuote|HealthQuote|PersonalQuote|null $lead = null;

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
        $leadQuery = $this->allocationRequest->model()
            ->where('uuid', $this->allocationRequest->getQuoteUUID())
            ->when(
                $this->allocationRequest->getQuoteType() === QuoteTypes::CAR
                    && ! $this->allocationRequest->getQuoteType()->isPersonalQuote(),
                fn ($query) => $query->with([
                    'carQuoteRequestDetail:id,car_quote_request_id,engagement_level,engagement_level_updated_at,utm_campaign',
                ])
            );

        $lead = $leadQuery->first();

        if (! $lead) {
            LoggerService::info('Lead not found');

            $this->throw('Lead not found', self::NOT_FOUND);
        }

        $this->logLeadData($lead);

        $this->allocationRequest->setLead($lead);

        if ($lead->isSIC($this->allocationRequest->getQuoteType())) {
            $this->allocationRequest->markAsSIC();
        }

        if ($lead->isAIG($this->allocationRequest->getQuoteType())) {
            $this->allocationRequest->markAsAIG();
        }

        if (! $lead->isAIAdviserRequired() && $lead->isAIAdvisorAssigned()) {
            $this->allocationRequest->setAsReassignmentJob();
            $this->allocationRequest->overrideAdvisorId();
        }

        return $lead;
    }

    protected function logLeadData(Model $lead)
    {
        $data = [
            'payment_status_id' => $lead->payment_status_id,
            'sic_advisor_requested' => $lead->sic_advisor_requested,
            'quote_status_id' => $lead->quote_status_id,
            'lead_allocation_failed_at' => $lead->lead_allocation_failed_at,
            'sic_flow_enabled' => $lead->sic_flow_enabled,
            'parent_quote_id' => $lead->parent_id,
            'source' => $lead->source,
        ];

        if ($lead instanceof HealthQuote) {
            $data = [
                ...$data,
                'price_starting_from' => $lead->price_starting_from,
                'plan_id' => $lead->plan_id,
            ];
        }

        if ($lead instanceof TravelQuote) {
            $data = [
                ...$data,
                'coverage_code' => $lead->coverage_code,
            ];
        }

        LoggerService::info(self::class.'::logLeadData', $data);
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
            ->when(! $this->allocationRequest->isOverrideAdvisorRequest(), fn ($q) => $q->whereNull('advisor_id'));
    }

    protected function throw(string $message, int $code = 500)
    {
        throw new AllocationException($message, $code);
    }

    protected function stop(string $message, int $code = 200)
    {
        $this->throw($message, $code);
    }

    protected function getQuoteBatch()
    {
        return QuoteBatches::latest()->first();
    }

    protected function getAdvisorBaseQuery($onlineStatus, $teamId, $roles, bool $isBuyLead = false, bool $isCATA = false)
    {
        return User::select('users.id as user_id')
            ->join('lead_allocation as la', 'la.user_id', '=', 'users.id')
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('users.status', $onlineStatus)
            ->when(
                $isBuyLead,
                function ($q) use ($isCATA) {
                    $q->where(function ($query) use ($isCATA) {
                        if ($isCATA) {
                            $query->whereRaw('la.buy_lead_cat_a_allocation_count < la.buy_lead_max_capacity');
                        } else {
                            $query->whereRaw('la.buy_lead_allocation_count < la.buy_lead_max_capacity');
                        }

                        $query->orWhere('la.buy_lead_max_capacity', -1);
                    });
                },
                function ($q) {
                    $q->where(function ($query) {
                        $query->whereRaw('la.allocation_count < la.max_capacity')->orWhere('la.max_capacity', -1);
                    });
                },
            )
            ->when($teamId, function ($q) use ($teamId) {
                $q->whereIn('users.id', fn ($query) => $query->select('user_id')->from('user_team')->where('team_id', $teamId));
            })
            ->whereIn('r.name', $roles)
            ->where('la.quote_type_id', $this->allocationRequest->getQuoteType()->id())
            ->when(
                $this->allocationRequest->hasNationalityConfig(),
                fn ($q) => $q->whereIn('users.id', $this->allocationRequest->getAdvisorIDs()),
                function ($q) {
                    if ($this->allocationRequest->hasExcludedAdvisorIds()) {
                        $q->whereNotIn('users.id', $this->allocationRequest->getExcludedAdvisorIds());
                    }
                },
            )
            ->activeUser()
            ->when($isBuyLead, fn ($q) => $q->where('la.buy_lead_status', true))
            ->when(
                $isBuyLead,
                fn ($q) => $q->orderBy('la.buy_lead_last_allocated', 'asc'),
                fn ($q) => $q->orderBy('la.last_allocated', 'asc'),
            )
            ->when($this->allocationRequest->isReassignmentJob() && $this->allocationRequest->getReAssigFromAdvisorId(), fn ($q) => $q->where('users.id', '!=', $this->allocationRequest->getReAssigFromAdvisorId()));
    }

    protected function getOnlineStatusesInOrder()
    {
        $statuses = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        // We need to add unavailable status if the lead is not a reassignment job or the lead is an AI advisor assigned
        if (! $this->allocationRequest->isReassignmentJob() || $this->lead->isAIAdvisorAssigned()) {
            $statuses[] = UserStatusEnum::UNAVAILABLE;
        }

        if (! $this->isBusinessHours()) {
            $statuses[] = UserStatusEnum::MANUAL_OFFLINE;
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

    protected function resolveAssignmentType()
    {
        $assignmentType = $this->allocationRequest->getAssignmentType();

        if (! empty($this->lead->advisor_id) && $assignmentType !== AssignmentTypeEnum::SYSTEM_REASSIGNED) {
            $assignmentType = AssignmentTypeEnum::SYSTEM_REASSIGNED;
        }

        if ($assignmentType === AssignmentTypeEnum::SYSTEM_ASSIGNED && $this->allocationRequest->isBuyLead()) {
            $assignmentType = AssignmentTypeEnum::BOUGHT_LEAD;
        }

        if ($assignmentType === AssignmentTypeEnum::SYSTEM_REASSIGNED && $this->allocationRequest->isBuyLead()) {
            $assignmentType = AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD;
        }

        return $assignmentType;
    }

    protected function assignToAdvisor()
    {
        $advisor = $this->allocationRequest->getAdvisor();
        $assignmentType = $this->resolveAssignmentType();

        if (! empty($this->lead->advisor_id)) {
            LoggerService::info("Was previously assigned to User ID: {$this->lead->advisor_id} and is now being assigned to User ID: {$advisor->id}");
        }

        LoggerService::info(self::class.' - assignLead: Going to Assign Advisor');
        $previousAssignmentType = $this->lead->assignment_type;
        $previousAdvisorId = $this->lead->advisor_id;
        $isReAssignment = ! empty($previousAdvisorId);

        $this->lead->advisor_id = $advisor->id;
        $this->lead->assignment_type = $assignmentType;

        if ($advisor->isAi()) {
            $this->lead->ai_advisor_assigned_at = now();
        }

        $quoteBatch = $this->getQuoteBatch();
        $this->lead->quote_batch_id = $quoteBatch->id;

        if ($tier = $this->allocationRequest->getTier()) {
            $this->lead->tier_id = $tier->id;
            $this->lead->cost_per_lead = $tier->cost_per_lead;
        }

        if ($this->lead instanceof CarQuote) {
            $this->lead->auto_assigned = true;
        }

        if ($this->lead instanceof CarQuote || $this->lead instanceof TravelQuote || $this->lead instanceof HealthQuote) {
            $this->lead->sic_flow_enabled = 0;
        }

        if ($this->lead instanceof PersonalQuote && $this->lead->isCyber()) {
            $this->lead->sic_flow_enabled = 0;
        }

        if (empty($this->lead->lead_assignment_trigger)) {
            LoggerService::info(self::class.' - assignLeadToUserAndGetQuote: Setting lead_assignment_trigger to LEAD_AUTO_ASSIGNED');
            $this->lead->lead_assignment_trigger = LeadAssignmentTriggerEnum::LEAD_AUTO_ASSIGNED;
        }

        $this->lead->save();

        LoggerService::info("Assigned to advisor {$advisor->name}, Quote Batch with ID: {$quoteBatch->id} and Name: {$quoteBatch->name} as {$assignmentType}");

        $this->lead->endAllocation();

        if ($this->allocationRequest->isBuyLead()) {
            $this->allocationRequest->getBuyLeadRequest()->buyLead($this->lead, $this->allocationRequest->getQuoteType());
        }

        return [
            'advisor' => $advisor,
            'assignmentType' => $assignmentType,
            'previousAdvisorId' => $previousAdvisorId,
            'previousAssignmentType' => $previousAssignmentType,
            'quoteBatch' => $quoteBatch,
            'isReAssignment' => $isReAssignment,
        ];
    }

    protected function assign(?callable $afterAssign = null)
    {
        DB::beginTransaction();

        try {
            [
                'advisor' => $advisor,
                'assignmentType' => $assignmentType,
                'previousAdvisorId' => $previousAdvisorId,
                'previousAssignmentType' => $previousAssignmentType,
                'isReAssignment' => $isReAssignment,
            ] = $this->assignToAdvisor();

            $previousAdvisorAssignedDate = $this->updateQuoteDetail();

            if ($this->lead->source != LeadSourceEnum::REFERRAL) {
                LoggerService::info(self::class.' - lead source is not referral so about to update allocation record');

                $quoteTypeId = $this->allocationRequest->getQuoteType()->id();
                $isCatABuyLead = $this->allocationRequest->get('hasCatABuyLeadRequest', false);

                match ($assignmentType) {
                    AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::BOUGHT_LEAD => $this->addAllocationCounts($advisor->id, $quoteTypeId, $this->allocationRequest->isBuyLead(), $isCatABuyLead),
                    default => $this->adjustAllocationCounts($advisor->id, $this->lead, $previousAdvisorId, $previousAdvisorAssignedDate, $previousAssignmentType, $quoteTypeId, $this->allocationRequest->isBuyLead(), $isCatABuyLead),
                };
            }

            $this->allocationRequest->markAsAllocated();

            DB::commit();

            if ($afterAssign) {
                $afterAssign($isReAssignment, $previousAdvisorId, $previousAssignmentType);
            }
        } catch (Exception $e) {
            DB::rollBack();

            LoggerService::error($e->getMessage(), exception: $e);

            $this->allocationRequest->markAsFailed();
            $this->throw('Lead allocation failed: '.$e->getMessage(), self::SERVER_ERROR);
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

    protected function verifyIfAdvisorIsSameAsPreviousAdvisor(User $advisor)
    {
        if (! $this->lead->advisor_id) {
            return;
        }

        if ($advisor->id == $this->lead->advisor_id) {
            LoggerService::info('Advisor is same as previous advisor. Skipping for now.');

            $this->allocationRequest->markAsSameAdvisor();

            $this->throw('Eligible Advisor is already assigned to this lead', self::OK);
        }

    }

    public function resolveExcludedAdvisorIds()
    {
        $excludedAdvisorIds = NationalityAllocationService::getExcludedUserIds($this->allocationRequest->getQuoteType());

        if (empty($excludedAdvisorIds)) {
            return;
        }

        $excludedAdvisorIds = $this->finalizeExcludedAdvisorIds($excludedAdvisorIds);

        $this->allocationRequest->excludedAdvisorIds($excludedAdvisorIds);
    }

    protected function getUserIdsFromRuleRecords($matchedRuleRecords): array
    {
        // Get the lead source users from the first matched rule record.
        $leadSourceUsers = $matchedRuleRecords->first()->leadSourceUsers;

        // Check if the lead source users contain a comma (,) indicating multiple users.
        if (str_contains($leadSourceUsers, ',')) {
            // If there are multiple users, split the string by commas, convert each part to an integer, and store them in an array.
            $userIds = array_map('intval', explode(',', $leadSourceUsers));
        } else {
            // If there's only one user, cast it to an integer and store it in a single-element array.
            $userIds = [(int) $leadSourceUsers];
        }

        // Return the array of user IDs.
        return $userIds;
    }

    protected function finalizeExcludedAdvisorIds(?array $excludedAdvisorIds): array
    {
        if (empty($excludedAdvisorIds)) {
            return [];
        }

        $superAdvisorIds = User::whereHas('permissions', function ($query) {
            $query->where('name', PermissionsEnum::NONRULE_LEADALLOCATION);
        })->pluck('id')->toArray();

        $excludedAdvisorIds = array_diff($excludedAdvisorIds, $superAdvisorIds);
        $excludedAdvisorIds = array_values($excludedAdvisorIds);

        return $excludedAdvisorIds;
    }
}

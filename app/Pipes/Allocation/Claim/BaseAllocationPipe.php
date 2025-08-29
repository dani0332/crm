<?php

namespace App\Pipes\Allocation\Claim;

use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadAssignmentTriggerEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\UserStatusEnum;
use App\Models\ClaimRequest;
use App\Models\User;
use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

abstract class BaseAllocationPipe
{
    public const NOT_FOUND = Response::HTTP_NOT_FOUND;
    public const OK = Response::HTTP_OK;
    public const SERVER_ERROR = Response::HTTP_INTERNAL_SERVER_ERROR;

    protected AllocationRequest $claimAssignmentRequest;
    protected ?ClaimRequest $lead = null;

    protected function setRequest(AllocationRequest $claimAssignmentRequest, bool $startLogging = true)
    {
        $this->claimAssignmentRequest = $claimAssignmentRequest;

        if ($startLogging) {
            $this->startQuoteLogging();
        }

        if ($lead = $this->claimAssignmentRequest->getLead()) {
            $this->setLead($lead);
        }
    }

    protected function startQuoteLogging()
    {
        LoggerService::startQuoteLogging(
            $this->claimAssignmentRequest->getQuoteUUID(),
            LoggerFeatureEnum::CLAIM_ALLOCATION
        );
    }

    protected function setLead(Model $lead)
    {
        $this->lead = $lead;
    }

    protected function resolveLead()
    {
        $lead = $this->claimAssignmentRequest->model()->where('uuid', $this->claimAssignmentRequest->getQuoteUUID())->first();

        if (! $lead) {
            LoggerService::info('Claim lead not found');

            $this->throw('Claim lead not found', self::NOT_FOUND);
        }
        $this->claimAssignmentRequest->setLead($lead);

        return $lead;
    }

    protected function getClaimBaseQuery()
    {
        return $this->claimAssignmentRequest->model()->where('uuid', $this->claimAssignmentRequest->getQuoteUUID());
    }

    protected function throw(string $message, int $code = 500)
    {
        throw new Exception($message, $code);
    }

    protected function stop(string $message, int $code = 200)
    {
        $this->throw($message, $code);
    }

    /**
     * Get the base query for selecting eligible advisors for claim allocation.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getAdvisorBaseQuery(string $onlineStatus, array $roles)
    {
        // Use subquery to calculate allocation_count < max_capacity in the join condition for better performance
        return User::query()
            ->select('users.id as user_id')
            ->join('claims_lead_allocation_config as cla', function ($join) {
                $join->on('cla.user_id', '=', 'users.id')
                    ->whereColumn('cla.allocation_count', '<', 'cla.max_capacity')
                    ->where('cla.quote_type_id', $this->claimAssignmentRequest->getQuoteType()->id());
            })
            ->join('model_has_roles as mhr', function ($join) {
                $join->on('mhr.model_id', '=', 'users.id');
            })
            ->join('roles as r', function ($join) use ($roles) {
                $join->on('r.id', '=', 'mhr.role_id')
                    ->whereIn('r.name', $roles);
            })
            ->where('users.status', $onlineStatus)
            ->activeUser()
            ->groupBy('users.id')
            ->orderBy('cla.last_allocated', 'asc')
            ->limit(1);
    }

    protected function getOnlineStatusesInOrder()
    {
        $statuses = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (! $this->claimAssignmentRequest->isReassignmentJob()) {
            $statuses[] = UserStatusEnum::UNAVAILABLE;
        }

        return $statuses;
    }

    protected function findAvailableAdvisor($teamId = null)
    {

        $statusOrder = $this->getOnlineStatusesInOrder();

        foreach ($statusOrder as $status) {
            info(self::class." - trying to get advisors with current status as {$status} and team id: {$teamId}");
            $eligibleUser = $this->getAdvisorByStatus($status);

            if ($eligibleUser) {
                info(self::class." - eligible user found with status: {$status} and user id : {$eligibleUser->user_id}");

                return User::find($eligibleUser->user_id);
            }
        }

        return null;
    }

    protected function getAdvisorByStatus($onlineStatus)
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
        $assignmentType = $this->claimAssignmentRequest->getAssignmentType();

        if (! empty($this->lead->advisor_id) && $assignmentType !== AssignmentTypeEnum::SYSTEM_REASSIGNED) {
            $assignmentType = AssignmentTypeEnum::SYSTEM_REASSIGNED;
        }

        return $assignmentType;
    }

    protected function assignToAdvisor()
    {
        $advisor = $this->claimAssignmentRequest->getAdvisor();
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

        if (empty($this->lead->lead_assignment_trigger)) {
            LoggerService::info(self::class.' - assignLeadToUserAndGetQuote: Setting lead_assignment_trigger to LEAD_AUTO_ASSIGNED');
            $this->lead->lead_assignment_trigger = LeadAssignmentTriggerEnum::LEAD_AUTO_ASSIGNED;
        }

        $this->lead->save();

        $this->lead->endAllocation();

        return [
            'advisor' => $advisor,
            'assignmentType' => $assignmentType,
            'previousAdvisorId' => $previousAdvisorId,
            'previousAssignmentType' => $previousAssignmentType,
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

            $this->claimAssignmentRequest->markAsAllocated();

            DB::commit();

            if ($afterAssign) {
                $afterAssign($isReAssignment, $previousAdvisorId, $previousAssignmentType);
            }
        } catch (Exception $e) {
            DB::rollBack();

            LoggerService::error($e->getMessage(), exception: $e);

            $this->claimAssignmentRequest->markAsFailed();
            $this->throw('Lead allocation failed: '.$e->getMessage(), self::SERVER_ERROR);
        }
    }

    protected function verifyIfAdvisorIsSameAsPreviousAdvisor(User $advisor)
    {
        if (! $this->lead->advisor_id) {
            return;
        }

        if ($advisor->id == $this->lead->advisor_id) {
            LoggerService::info('Advisor is same as previous advisor. Skipping for now.');

            $this->claimAssignmentRequest->markAsSameAdvisor();

            $this->throw('Eligible Advisor is already assigned to this lead', self::OK);
        }

    }

}

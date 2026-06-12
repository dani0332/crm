<?php

namespace App\Pipes\Allocation\Claim;

use App\Enums\AssignmentTypeEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\UserStatusEnum;
use App\Models\ClaimRequest;
use App\Models\User;
use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\ClaimAllocation\ClaimAllocationService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

abstract class BaseAllocationPipe extends ClaimAllocationService
{
    public const NOT_FOUND = Response::HTTP_NOT_FOUND;
    public const OK = Response::HTTP_OK;
    public const SERVER_ERROR = Response::HTTP_INTERNAL_SERVER_ERROR;

    protected AllocationRequest $allocationRequest;
    protected ?ClaimRequest $lead = null;

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
            $this->allocationRequest->getClaimUUID(),
            LoggerFeatureEnum::CLAIM_ALLOCATION
        );
    }

    protected function setLead(Model $lead)
    {
        $this->lead = $lead;
    }

    protected function resolveLead()
    {
        $lead = $this->allocationRequest->model()->where('uuid', $this->allocationRequest->getClaimUUID())->first();
        if (! $lead) {
            LoggerService::info('Claim lead not found');

            $this->throw('Claim lead not found', self::NOT_FOUND);
        }

        $this->allocationRequest->setLead($lead);

        return $lead;
    }

    protected function getClaimBaseQuery()
    {
        return $this->allocationRequest->model()->where('uuid', $this->allocationRequest->getClaimUUID());
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
     * @return Builder
     */
    protected function getAdvisorBaseQuery(string $onlineStatus, array $roles)
    {
        // Use subquery to calculate allocation_count < max_capacity in the join condition for better performance
        return User::query()
            ->select('users.id as user_id')
            ->join('claims_lead_allocation_config as cla', function ($join) {
                $join->on('cla.user_id', '=', 'users.id')
                    ->whereColumn('cla.allocation_count', '<', 'cla.max_capacity')
                    ->where('cla.quote_type_id', $this->allocationRequest->getQuoteType()->id());
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

        if (! $this->allocationRequest->isReassignmentJob()) {
            $statuses[] = UserStatusEnum::UNAVAILABLE;
        }

        return $statuses;
    }

    protected function findAvailableManager($teamId = null)
    {

        $statusOrder = $this->getOnlineStatusesInOrder();

        foreach ($statusOrder as $status) {
            info(self::class." - trying to get managers with current status as {$status} and team id: {$teamId}");
            $eligibleUser = $this->getManagerByStatus($status);

            if ($eligibleUser) {
                info(self::class." - eligible user found with status: {$status} and user id : {$eligibleUser->user_id}");

                return User::find($eligibleUser->user_id);
            }
        }

        return null;
    }

    protected function getManagerByStatus($onlineStatus)
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

        if (! empty($this->lead->manager_id) && $assignmentType !== AssignmentTypeEnum::SYSTEM_REASSIGNED) {
            $assignmentType = AssignmentTypeEnum::SYSTEM_REASSIGNED;
        }

        return $assignmentType;
    }

    protected function assignToManager()
    {
        $manager = $this->allocationRequest->getManager();
        $assignmentType = $this->resolveAssignmentType();

        $previousManagerId = $this->lead->manager_id;
        $previousAssignmentType = $this->lead->assignment_type ?? null;
        $isReAssignment = ! empty($this->lead->manager_id);

        if ($isReAssignment) {
            LoggerService::info("Was previously assigned to User ID: {$previousManagerId} and is now being assigned to User ID: {$manager->id}");
        }

        LoggerService::info(self::class.' - assignLead: Going to Assign Manager');
        $this->lead->manager_id = $manager->id;
        $this->lead->manager_assigned_date = now();

        $this->updateClaimAllocationConfig($manager->id, $this->allocationRequest->getQuoteType()->id());
        $this->lead->save();

        $this->lead->endAllocation();

        return [
            'manager' => $manager,
            'assignmentType' => $assignmentType,
            'previousManagerId' => $previousManagerId,
            'previousAssignmentType' => $previousAssignmentType,
            'isReAssignment' => $isReAssignment,
        ];
    }

    protected function assign(?callable $afterAssign = null)
    {

        DB::beginTransaction();

        try {
            [
                'manager' => $manager,
                'assignmentType' => $assignmentType,
                'previousManagerId' => $previousManagerId,
                'previousAssignmentType' => $previousAssignmentType,
                'isReAssignment' => $isReAssignment,
            ] = $this->assignToManager();

            $this->allocationRequest->markAsAllocated();

            DB::commit();

            if ($afterAssign) {
                $afterAssign($isReAssignment, $previousManagerId, $previousAssignmentType);
            }
        } catch (Exception $e) {
            DB::rollBack();

            LoggerService::error($e->getMessage(), exception: $e);

            $this->allocationRequest->markAsFailed();
            $this->throw('Lead allocation failed: '.$e->getMessage(), self::SERVER_ERROR);
        }
    }

    protected function verifyIfManagerIsSameAsPreviousManager(User $manager)
    {
        if (! $this->lead->manager_id) {
            return;
        }
        if ($manager->id == $this->lead->manager_id) {
            LoggerService::info('Manager is same as previous manager. Skipping for now.');
            $this->allocationRequest->markAsSameManager();
            LoggerService::info('Eligible Manager is already assigned to this lead');
        }

    }

}

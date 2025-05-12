<?php

namespace App\Pipelines\Allocation\Travel;

use App\Enums\ProcessTracker\StepsEnums\ProcessTrackerAllocationEnum;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\UserStatusEnum;
use App\Models\Team;
use App\Models\User;
use App\Pipelines\Allocation\Common\BaseAllocationPipeline;
use App\Services\Logger\LoggerService;
use App\Strategies\Allocations\PipelineHandlers\AllocationRequest;
use Closure;

class FetchAvailableAdvisorPipeline extends BaseAllocationPipeline
{
    /**
     * Handle the incoming request.
     *
     * @param  mixed  $passable
     * @return mixed
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $advisor = $this->fetchAvailableAdvisor();

        if (! $advisor) {
            LoggerService::info(self::class.' - No advisor found');

            $this->allocationRequest->getTracker()->saveResult(ProcessTrackerAllocationEnum::ADVISOR_NOT_FOUND, ignoreStep: true);

            $this->throw('Advisor not found', self::NOT_FOUND);
        }

        $this->allocationRequest->set('advisor', $advisor);

        return $next($request);
    }

    private function fetchAvailableAdvisor()
    {
        $isReassignmentJob = $this->allocationRequest->isReassignmentJob();
        $teamId = $this->allocationRequest->getTeamId();
        $tracker = $this->allocationRequest->getTracker();

        LoggerService::info(self::class." - fetchAvailableAdvisor: {$isReassignmentJob} - {$teamId}");

        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (! $isReassignmentJob) {
            $statusOrder[] = UserStatusEnum::UNAVAILABLE;
        }

        $teamName = null;

        if ($this->lead->isPaymentAuthorizedOrPaymentLinkRequested()) {
            $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
            $teamName = TeamNameEnum::SIC_UNASSISTED;
        }

        foreach ($statusOrder as $status) {
            info(self::class." - trying to get advisors with current status as {$status}");
            $eligibleUser = $this->getAdvisorByStatus($status, $teamId);

            if ($eligibleUser) {
                info(self::class." - eligible user found with status: {$status} and user id : {$eligibleUser->user_id}");

                $user = User::find($eligibleUser->user_id);

                if ($tracker) {
                    $tracker->addStep(
                        ProcessTrackerAllocationEnum::ADVISOR_FOUND,
                        [
                            'userId' => $user->id,
                            '@name' => $user->name,
                            '@email' => $user->email,
                            '@status' => UserStatusEnum::getUserStatusText($status),
                        ]
                    );
                }

                return $user;
            } else {
                if ($tracker) {
                    $tracker->addStep(
                        ProcessTrackerAllocationEnum::ADVISOR_NOT_FOUND,
                        [
                            '@status' => UserStatusEnum::getUserStatusText($status),
                            'teamId' => $teamId,
                            ':teamName' => $teamName,
                            '@roleName' => RolesEnum::TravelAdvisor,
                        ],
                        removableWords: $teamName ? [] : ['against team :teamName']
                    );
                }
            }
        }

        return null;
    }

    public function getAdvisorByStatus($status, $teamId)
    {
        if ($this->allocationRequest->get('isCHSAdvisor')) {
            info(self::class.' - getAdvisorByStatus: CHS Advisor is required');

            return User::select('users.id as user_id')->chs()->first();
        }

        if ($this->allocationRequest->get('isSICAdvisor')) {
            info(self::class.' - getAdvisorByStatus: SIC Advisor is required');

            $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
        }

        $userQuery = User::select('users.id as user_id')
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
                $sicUnassistedTeamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
                if ($sicUnassistedTeamId) {
                    $q->whereNotIn('users.id', fn ($query) => $query->select('user_id')->from('user_team')->where('team_id', $sicUnassistedTeamId));
                }
            })
            ->whereIn('r.name', [RolesEnum::TravelAdvisor])
            ->where('la.quote_type_id', $this->allocationRequest->getQuoteType()->id())
            ->activeUser()
            ->when($this->lead->isSIC($this->allocationRequest->getQuoteType()), function ($q) {
                $q->where('la.is_hardstop', true); // fetch users only with hardstop as true as they are eligible for allocation
            })
            ->orderBy('la.last_allocated', 'asc');

        LoggerService::info(self::class." - getAdvisorByStatus query: {$userQuery->toRawSql()}");

        return $userQuery->first();
    }
}

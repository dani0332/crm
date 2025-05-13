<?php

namespace App\Pipelines\Allocation\Travel;

use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
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

            $this->throw('Advisor not found', self::NOT_FOUND);
        }

        $this->allocationRequest->set('advisor', $advisor);

        return $next($request);
    }

    private function fetchAvailableAdvisor()
    {
        $teamId = $this->allocationRequest->getTeamId();

        $statusOrder = $this->getOnlineStatusesInOrder();

        if ($this->lead->isPaymentAuthorizedOrPaymentLinkRequested()) {
            $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
        }

        foreach ($statusOrder as $status) {
            info(self::class." - trying to get advisors with current status as {$status}");
            $eligibleUser = $this->getAdvisorByStatus($status, $teamId);

            if ($eligibleUser) {
                info(self::class." - eligible user found with status: {$status} and user id : {$eligibleUser->user_id}");

                return User::find($eligibleUser->user_id);
            }
        }

        return null;
    }

    public function getAdvisorByStatus($onlineStatus, $teamId)
    {
        if ($this->allocationRequest->get('isCHSAdvisor')) {
            info(self::class.' - getAdvisorByStatus: CHS Advisor is required');

            return User::select('users.id as user_id')->chs()->first();
        }

        if ($this->allocationRequest->get('isSICAdvisor')) {
            info(self::class.' - getAdvisorByStatus: SIC Advisor is required');

            $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
        }

        $query = $this->getAdvisorBaseQuery($onlineStatus, $teamId, [RolesEnum::TravelAdvisor])
            ->when(! $teamId, function ($q) {
                $sicUnassistedTeamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
                if ($sicUnassistedTeamId) {
                    $q->whereNotIn('users.id', fn ($query) => $query->select('user_id')->from('user_team')->where('team_id', $sicUnassistedTeamId));
                }
            })
            ->when($this->lead->isSIC($this->allocationRequest->getQuoteType()), function ($q) {
                $q->where('la.is_hardstop', true); // fetch users only with hardstop as true as they are eligible for allocation
            });

        LoggerService::info(self::class." - getAdvisorByStatus query: {$query->toRawSql()}");

        return $query->first();
    }
}

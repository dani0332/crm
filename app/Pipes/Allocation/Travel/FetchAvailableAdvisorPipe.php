<?php

namespace App\Pipes\Allocation\Travel;

use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Models\User;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FetchAvailableAdvisorPipe extends BaseAllocationPipe
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

        if ($this->allocationRequest->get('skipAdvisorEligibilityFetch', false)) {
            LoggerService::info(self::class.' - Skipping advisor eligibility fetch');

            return $next($request);
        }

        $advisor = $this->fetchAvailableAdvisor();

        if (! $advisor) {
            LoggerService::info(self::class.' - No advisor found');

            if ($this->allocationRequest->get('skipAdvisorEligibilityFetch', false)) {
                LoggerService::info(self::class.' - Second call after reset - throwing exception');
                $this->allocationRequest->markAsFailed();
                $this->throw('Advisor not found', self::OK);
            } else {
                LoggerService::info(self::class.' - First call - continuing to ResetNationalityConfigPipe');

                return $next($request);
            }
        }

        $this->allocationRequest->setAdvisor($advisor);

        $this->verifyIfAdvisorIsSameAsPreviousAdvisor($advisor);

        return $next($request);
    }

    private function fetchAvailableAdvisor()
    {
        // Use the team ID that was already evaluated in EvaluateTeamPipe
        $teamId = $this->allocationRequest->getTeamId();

        return $this->findAvailableAdvisor($teamId);
    }

    protected function getAdvisorByStatus($onlineStatus, $teamId)
    {
        if ($this->allocationRequest->get('isCHSAdvisor')) {
            LoggerService::info(self::class.' - getAdvisorByStatus: CHS Advisor is required');

            return User::select('users.id as user_id')->chs()->first();
        }

        if ($this->allocationRequest->get('isSICAdvisor')) {
            LoggerService::info(self::class.' - getAdvisorByStatus: SIC Advisor is required');

            $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
        }

        return $this->getAdvisorBaseQuery($onlineStatus, $teamId, [RolesEnum::TravelAdvisor])
            ->when(! $teamId, function ($q) {
                $sicUnassistedTeamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
                if ($sicUnassistedTeamId) {
                    $q->whereNotIn('users.id', fn ($query) => $query->select('user_id')->from('user_team')->where('team_id', $sicUnassistedTeamId));
                }
            })
            ->when($this->allocationRequest->isSIC(), function ($q) {
                $q->where('la.is_hardstop', true); // fetch users only with hardstop as true as they are eligible for allocation
            })
            ->logRawSql()
            ->first();
    }
}

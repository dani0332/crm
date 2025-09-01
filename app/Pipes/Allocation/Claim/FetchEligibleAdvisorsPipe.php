<?php

namespace App\Pipes\Allocation\Claim;

use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Models\ClaimRequest;
use App\Models\User;
use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FetchEligibleAdvisorsPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        if ($this->allocationRequest->get('skipAdvisorEligibilityFetch', false)) {
            return $next($request);
        }
        $lead = $request->getLead();

        $eligibleAdvisors = $this->fetchEligibleUsersByStatus($lead);
        $request->set('eligibleAdvisors', $eligibleAdvisors);

        return $next($request);
    }

    private function fetchEligibleUsersByStatus(ClaimRequest $lead)
    {

        $advisors = [];

        if (empty($advisors)) {
            $advisors = $this->fetchAdvisors('getAdvisorsByStatus');
        }

        return $advisors ?? [];
    }

    private function fetchAdvisors()
    {
        $statusOrder = $this->getOnlineStatusesInOrder();

        foreach ($statusOrder as $status) {

            $eligibleUsers = $this->getAdvisorsByStatus($status);
            if ($eligibleUsers && count($eligibleUsers) > 0) {
                LoggerService::info(self::class.'::getAdvisorsByStatus - Eligble Users found with the availability status of: '.UserStatusEnum::getUserStatusText($status));

                return $eligibleUsers->toArray();
            }
            LoggerService::info(self::class.'::getAdvisorsByStatus - No Users were found with the availability status of: '.UserStatusEnum::getUserStatusText($status));
        }

        return [];
    }

    protected function getAdvisorsByStatus(int $onlineStatus)
    {
        // Use subquery to calculate allocation_count < max_capacity in the join condition for better
        $roles = [RolesEnum::ClaimsManager];
        $users = User::select('users.id as user_id')
            ->join('claims_lead_allocation_config as la', 'la.user_id', '=', 'users.id')
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('users.status', $onlineStatus)
            ->whereIn('r.name', $roles)
            ->where('la.quote_type_id', $this->allocationRequest->getQuoteType()->id())
            ->activeUser()
            ->whereColumn('la.allocation_count', '<', 'la.max_capacity')
            ->orderBy('la.last_allocated', 'asc')
            ->logRawSql()
            ->distinct('users.id')
            ->get();

        return $users;
    }

}

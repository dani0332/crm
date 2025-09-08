<?php

namespace App\Pipes\Allocation\Claim;

use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Models\ClaimRequest;
use App\Models\User;
use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FetchEligibleManagersPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        if ($this->allocationRequest->get('skipManagerEligibilityFetch', false)) {
            return $next($request);
        }
        $lead = $request->getLead();

        $eligibleManagers = $this->fetchEligibleUsersByStatus($lead);
        $request->set('eligibleManagers', $eligibleManagers);
        return $next($request);
    }

    private function fetchEligibleUsersByStatus(ClaimRequest $lead)
    {

        $managers = [];

        if (empty($managers)) {
            $managers = $this->fetchManagers('getManagersByStatus');
        }

        return $managers ?? [];
    }

    private function fetchManagers()
    {
        $statusOrder = $this->getOnlineStatusesInOrder();

        foreach ($statusOrder as $status) {

            $eligibleUsers = $this->getManagersByStatus($status);
            if ($eligibleUsers && count($eligibleUsers) > 0) {
                LoggerService::info(self::class.'::getManagersByStatus - Eligble Users found with the availability status of: '.UserStatusEnum::getUserStatusText($status));

                return $eligibleUsers->toArray();
            }
            LoggerService::info(self::class.'::getManagersByStatus - No Users were found with the availability status of: '.UserStatusEnum::getUserStatusText($status));
        }

        return [];
    }

    protected function getManagersByStatus(int $onlineStatus)
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

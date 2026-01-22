<?php

namespace App\Pipes\Allocation\Claim;

use App\Models\User;
use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FetchLeadPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request, true);

        $this->resolveLead();
        $lead = $this->allocationRequest->getLead();
        // Check if manager is already assigned
        if ($lead && ! empty($lead->manager_id)) {
            LoggerService::info('Manager is already assigned to this claim. Manager ID: '.$lead->manager_id);
            $this->allocationRequest->markAsAlreadyAssigned();
            $this->allocationRequest->setManager(User::find($lead->manager_id));
            $this->stop('Manager is already assigned', self::OK);
        }

        return $next($request);
    }
}

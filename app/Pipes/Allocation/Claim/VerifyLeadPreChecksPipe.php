<?php

namespace App\Pipes\Allocation\Claim;

use App\Enums\LeadSourceEnum;
use App\Models\User;
use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class VerifyLeadPreChecksPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        // Resolve lead if not already set
        if (! $this->lead) {
            $this->resolveLead();
        }
        $lead = $this->lead;
        // Check if manager is already assigned (reassignment jobs are allowed to proceed)
        if ($lead && ! empty($lead->manager_id) && ! $this->allocationRequest->isReassignmentJob()) {
            LoggerService::info('Manager is already assigned to this claim. Manager ID: '.$lead->manager_id);
            $this->stop('Manager is already assigned', self::OK);
        }

        $isVerified = $this->verifyPreChecks();

        if (! $isVerified) {

            $this->throw('Lead does not meet pre-check criteria', self::NOT_FOUND);

            return false;
        }

        return $next($request);
    }

    private function verifyPreChecks(): bool
    {
        $lead = $this->lead;

        $continueAssignment = false;
        if ($lead->source == LeadSourceEnum::IMCRM) {
            LoggerService::info(self::class.'::verifyPreChecks - Lead source is IMCRM, failing pre-check');
            $continueAssignment = false;
        } elseif (! empty($lead->manager_id) && ! $this->allocationRequest->isReassignmentJob()) {
            LoggerService::info(self::class.'::verifyPreChecks - Manager ID is already set, failing pre-check');
            $this->allocationRequest->markAsAlreadyAssigned();
            $manager = User::find($lead->manager_id);
            $this->allocationRequest->setManager($manager);
            $continueAssignment = false;
        } elseif ($lead->quote_type_id != $this->allocationRequest->getQuoteType()->id()) {
            LoggerService::info(self::class."::verifyPreChecks - Quote type mismatch, failing pre-check {$lead->quote_type_id} != {$this->allocationRequest->getQuoteType()->id()}}");
            $this->allocationRequest->markAsQuoteTypeMismatch();
            $continueAssignment = false;
        } else {
            $continueAssignment = true;
        }

        return $continueAssignment;
    }
}

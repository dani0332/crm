<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ClaimRequest;
use App\Services\ClaimsService;

class ClaimRequestObserver
{
    public function updating(ClaimRequest $claimRequest): void
    {
        $claimService = new ClaimsService();
        if ($claimRequest->isDirty('claim_number')) {
            $originalClaimNumber = $claimRequest->getOriginal('claim_number');
            $newClaimNumber = $claimRequest->claim_number;

            if (empty($originalClaimNumber) && !empty($newClaimNumber)) {
                $claimService->updateClaimSubStatusToClaimRegistered($claimRequest);
            }
        }

        if ($claimRequest->isDirty('claim_sub_status_id')) {
            $originalClaimSubStatusId = $claimRequest->getOriginal('claim_sub_status_id');
            $newClaimSubStatusId = $claimRequest->claim_sub_status_id;

            $shouldCloseTheClaim = $claimService->checkSubStatusForClaimClosure($claimRequest, $newClaimSubStatusId);
            if ($shouldCloseTheClaim) {
                $claimService->markClaimAsClosedAndSendReviewEmail($claimRequest);
            }

        }
    }

    public function updated(ClaimRequest $claimRequest): void{}



    /**
     * Handle the ClaimRequest "creating" event.
     */
    public function creating(ClaimRequest $claimRequest): void
    {
        $claimService = new ClaimsService();
        // If claim number is provided during creation, set status to "Claim registered"
        if (!empty($claimRequest->claim_number)) {
            $claimService->updateClaimSubStatusToClaimRegistered($claimRequest);
        }
    }

    /**
     * Handle the ClaimRequest "created" event.
     */
    public function created(ClaimRequest $claimRequest): void {}
}

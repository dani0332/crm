<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ClaimRequest; 
use App\Services\ClaimsService; 

class ClaimRequestObserver
{
    public function updating(ClaimRequest $claimRequest): void
    {
        $claimService = new ClaimsService;
        if ($claimRequest->isDirty('claim_number')) {
            $originalClaimNumber = $claimRequest->getOriginal('claim_number');
            $newClaimNumber = $claimRequest->claim_number;

            if (empty($originalClaimNumber) && ! empty($newClaimNumber)) {
                $claimService->updateClaimSubStatusToClaimRegistered($claimRequest);
            }
        }

        if ($claimRequest->isDirty('claim_sub_status_id')) {
            $originalClaimSubStatusId = $claimRequest->getOriginal('claim_sub_status_id');
            $newClaimSubStatusId = $claimRequest->claim_sub_status_id;

            $shouldCloseTheClaim = $claimService->checkSubStatusForClaimClosure($claimRequest, $newClaimSubStatusId);
            if ($shouldCloseTheClaim) {
                $claimService->markClaimAsClosed($claimRequest);
            }

        }

         $isCarOrBikeLOB = $claimRequest->isCarOrBikeLOB();

        if($isCarOrBikeLOB){
            if ($claimRequest->isDirty('approved_repair_amount')) {
                $originalApprovedRepairAmount = $claimRequest->getOriginal('approved_repair_amount');
                $newApprovedRepairAmount = $claimRequest->approved_repair_amount;
    
                if (empty($originalApprovedRepairAmount) && ! empty($newApprovedRepairAmount)) {
                    $claimService->updateClaimSubStatusToRepairApprovedAndWIP($claimRequest);
                }
            }
            if ($claimRequest->isDirty('approved_total_loss_amount')) {
                $originalApprovedTotalLossAmount = $claimRequest->getOriginal('approved_total_loss_amount');
                $newApprovedTotalLossAmount = $claimRequest->approved_total_loss_amount;
    
                if (empty($originalApprovedTotalLossAmount) && ! empty($newApprovedTotalLossAmount)) {
                    $claimService->updateClaimSubStatusToTotalLossOfferLetterShared($claimRequest);
                }
            }
            if ($claimRequest->isDirty('approved_cash_loss_amount')) {
                $originalApprovedCashLossAmount = $claimRequest->getOriginal('approved_cash_loss_amount');
                $newApprovedCashLossAmount = $claimRequest->approved_cash_loss_amount;
    
                if (empty($originalApprovedCashLossAmount) && ! empty($newApprovedCashLossAmount)) {
                    $claimService->updateClaimSubStatusToCashLossApproved($claimRequest);
                }
            }
        }
        
        if ($claimRequest->isDirty('claim_decline_reason')) {
            $originalClaimDeclineReason = $claimRequest->getOriginal('claim_decline_reason');
            $newClaimDeclineReason = $claimRequest->claim_decline_reason;

            if (empty($originalClaimDeclineReason) && ! empty($newClaimDeclineReason)) {
                $claimService->markClaimAsClosed($claimRequest);
            }
        }

        // Check if claim status has changed to closed and send Google review email
        if ($claimRequest->isDirty('claim_status_id')) {
            $originalClaimStatusId = $claimRequest->getOriginal('claim_status_id');
            $newClaimStatusId = $claimRequest->claim_status_id;

            // Check if the claim is now closed
          /*   if ($claimService->isClaimStatusClosed($newClaimStatusId) && !$claimService->isClaimStatusClosed($originalClaimStatusId)) {
                $claimService->dispatchGoogleReviewEmail($claimRequest);
            } */
        }
    }

    /**
     * Handle the ClaimRequest "creating" event.
     */
    public function creating(ClaimRequest $claimRequest): void
    {
        $claimService = new ClaimsService;
        // If claim number is provided during creation, set status to "Claim registered"
        if (! empty($claimRequest->claim_number)) {
            $claimService->updateClaimSubStatusToClaimRegistered($claimRequest);
        }
    }
    
}

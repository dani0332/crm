<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ClaimsEnum;
use App\Jobs\Claim\TriggerBirdClaimsFlowJob;
use App\Models\ClaimRequest;
use App\Models\ClaimStatus;
use App\Services\ClaimStatusesService;
use App\Services\EmailServices\ClaimRequestEmailService;
use App\Services\Logger\LoggerService;

class ClaimRequestObserver
{
    protected ClaimStatusesService $claimStatusesService;
    protected ClaimRequestEmailService $claimRequestEmailService;

    /**
     * Create a new observer instance.
     */
    public function __construct(
        ClaimStatusesService $claimStatusesService,
        ClaimRequestEmailService $claimRequestEmailService,
    ) {
        $this->claimStatusesService = $claimStatusesService;
        $this->claimRequestEmailService = $claimRequestEmailService;
    }

    public function updating(ClaimRequest $claimRequest): void
    {

        if ($claimRequest->isDirty('claim_number')) {
            $originalClaimNumber = $claimRequest->getOriginal('claim_number');
            $newClaimNumber = $claimRequest->claim_number;

            if (empty($originalClaimNumber) && ! empty($newClaimNumber)) {
                $this->claimStatusesService->updateClaimSubStatusToClaimRegistered($claimRequest);
            }
        }

        if ($claimRequest->isDirty('claim_sub_status_id')) {
            $newClaimSubStatusId = $claimRequest->claim_sub_status_id;

            $shouldCloseTheClaim = $this->claimStatusesService->checkSubStatusForClaimClosure($claimRequest, $newClaimSubStatusId);
            if ($shouldCloseTheClaim) {
                $this->claimStatusesService->markClaimAsClosed($claimRequest);
                $this->claimRequestEmailService->dispatchClaimGoogleReviewEmail($claimRequest);
            }

        }

        $isCarOrBikeLOB = $claimRequest->isCarOrBikeLOB();

        if ($isCarOrBikeLOB) {
            if ($claimRequest->isDirty('approved_repair_amount')) {
                $originalApprovedRepairAmount = $claimRequest->getOriginal('approved_repair_amount');
                $newApprovedRepairAmount = $claimRequest->approved_repair_amount;

                if (empty($originalApprovedRepairAmount) && ! empty($newApprovedRepairAmount)) {
                    $claimStatusClosed = ClaimStatus::where('text', ClaimsEnum::CLAIM_SUB_STATUS_REPAIR_APPROVED_AND_WORK_IN_PROGRESS->value)->where('is_active', 1)->first();
                    $this->claimStatusesService->updateClaimSubStatus($claimRequest, $claimStatusClosed);
                }
            }
            if ($claimRequest->isDirty('approved_total_loss_amount')) {
                $originalApprovedTotalLossAmount = $claimRequest->getOriginal('approved_total_loss_amount');
                $newApprovedTotalLossAmount = $claimRequest->approved_total_loss_amount;

                if (empty($originalApprovedTotalLossAmount) && ! empty($newApprovedTotalLossAmount)) {
                    $claimStatusTotalLossOfferShared = ClaimStatus::where('text', ClaimsEnum::CLAIM_SUB_STATUS_TOTAL_LOSS_OFFER_LETTER_SHARED->value)->where('is_active', 1)->first();
                    $this->claimStatusesService->updateClaimSubStatus($claimRequest, $claimStatusTotalLossOfferShared);
                }
            }
            if ($claimRequest->isDirty('approved_cash_loss_amount')) {
                $originalApprovedCashLossAmount = $claimRequest->getOriginal('approved_cash_loss_amount');
                $newApprovedCashLossAmount = $claimRequest->approved_cash_loss_amount;

                if (empty($originalApprovedCashLossAmount) && ! empty($newApprovedCashLossAmount)) {
                    $claimStatusCashLossApproved = ClaimStatus::where('text', ClaimsEnum::CLAIM_SUB_STATUS_CASH_LOSS_APPROVED->value)->where('is_active', 1)->first();
                    $this->claimStatusesService->updateClaimSubStatus($claimRequest, $claimStatusCashLossApproved);
                }
            }
            if ($claimRequest->isDirty('policy_number') && $claimRequest->manager_id && $claimRequest->insurance_provider_id) {
                $originalPolicyNumber = $claimRequest->getOriginal('policy_number');
                $newPolicyNumber = $claimRequest->policy_number;
                if ($originalPolicyNumber != $newPolicyNumber) {
                    TriggerBirdClaimsFlowJob::dispatch($claimRequest->uuid)->delay(now()->addSeconds(10));
                    LoggerService::info('TriggerBirdClaimsFlowJob dispatched for claim', [
                        'uuid' => $claimRequest->uuid,
                        'claim_id' => $claimRequest->id,
                    ]);
                }
            }
        }

        // Check if claim status has changed to closed and send Google review email
        if ($claimRequest->isDirty('claim_status_id')) {
            $originalClaimStatusId = $claimRequest->getOriginal('claim_status_id');
            $newClaimStatusId = $claimRequest->claim_status_id;

            // Check if the claim is now closed
            if ($this->claimStatusesService->isClaimStatusClosed($newClaimStatusId) && ! $this->claimStatusesService->isClaimStatusClosed($originalClaimStatusId)) {
                $this->claimRequestEmailService->dispatchClaimGoogleReviewEmail($claimRequest);
            }
        }
    }

    /**
     * Handle the ClaimRequest "creating" event.
     * Set initial sub_status on the model only; do not save—the record is not inserted yet.
     */
    public function creating(ClaimRequest $claimRequest): void
    {
        if (! empty($claimRequest->claim_number)) {
            $this->claimStatusesService->setClaimSubStatusToClaimRegisteredForCreation($claimRequest);
        }
    }

}

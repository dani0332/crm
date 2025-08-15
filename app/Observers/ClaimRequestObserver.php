<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ClaimsEnum;
use App\Jobs\SendGoogleReviewEmailJob;
use App\Models\ClaimRequest;
use App\Models\ClaimsStatus;
use App\Services\ClaimsService;
use App\Services\Logger\LoggerService;

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

    /**
     * Handle the ClaimRequest "created" event.
     */
    public function created(ClaimRequest $claimRequest): void {}

    public function updated(ClaimRequest $claimRequest): void
    {
        // Check if claim status has changed to closed and send Google review email
        if ($claimRequest->isDirty('claim_status_id')) {
            $originalClaimStatusId = $claimRequest->getOriginal('claim_status_id');
            $newClaimStatusId = $claimRequest->claim_status_id;

            // Check if the claim is now closed
            /* if ($this->isClaimStatusClosed($newClaimStatusId) && !$this->isClaimStatusClosed($originalClaimStatusId)) {
                $this->dispatchGoogleReviewEmail($claimRequest);
            } */
        }
    }

    /**
     * Check if the given claim status ID represents a closed status
     */
    private function isClaimStatusClosed(?int $statusId): bool
    {
        if (! $statusId) {
            return false;
        }

        $closedStatus = ClaimsStatus::where('id', $statusId)->where('is_active', 1)->first();

        return $closedStatus !== null && $closedStatus->text === ClaimsEnum::CLAIM_STATUS_CLOSED->value;
    }

    /**
     * Dispatch Google review email job for the claim request
     */
    private function dispatchGoogleReviewEmail(ClaimRequest $claimRequest): void
    {
        try {
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Dispatching Google review email job - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'customer_email' => $claimRequest->email,
            ]);

            // Dispatch the job to send Google review email
            SendGoogleReviewEmailJob::dispatch($claimRequest->uuid);

        } catch (\Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Failed to dispatch Google review email job - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

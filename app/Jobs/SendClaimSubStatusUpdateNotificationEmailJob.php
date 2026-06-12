<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Models\ClaimRequest;
use App\Services\EmailServices\ClaimRequestEmailService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Send Claim Sub Status Update Notification Email Job
 *
 * Queued job to send claim sub status update notification emails to customers.
 */
class SendClaimSubStatusUpdateNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 100;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 300;

    /**
     * The claim request UUID to send claim sub status update notification email for
     */
    private string $claimRequestUuid;

    private string $customerMessage;

    /**
     * Create a new job instance.
     * Defers dispatch until after the current DB transaction commits, so the job is not
     * queued if the enclosing transaction rolls back (e.g. when dispatched from the observer).
     */
    public function __construct(string $claimRequestUuid, string $message)
    {
        $this->claimRequestUuid = $claimRequestUuid;
        $this->customerMessage = $message;
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(ClaimRequestEmailService $claimRequestEmailService): void
    {
        try {

            LoggerService::startQuoteLogging($this->claimRequestUuid, LoggerFeatureEnum::CLAIM_SUB_STATUS_CUSTOMER_UPDATE_EMAIL);

            LoggerService::info(' Job started - Claim UUID: '.$this->claimRequestUuid, [
                'claim_request_uuid' => $this->claimRequestUuid,
                'customer_message' => $this->customerMessage,
                'time' => now(),
            ]);

            // Find the claim request
            $claimRequest = ClaimRequest::where('uuid', $this->claimRequestUuid)->first();

            if (! $claimRequest) {
                LoggerService::warning(' Claim request not found - Claim UUID: '.$this->claimRequestUuid, [
                    'claim_request_uuid' => $this->claimRequestUuid,
                ]);

                return;
            }

            // Check if customer is eligible for review email
            if (! $claimRequestEmailService->isEligibleForSubStatusUpdateEmail($claimRequest)) {
                LoggerService::info(' Customer not eligible for Claim sub status update notification email - Claim UUID: '.$claimRequest->uuid, [
                    'claim_request_id' => $claimRequest->id,
                    'claim_uuid' => $claimRequest->uuid,
                    'customer_email' => $claimRequest->email,
                ]);

                return;
            }

            // Send the  Claim sub status update notification email
            $responseCode = $claimRequestEmailService->sendClaimSubStatusCustomerUpdateEmail($claimRequest, $this->customerMessage);

            // Log success or failure based on response code
            if (in_array($responseCode, [200, 201, 202])) {
                LoggerService::info(' Claim sub status update notification email sent successfully - Claim UUID: '.$this->claimRequestUuid, [
                    'response_code' => $responseCode,
                    'customer_email' => $claimRequest->email,
                    'claim_uuid' => $this->claimRequestUuid,
                ]);
            } else {
                LoggerService::error(' Claim sub status update notification email failed to send - Claim UUID: '.$this->claimRequestUuid, [
                    'response_code' => $responseCode,
                    'customer_email' => $claimRequest->email,
                    'claim_uuid' => $this->claimRequestUuid,
                ]);
            }

        } catch (Exception $e) {
            LoggerService::error(' Job failed - Claim UUID: '.$this->claimRequestUuid, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'claim_request_uuid' => $this->claimRequestUuid,
            ]);

            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        LoggerService::error(' Job permanently failed after all retries - Claim UUID: '.$this->claimRequestUuid, [
            'error' => $exception->getMessage(),
            'claim_request_uuid' => $this->claimRequestUuid,
            'attempts' => $this->attempts(),
        ]);
    }
}

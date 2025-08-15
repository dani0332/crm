<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ClaimRequest;
use App\Services\EmailServices\ClaimRequestEmailService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Send Google Review Email Job
 *
 * Queued job to send Google review emails to customers when their claims are closed.
 * Uses the existing Bird integration for email delivery.
 */
class SendGoogleReviewEmailJob implements ShouldQueue
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
     * The claim request UUID to send review email for
     */
    private string $claimRequestUuid;

    /**
     * Create a new job instance.
     *
     * @param string $claimRequestUuid
     */
    public function __construct(string $claimRequestUuid)
    {
        $this->claimRequestUuid = $claimRequestUuid;
    }

    /**
     * Execute the job.
     *
     * @param ClaimRequestEmailService $googleReviewEmailService
     * @return void
     */
    public function handle(ClaimRequestEmailService $googleReviewEmailService): void
    {
        try {
            LoggerService::info(self::class . '::' . __FUNCTION__ . ' - Job started - Claim UUID: ' . $this->claimRequestUuid, [
                'claim_request_uuid' => $this->claimRequestUuid,
                'time' => now(),
            ]);

            // Find the claim request
            $claimRequest = ClaimRequest::where('uuid', $this->claimRequestUuid)->first();

            if (!$claimRequest) {
                LoggerService::warning(self::class . '::' . __FUNCTION__ . ' - Claim request not found - Claim UUID: ' . $this->claimRequestUuid, [
                    'claim_request_uuid' => $this->claimRequestUuid,
                ]);
                return;
            }

            // Check if customer is eligible for review email
            if (!$googleReviewEmailService->isEligibleForReviewEmail($claimRequest)) {
                LoggerService::info(self::class . '::' . __FUNCTION__ . ' - Customer not eligible for Google review email - Claim UUID: ' . $claimRequest->uuid, [
                    'claim_request_id' => $claimRequest->id,
                    'claim_uuid' => $claimRequest->uuid,
                    'customer_email' => $claimRequest->email,
                ]);
                return;
            }

            // Send the Google review email
            $responseCode = $googleReviewEmailService->sendGoogleReviewEmail($claimRequest);

            // Log success or failure based on response code
            if (in_array($responseCode, [200, 201])) {
                LoggerService::info(self::class . '::' . __FUNCTION__ . ' - Google review email sent successfully - Claim UUID: ' . $this->claimRequestUuid, [
                    'response_code' => $responseCode,
                    'customer_email' => $claimRequest->email,
                    'claim_uuid' => $this->claimRequestUuid,
                ]);
            } else {
                LoggerService::error(self::class . '::' . __FUNCTION__ . ' - Google review email failed to send - Claim UUID: ' . $this->claimRequestUuid, [
                    'response_code' => $responseCode,
                    'customer_email' => $claimRequest->email,
                    'claim_uuid' => $this->claimRequestUuid,
                ]);
            }

        } catch (Exception $e) {
            LoggerService::error(self::class . '::' . __FUNCTION__ . ' - Job failed - Claim UUID: ' . $this->claimRequestUuid, [
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
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception): void
    {
        LoggerService::error(self::class . '::' . __FUNCTION__ . ' - Job permanently failed after all retries - Claim UUID: ' . $this->claimRequestUuid, [
            'error' => $exception->getMessage(),
            'claim_request_uuid' => $this->claimRequestUuid,
            'attempts' => $this->attempts(),
        ]);
    }
}

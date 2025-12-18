<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DeviceFailureTypeEnum;
use App\Enums\EnvEnum;
use App\Enums\WorkflowTypeEnum;
use App\Exceptions\BirdWebhookException;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Services\BirdService;
use App\Services\DeviceFailureEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDeviceFailureEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    private string $logPrefix = 'SendDeviceFailureEmailJob:';

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $quoteId,
        private DeviceFailureTypeEnum $failureType,
        private ?string $providerCode = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::info("{$this->logPrefix} Starting failure email job", extra: [
            'quoteId' => $this->quoteId,
            'failureType' => $this->failureType->value,
            'attempt' => $this->attempts(),
        ]);

        try {
            $quote = PersonalQuote::with(['advisor', 'customer'])->find($this->quoteId);

            if (! $quote) {
                LoggerService::error("{$this->logPrefix} Quote not found", extra: [
                    'quoteId' => $this->quoteId,
                ]);
                return;
            }

            // Validate LOB is Device and Provider is NGI
            if (! app(DeviceFailureEmailService::class)->isValidDeviceNgiQuote($quote, $this->providerCode)) {
                LoggerService::warning("{$this->logPrefix} Skipping - not a valid Device/NGI quote", extra: [
                    'quoteId' => $this->quoteId,
                    'quoteTypeId' => $quote->quote_type_id,
                ]);
                return;
            }

            // Send email via Bird
            $response = $this->sendViaBird($quote);

            if ($response === 200) {
              $this->logFailureAttempt($quote);
                  LoggerService::info("{$this->logPrefix} Failure email sent successfully via Bird", extra: [
                  'quoteId' => $this->quoteId,
                  'failureType' => $this->failureType->value,
                  'refId' => $quote->code,
              ]);
            } else {
                throw new BirdWebhookException(
                    "Bird webhook returned status: {$response}",
                    BirdWebhookException::WEBHOOK_FAILED,
                    $response
                );
            }

        } catch (\Exception $e) {
            LoggerService::error("{$this->logPrefix} Failed to send failure email", extra: [
                'quoteId' => $this->quoteId,
                'failureType' => $this->failureType->value,
                'attempt' => $this->attempts(),
            ], exception: $e);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Send failure email via Bird webhook
     */
    private function sendViaBird(PersonalQuote $quote): ?int
    {
        $appEnv = config('constants.APP_ENV');

        // Build CC emails
        $cc = $this->buildCcEmails($quote, $appEnv);

        // Determine recipient based on failure type
        $recipient = $this->getRecipient($quote);

        // Build email data for Bird workflow
        $emailData = (object) [
            'quoteUID' => $quote->uuid,
            'refId' => $quote->code,
            'customerEmail' => $recipient['email'],
            'customerName' => $recipient['name'],
            'recipientEmail' => $recipient['email'],
            'recipientName' => $recipient['name'],
            'triggerPoint' => $this->failureType->getTriggerPointText(),
            'failureType' => $this->failureType->getDisplayName(),
            'imcrmReferenceNumber' => $quote->code,
            'imcrmLink' => $this->generateImcrmLink($quote),
            'escalationLink' => $this->getEscalationLink(),
            'cc' => $cc,
            'workflowType' => WorkflowTypeEnum::DEVICE_AUTOMATION_FAILED,
        ];



        // Get Bird workflow URL from ApplicationStorage
        $birdUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_BIRD_URL)->first();

        if (! $birdUrl) {
            LoggerService::warning("{$this->logPrefix} Bird URL not found in ApplicationStorage", extra: [
                'key' => ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_BIRD_URL,
            ]);
            return null;
        }

        LoggerService::info("{$this->logPrefix} Triggering Bird webhook", extra: [
            'quoteId' => $this->quoteId,
            'url' => $birdUrl->value,
            'emailData' => $emailData,
        ]);

        $response = app(BirdService::class)->triggerWebHookRequest($birdUrl->value, $emailData);

        return $response?->status_code;
    }

    /**
     * Get recipient based on failure type (FRD requirement)
     */
    private function getRecipient(PersonalQuote $quote): array
    {
        // Booking Details API failure goes to Production Approval Team
        if ($this->failureType === DeviceFailureTypeEnum::BOOK_POLICY) {
            $toEmail = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_TO)
                ?: 'production.approval.team@insurancemarket.ae';
            return ['email' => $toEmail, 'name' => 'Production Approval Team'];
        }

        // Other failures go to assigned advisor
        if ($quote->advisor) {
            return ['email' => $quote->advisor->email, 'name' => $quote->advisor->name];
        }

        // Fallback
        $toEmail = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_TO)
            ?: 'production.approval.team@insurancemarket.ae';
        return ['email' => $toEmail, 'name' => 'Device Support Team'];
    }

    /**
     * Build CC emails
     */
    private function buildCcEmails(PersonalQuote $quote, string $appEnv): array
    {
        $cc = [
            'approvalemail' => null,
            'prodemail' => null,
            'advisoremail' => null,
        ];

        if (in_array($appEnv, [EnvEnum::PRODUCTION, EnvEnum::STAGING])) {
            $cc['approvalemail'] = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_CC);
            $cc['prodemail'] = getAppStorageValueByKey(ApplicationStorageEnums::PRODUCTION_APPROVAL_EMAIL);

            // Include advisor in CC for booking failures
            if ($this->failureType === DeviceFailureTypeEnum::BOOK_POLICY && $quote->advisor) {
                $cc['advisoremail'] = $quote->advisor->email;
            }
        }

        return $cc;
    }

    private function generateImcrmLink(PersonalQuote $quote): string
    {
        $baseUrl = config('app.url', env('APP_URL'));
        return "{$baseUrl}/personal-quotes/device/{$quote->uuid}";
    }

    private function getEscalationLink(): string
    {
        $link = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_ESCALATION_LINK);

        return $link ?: 'https://forms.clickup.com/2197982/f/232ey-57398/E5NVOINDYMZRFPTA3T';
    }

    /**
     * Log the failure attempt to API logs
     */
    private function logFailureAttempt(PersonalQuote $quote): void
    {
        // Log to API logs table for auditing
        $quote->apiLogs()->create([
            'request_type' => 'FAILURE_EMAIL_SENT_VIA_BIRD',
            'request_data' => json_encode([
                'failure_type' => $this->failureType->value,
                'trigger_point' => $this->failureType->getTriggerPointText(),
                'attempt' => $this->attempts(),
            ]),
            'response_data' => json_encode([
                'status' => 'sent',
                'sent_at' => now()->toIso8601String(),
            ]),
            'status' => 'success',
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        LoggerService::error("{$this->logPrefix} Job failed after all retries", extra: [
            'quoteId' => $this->quoteId,
            'failureType' => $this->failureType->value,
            'error' => $exception->getMessage(),
        ]);
    }
}

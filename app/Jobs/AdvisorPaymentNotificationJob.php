<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Exceptions\AdvisorNotificationFailedException;
use App\Exceptions\BirdUrlNotFoundException;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\HttpFoundation\Response;

class AdvisorPaymentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(private array $payment) {}

    /**
     * Execute the job.
     */
    public function handle(BirdService $birdService): void
    {
        $emailData = (object) [
            'date' => Carbon::now()->format(config('constants.DATE_DISPLAY_FORMAT')),
            'crmLink' => url('/reports/payment-summary'),
            ...$this->payment,
        ];

        $birdUrl = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_ADVISOR_PAYMENT_NOTIFICATION_WORKFLOW_URL, false, true) ?? '';
        if (! $birdUrl) {
            LoggerService::error('AdvisorPaymentNotificationJob: Bird URL not found');

            throw new BirdUrlNotFoundException;
        }

        $response = $birdService->triggerWebHookRequest($birdUrl, $emailData);

        if ($response?->status_code !== Response::HTTP_OK) {
            LoggerService::error('AdvisorPaymentNotificationJob: Email sent failed', [
                'response' => json_encode($response),
            ]);

            throw new AdvisorNotificationFailedException(
                'Failed to send advisor payment notification email',
                $response?->status_code ?? 0
            );
        }

        LoggerService::info('AdvisorPaymentNotificationJob: Email sent successfully');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        LoggerService::error('AdvisorPaymentNotificationJob: Job failed after all retry attempts', [
            'error' => $exception->getMessage(),
            'advisor_id' => $this->payment['advisorId'] ?? null,
            'advisor_email' => $this->payment['advisorEmail'] ?? null,
        ]);
    }
}

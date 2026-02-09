<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AdvisorPaymentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(private array $payment)
    {
    }

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

            throw new \Exception('Bird URL not found for advisor payment notification');
        }

        $response = $birdService->triggerWebHookRequest($birdUrl, $emailData);

        if ($response?->status_code !== Response::HTTP_OK) {
            LoggerService::error('AdvisorPaymentNotificationJob: Email sent failed', [
                'response' => json_encode($response),
            ]);

            throw new \Exception('Failed to send advisor payment notification email. Status: '.($response?->status_code ?? 'unknown'));
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

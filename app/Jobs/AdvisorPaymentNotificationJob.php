<?php

namespace App\Jobs;

use App\Enums\WorkflowTypeEnum;
use App\Services\EmailServices\WebEngageService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
    public function handle(): void
    {
        $emailData = (object) [
            'workflowType' => WorkflowTypeEnum::ADVISOR_PAYMENT_NOTIFICATION,
            'date' => Carbon::now()->format(config('constants.DATE_DISPLAY_FORMAT')),
            'crmLink' => url('/reports/payment-summary'),
            ...$this->payment,
            'customerId' => $this->payment['advisorEmail'] ?? '',
            'firstName' => $this->payment['advisorName'] ?? '',
            'lastName' => '',
            'customerEmail' => $this->payment['advisorEmail'] ?? '',
            'customerMobile' => '',
            'quoteUID' => '',
        ];

        app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::ADVISOR_PAYMENT_NOTIFICATION, (array) $emailData);

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

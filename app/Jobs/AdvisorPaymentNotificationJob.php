<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Models\ApplicationStorage;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use App\Services\SendEmailCustomerService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdvisorPaymentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $payment = [];
    public $tries = 3;
    public $timeout = 30;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payment)
    {
        $this->payment = $payment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(BirdService $birdService)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::AUTHORISED_PAYMENT_NOTIFICATION_TO_ADVISOR);

        $emailData = (object) array_merge([
            'date' => Carbon::now()->format('d-m-Y'),
            'crmLink' => url('/reports/payment-summary'),
        ], $this->payment);

        $birdUrl = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_ADVISOR_PAYMENT_NOTIFICATION_WORKFLOW_URL) ?? '';
        if (! $birdUrl) {
            LoggerService::error('AdvisorPaymentNotificationJob: Bird URL not found');

            return;
        }

        $response = $birdService->triggerWebHookRequest($birdUrl, $emailData);

        if ($response?->status_code == JsonResponse::HTTP_OK) {
            LoggerService::info('AdvisorPaymentNotificationJob: Email sent successfully');
        } else {
            LoggerService::error('AdvisorPaymentNotificationJob: Email sent failed', [
                'response' => json_encode($response),
            ]);
        }
    }
}

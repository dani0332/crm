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
     *
     * @return void
     */
    public function handle(BirdService $birdService)
    {
        $emailData = (object) [
            'date' => Carbon::now()->format(config('constants.DATE_DISPLAY_FORMAT')),
            'crmLink' => url('/reports/payment-summary'),
            ...$this->payment,
        ];

        $birdUrl = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_ADVISOR_PAYMENT_NOTIFICATION_WORKFLOW_URL, useCache: true) ?? '';
        if (! $birdUrl) {
            LoggerService::error('AdvisorPaymentNotificationJob: Bird URL not found');

            return;
        }

        $response = $birdService->triggerWebHookRequest($birdUrl, $emailData);

        if ($response?->status_code == Response::HTTP_OK) {
            LoggerService::info('AdvisorPaymentNotificationJob: Email sent successfully');
        } else {
            LoggerService::error('AdvisorPaymentNotificationJob: Email sent failed', [
                'response' => json_encode($response),
            ]);
        }
    }
}

<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PartnerPolicyDocumentJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $timeout = 100;
    public int $tries = 3;
    private array $documents;
    private string $partnerEmail;

    public function __construct($documents, $partnerEmail)
    {
        $this->documents = $documents;
        $this->partnerEmail = $partnerEmail;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        LoggerService::info('PartnerPolicyDocumentJob - Starting job', extra: ['partnerEmail' => $this->partnerEmail]);

        $emailData = (object) [
            'partnerEmail' => $this->partnerEmail,
            ...$this->documents,
        ];

        $birdUrlKey = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_PARTNER_AUTOMATION_COMPLETED_WORKFLOW_URL);
        $response = app(BirdService::class)->triggerWebHookRequest($birdUrlKey, $emailData);

        LoggerService::info('PartnerPolicyDocumentJob - Job Response ', extra: ['response' => json_encode($response)]);

        if ($response?->status_code != 200) {
            LoggerService::info('PartnerPolicyDocumentJob - Job failed', extra: [
                'response' => json_encode($response),
            ]);

            return;
        }

        LoggerService::info('PartnerPolicyDocumentJob - email sent successfully');
    }

    public function failed(Exception $ex)
    {
        LoggerService::error('PartnerPolicyDocumentJob - Failed', exception: $ex);
    }
}

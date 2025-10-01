<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Services\EmailServices\CarEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFailedCarRenewalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $tries = 3;
    public $timeout = 60;
    public $backoff = 60;

    public $failedPolicyNumbers;
    public $renewalsUploadLeadsId;

    /**
     * Create a new job instance.
     */
    public function __construct($failedPolicyNumbers, $renewalsUploadLeadsId)
    {
        $this->failedPolicyNumbers = $failedPolicyNumbers;
        $this->renewalsUploadLeadsId = $renewalsUploadLeadsId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $workflowSwitch = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW)->first();

        if ($workflowSwitch) {
            app(CarEmailService::class)->sendFailedCarRenewals($this->failedPolicyNumbers, $this->renewalsUploadLeadsId);

            LoggerService::info(self::class.' - Car CQF Renewals Errors | Time: '.now().' | Ref-ID: '.implode(', ', $this->failedPolicyNumbers));
        } else {
            LoggerService::info(self::class.' - Car CQF Renewals Errors | Time: '.now().' | Ref-ID: '.$this->failedPolicyNumbers);
        }

    }
}

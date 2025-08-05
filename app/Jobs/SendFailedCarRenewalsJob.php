<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Services\EmailServices\CarEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendFailedCarRenewalsJob implements ShouldQueue
{
    use Queueable;

    public $failedQuotes;
    /**
     * Create a new job instance.
     */
    public function __construct($failedQuotes)
    {
        $this->failedQuotes = $failedQuotes;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $workflowSwitch = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW)->first();

        if ($workflowSwitch) {
            app(CarEmailService::class)->sendFailedCarRenewals($this->failedQuotes);

            LoggerService::info(self::class.' - Car CQF Renewals Errors | Time: '.now().' | Ref-ID: '.implode(', ', $this->failedQuotes));
        } else {
            LoggerService::info(self::class.' - Car CQF Renewals Errors | Time: '.now().' | Ref-ID: '.$this->failedQuotes);
        }

    }
}
